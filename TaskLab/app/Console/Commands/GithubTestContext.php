<?php

namespace App\Console\Commands;

use App\Models\GithubConnection;
use App\Services\GithubContextService;
use Illuminate\Console\Command;

class GithubTestContext extends Command
{
    protected $signature   = 'github:test-context {description? : Descripción de prueba}';
    protected $description = 'Prueba el contexto de código que la IA recibiría para una descripción dada';

    public function handle(GithubContextService $service): int
    {
        $conn = GithubConnection::active();

        if (! $conn) {
            $this->error('No hay conexión activa de GitHub.');
            $this->line('Conecta un repositorio en Configuración > GitHub.');
            return 1;
        }

        $this->info("Repositorio: {$conn->full_name} (rama: {$conn->branch})");
        $this->line("Último sync: " . ($conn->last_synced_at?->diffForHumans() ?? 'nunca'));

        $fileCount = is_array($conn->file_tree) ? count($conn->file_tree) : 0;
        $this->line("Archivos indexados: {$fileCount}");

        if ($fileCount === 0) {
            $this->warn('El árbol de archivos está vacío. Sincroniza desde Configuración > GitHub > Sincronizar.');
            return 1;
        }

        $description = $this->argument('description')
            ?? $this->ask('Descripción de prueba (simula un mensaje de Discord)', 'el botón de home no funciona');

        $this->newLine();
        $this->line('─────────────────────────────────────────');
        $this->info('Archivos relevantes encontrados:');

        $paths = $service->findRelevantPaths($description);

        if (empty($paths)) {
            $this->warn('No se encontraron archivos relevantes para esa descripción.');
            $this->line('Prueba con palabras que coincidan con rutas o nombres de archivos del repo.');
            return 0;
        }

        foreach ($paths as $i => $path) {
            $this->line("  " . ($i + 1) . ". {$path}");
        }

        $this->newLine();
        $this->line('─────────────────────────────────────────');

        if ($this->confirm('¿Quieres ver el contexto completo que recibirá la IA?', false)) {
            $context = $service->buildCodeContext($description);
            if ($context) {
                $this->line($context);
            } else {
                $this->warn('No se pudo obtener el contenido de los archivos (verifica el token de GitHub).');
            }
        }

        return 0;
    }
}
