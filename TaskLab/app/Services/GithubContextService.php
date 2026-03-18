<?php

namespace App\Services;

use App\Models\GithubConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GithubContextService
{
    private const API_BASE    = 'https://api.github.com';
    private const MAX_FILES   = 8;     // Max files to fetch content for
    private const MAX_FILE_KB = 80;    // Skip files larger than this (KB)

    // Extensions we care about — skip binaries, lock files, etc.
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

    // ── Public API ─────────────────────────────────────────────────────────

    public function isConnected(): bool
    {
        return GithubConnection::active() !== null;
    }

    /**
     * Fetch the full file tree from GitHub and cache it in the DB.
     * Returns the number of files cached, or throws on error.
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

        return count($paths);
    }

    /**
     * Given a task description, find the most relevant file paths in the cached tree.
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
                    // Bonus for filename match vs directory match
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
     * Build a code-context string to inject into the AI prompt.
     * Returns an empty string if no connection or no relevant files.
     */
    public function buildCodeContext(string $description): string
    {
        try {
            $conn = GithubConnection::active();
            if (! $conn) {
                return '';
            }

            $paths = $this->findRelevantPaths($description);
            if (empty($paths)) {
                return '';
            }

            $sections = [];

            foreach ($paths as $path) {
                $content = $this->fetchFileContent($conn, $path);
                if ($content === null) {
                    continue;
                }

                // Truncate very long files
                $lines = explode("\n", $content);
                if (count($lines) > 300) {
                    $content = implode("\n", array_slice($lines, 0, 300)) . "\n... (truncado)";
                }

                $sections[] = "### {$path}\n```\n{$content}\n```";
            }

            if (empty($sections)) {
                return '';
            }

            $header = "CÓDIGO FUENTE RELEVANTE DEL REPOSITORIO ({$conn->owner}/{$conn->repo} · rama {$conn->branch})";

            if ($conn->site_url) {
                $header .= "\nURL DE PRODUCCIÓN: {$conn->site_url} — usa este dominio como base para construir las URLs en primary_url y additional_urls.";
            }

            return $header . "\n\n" . implode("\n\n", $sections);

        } catch (\Throwable $e) {
            Log::warning("GithubContextService: buildCodeContext failed: " . $e->getMessage());
            return '';
        }
    }

    // ── Private helpers ────────────────────────────────────────────────────

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

            // Skip files over the size limit
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

    /**
     * Extract meaningful search keywords from a task description.
     * Strips common stop-words and returns lowercased tokens ≥ 3 chars.
     */
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

        // Tokenize
        $tokens = preg_split('/[\s\.,;:!\?\'"\(\)\[\]{}<>\/\\\\]+/u', Str::lower($description));

        return collect($tokens)
            ->filter(fn ($t) => strlen($t) >= 3 && ! in_array($t, $stopWords, true))
            ->unique()
            ->values()
            ->all();
    }

    private function shouldInclude(string $path): bool
    {
        // Exclude known noisy paths
        foreach (self::EXCLUDE_PATTERNS as $pattern) {
            if (str_starts_with($path, $pattern) || str_contains($path, '/' . $pattern)) {
                return false;
            }
        }

        // Include only known text-based extensions
        foreach (self::INCLUDE_EXTENSIONS as $ext) {
            if (str_ends_with($path, '.' . $ext) || str_ends_with($path, $ext)) {
                return true;
            }
        }

        return false;
    }
}
