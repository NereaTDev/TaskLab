<?php

namespace App\Services;

use App\Models\GithubConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GithubContextService
{
    private const API_BASE    = 'https://api.github.com';
    private const MAX_FILES   = 8;
    private const MAX_FILE_KB = 80;

    private const INCLUDE_EXTENSIONS = [
        'php', 'js', 'ts', 'tsx', 'jsx', 'vue', 'blade.php',
        'py', 'rb', 'go', 'java', 'kt', 'swift', 'cs',
        'html', 'css', 'scss', 'json', 'yaml', 'yml',
        'md', 'env.example',
    ];

    private const EXCLUDE_PATTERNS = [
        'node_modules/', 'vendor/', '.git/', 'dist/', 'build/',
        'storage/', 'bootstrap/cache/', '.next/', '__pycache__/',
        'package-lock.json', 'composer.lock', 'yarn.lock',
    ];

    // Archivos de documentación prioritarios para generar la memoria
    private const DOC_PRIORITY = [
        'readme.md', 'claude.md', 'contributing.md', 'architecture.md',
        'docs/', '.github/', 'wiki/',
    ];

    // ── Public API ─────────────────────────────────────────────────────────

    public function isConnected(): bool
    {
        return GithubConnection::active() !== null;
    }

    /**
     * Sincroniza el árbol de archivos desde GitHub y regenera la memoria del proyecto.
     */
    public function syncFileTree(GithubConnection $conn): int
    {
        $response = Http::withToken($conn->token)
            ->withHeaders(['Accept' => 'application/vnd.github+json'])
            ->timeout(30)
            ->get(self::API_BASE . "/repos/{$conn->owner}/{$conn->repo}/git/trees/{$conn->branch}", [
                'recursive' => '1',
            ]);

        if (! $response->ok()) {
            throw new \RuntimeException(
                "GitHub API error {$response->status()}: " . $response->json('message', $response->body())
            );
        }

        $tree = $response->json('tree', []);

        $paths = collect($tree)
            ->filter(fn ($node) => $node['type'] === 'blob')
            ->pluck('path')
            ->filter(fn ($path) => $this->shouldInclude($path))
            ->values()
            ->all();

        $conn->update([
            'file_tree'      => $paths,
            'last_synced_at' => now(),
        ]);

        // Generar memoria del proyecto automáticamente tras sincronizar
        $this->syncProjectMemory($conn);

        return count($paths);
    }

    /**
     * Genera y persiste la memoria del proyecto leyendo los archivos de documentación del repo.
     */
    public function syncProjectMemory(GithubConnection $conn): void
    {
        try {
            $docPaths = $this->findDocPaths($conn);
            if (empty($docPaths)) {
                return;
            }

            $fileContents = [];
            foreach ($docPaths as $path) {
                $content = $this->fetchFileContent($conn, $path);
                if ($content === null) {
                    continue;
                }
                // Truncar archivos muy largos
                $lines = explode("\n", $content);
                if (count($lines) > 300) {
                    $content = implode("\n", array_slice($lines, 0, 300)) . "\n... (truncado)";
                }
                $fileContents[] = "### {$path}\n```\n{$content}\n```";
            }

            if (empty($fileContents)) {
                return;
            }

            $memory = $this->generateProjectMemory($conn, $fileContents);
            if ($memory) {
                $conn->update(['project_memory' => $memory]);
            }
        } catch (\Throwable $e) {
            Log::warning("GithubContextService: syncProjectMemory failed: " . $e->getMessage());
        }
    }

    /**
     * Devuelve los archivos más relevantes del árbol para la descripción dada.
     */
    public function findRelevantPaths(string $description, int $limit = self::MAX_FILES): array
    {
        $conn = GithubConnection::active();
        if (! $conn || empty($conn->file_tree)) {
            return [];
        }

        $keywords = $this->extractKeywords($description);
        if (empty($keywords)) {
            return [];
        }

        $scored = [];
        foreach ($conn->file_tree as $path) {
            $pathLower = Str::lower($path);
            $score     = 0;

            foreach ($keywords as $kw) {
                if (str_contains($pathLower, $kw)) {
                    $filename = Str::lower(basename($path));
                    $score   += str_contains($filename, $kw) ? 3 : 1;
                }
            }

            if ($score > 0) {
                $scored[] = ['path' => $path, 'score' => $score];
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] - $a['score']);

        return array_column(array_slice($scored, 0, $limit), 'path');
    }

    /**
     * Construye el contexto de código completo para inyectar en el prompt de la IA.
     * Siempre antepone la memoria del proyecto (si existe) antes del código relevante.
     */
    public function buildCodeContext(string $description): string
    {
        try {
            $conn = GithubConnection::active();
            if (! $conn) {
                return '';
            }

            $parts = [];

            // 1. Memoria del proyecto — siempre al inicio si existe
            if ($conn->project_memory) {
                $parts[] = "MEMORIA DEL PROYECTO ({$conn->owner}/{$conn->repo}):\n\n{$conn->project_memory}";
            }

            // 2. Archivos de código relevantes para esta descripción concreta
            $paths = $this->findRelevantPaths($description);
            if (! empty($paths)) {
                $sections = [];
                foreach ($paths as $path) {
                    $content = $this->fetchFileContent($conn, $path);
                    if ($content === null) {
                        continue;
                    }
                    $lines = explode("\n", $content);
                    if (count($lines) > 300) {
                        $content = implode("\n", array_slice($lines, 0, 300)) . "\n... (truncado)";
                    }
                    $sections[] = "### {$path}\n```\n{$content}\n```";
                }

                if (! empty($sections)) {
                    $header = "CÓDIGO FUENTE RELEVANTE ({$conn->owner}/{$conn->repo} · rama {$conn->branch})";
                    if ($conn->site_url) {
                        $header .= "\nURL DE PRODUCCIÓN: {$conn->site_url} — usa este dominio como base para primary_url y additional_urls.";
                    }
                    $parts[] = $header . "\n\n" . implode("\n\n", $sections);
                }
            } elseif ($conn->site_url && ! empty($parts)) {
                // Aunque no haya archivos relevantes, añadir la URL si hay memoria
                $parts[] = "URL DE PRODUCCIÓN: {$conn->site_url}";
            }

            return implode("\n\n---\n\n", $parts);

        } catch (\Throwable $e) {
            Log::warning("GithubContextService: buildCodeContext failed: " . $e->getMessage());
            return '';
        }
    }

    // ── Private helpers ────────────────────────────────────────────────────

    /**
     * Devuelve los archivos de documentación del repo, priorizando README, CLAUDE.md y docs/.
     */
    private function findDocPaths(GithubConnection $conn): array
    {
        $tree = $conn->file_tree ?? [];

        // Clasificar: prioridad alta (readme, claude.md) vs media (docs/*.md) vs resto .md
        $high   = [];
        $medium = [];
        $low    = [];

        foreach ($tree as $path) {
            $lower    = Str::lower($path);
            $basename = Str::lower(basename($path));

            if (! str_ends_with($lower, '.md') &&
                ! in_array($basename, ['package.json', 'composer.json'])) {
                continue;
            }

            if (in_array($basename, ['readme.md', 'claude.md'])) {
                $high[] = $path;
            } elseif (str_starts_with($lower, 'docs/') || str_starts_with($lower, '.github/')) {
                $medium[] = $path;
            } else {
                $low[] = $path;
            }
        }

        // Limitar: hasta 3 high + 4 medium + 3 low = 10 archivos máx
        return array_merge(
            array_slice($high, 0, 3),
            array_slice($medium, 0, 4),
            array_slice($low, 0, 3),
        );
    }

    /**
     * Llama a OpenAI para generar un resumen estructurado del proyecto a partir de su documentación.
     */
    private function generateProjectMemory(GithubConnection $conn, array $fileContents): ?string
    {
        $apiKey = config('services.openai.api_key');
        if (! $apiKey) {
            // Sin API key: devolver los archivos directamente como memoria
            return implode("\n\n", $fileContents);
        }

        $filesText = implode("\n\n", $fileContents);

        $systemPrompt = <<<PROMPT
Eres un asistente técnico que genera documentación concisa de proyectos de software.
Dado el contenido de los archivos de documentación de un repositorio, genera un documento de memoria del proyecto en markdown.

El documento DEBE incluir:
- **Stack tecnológico**: lenguajes, frameworks, librerías principales
- **Arquitectura**: estructura, módulos principales, patrones usados
- **Funcionalidades clave**: qué hace el proyecto y para qué sirve
- **Convenciones de código**: naming, patrones, estilo si están documentados
- **URLs/rutas importantes**: si las hay en la documentación
- **Contexto adicional para la IA**: cualquier información relevante para entender las tareas del proyecto

Sé conciso pero completo. Usa markdown con headers (##) y listas.
Responde SOLO con el documento markdown, sin texto adicional.
PROMPT;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(45)->post('https://api.openai.com/v1/chat/completions', [
                'model'       => config('services.openai.tasklab_model', 'gpt-4.1-mini'),
                'messages'    => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => "Repositorio: {$conn->owner}/{$conn->repo}\n\nARCHIVOS:\n\n{$filesText}"],
                ],
                'temperature' => 0.2,
                'max_tokens'  => 2000,
            ]);

            if ($response->ok()) {
                return trim($response->json('choices.0.message.content', ''));
            }

            Log::warning('GithubContextService: generateProjectMemory OpenAI error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

        } catch (\Throwable $e) {
            Log::warning("GithubContextService: generateProjectMemory failed: " . $e->getMessage());
        }

        return null;
    }

    private function fetchFileContent(GithubConnection $conn, string $path): ?string
    {
        try {
            $response = Http::withToken($conn->token)
                ->withHeaders(['Accept' => 'application/vnd.github+json'])
                ->timeout(15)
                ->get(self::API_BASE . "/repos/{$conn->owner}/{$conn->repo}/contents/" . ltrim($path, '/'), [
                    'ref' => $conn->branch,
                ]);

            if (! $response->ok()) {
                return null;
            }

            $data = $response->json();

            if (isset($data['size']) && $data['size'] > self::MAX_FILE_KB * 1024) {
                return null;
            }

            $encoded = $data['content'] ?? null;
            if (! $encoded) {
                return null;
            }

            return base64_decode(str_replace(["\n", "\r"], '', $encoded));

        } catch (\Throwable $e) {
            Log::debug("GithubContextService: failed to fetch {$path}: " . $e->getMessage());
            return null;
        }
    }

    private function extractKeywords(string $description): array
    {
        $stopWords = [
            'el', 'la', 'los', 'las', 'un', 'una', 'unos', 'unas',
            'de', 'del', 'en', 'con', 'por', 'para', 'que', 'no',
            'es', 'se', 'me', 'le', 'al', 'lo', 'ya', 'si', 'mi',
            'the', 'a', 'an', 'in', 'on', 'at', 'to', 'is', 'it',
            'and', 'or', 'not', 'for', 'with', 'this', 'that',
            'falla', 'error', 'problema', 'bug', 'issue',
            'cuando', 'page', 'página', 'botón', 'button',
        ];

        $tokens = preg_split('/[\s\.,;:!\?\'"\(\)\[\]{}<>\/\\\\]+/u', Str::lower($description));

        return collect($tokens)
            ->filter(fn ($t) => strlen($t) >= 3 && ! in_array($t, $stopWords, true))
            ->unique()
            ->values()
            ->all();
    }

    private function shouldInclude(string $path): bool
    {
        foreach (self::EXCLUDE_PATTERNS as $pattern) {
            if (str_starts_with($path, $pattern) || str_contains($path, '/' . $pattern)) {
                return false;
            }
        }

        foreach (self::INCLUDE_EXTENSIONS as $ext) {
            if (str_ends_with($path, '.' . $ext) || str_ends_with($path, $ext)) {
                return true;
            }
        }

        return false;
    }
}
