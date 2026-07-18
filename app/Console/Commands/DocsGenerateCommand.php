<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Throwable;

final class DocsGenerateCommand extends Command
{
    protected $signature = 'docs:generate';

    protected $description = 'Gera um índice técnico das classes da aplicação.';

    public function handle(): int
    {
        $classes = [];

        foreach (File::allFiles(app_path()) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = $file->getContents();

            if (! preg_match('/namespace\s+([^;]+);/m', $contents, $namespace)
                || ! preg_match('/(?:final\s+|abstract\s+)?(?:readonly\s+)?class\s+(\w+)|interface\s+(\w+)|trait\s+(\w+)/m', $contents, $classMatch)) {
                continue;
            }

            $shortName = $classMatch[1] ?: ($classMatch[2] ?: $classMatch[3]);
            $className = trim($namespace[1]).'\\'.$shortName;
            $methods = [];

            try {
                if (class_exists($className) || interface_exists($className) || trait_exists($className)) {
                    $reflection = new ReflectionClass($className);

                    foreach ($reflection->getMethods() as $method) {
                        if ($method->isPublic() && $method->getDeclaringClass()->getName() === $className) {
                            $methods[] = $method->getName();
                        }
                    }
                }
            } catch (Throwable) {
                // O índice ainda registra a classe mesmo quando Reflection não está disponível.
            }

            $classes[] = [
                'class' => $className,
                'file' => $file->getRelativePathname(),
                'methods' => $methods,
            ];
        }

        usort($classes, fn (array $a, array $b): int => $a['class'] <=> $b['class']);

        $lines = [
            '# Índice de Classes',
            '',
            '> Gerado por `php artisan docs:generate`.',
            '',
            '| Classe | Arquivo | Métodos públicos declarados |',
            '|---|---|---|',
        ];

        foreach ($classes as $class) {
            $methods = $class['methods'] === [] ? '—' : implode(', ', array_map(fn (string $method): string => "`{$method}()`", $class['methods']));
            $lines[] = "| `{$class['class']}` | `app/{$class['file']}` | {$methods} |";
        }

        File::ensureDirectoryExists(base_path('docs/generated'));
        File::put(base_path('docs/generated/class-index.md'), implode(PHP_EOL, $lines).PHP_EOL);

        $this->info('Índice gerado em docs/generated/class-index.md.');

        return self::SUCCESS;
    }
}
