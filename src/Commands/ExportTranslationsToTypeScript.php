<?php

namespace Rosiersrobin\VueTranslationEnforcer\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ExportTranslationsToTypeScript extends Command
{
    protected $signature = 'vte:export';

    protected $description = 'Export translation keys to a TypeScript file for IDE auto-completion';

    public function handle(): void
    {
        $langPath = lang_path();
        $outputPath = resource_path('js/translations/lang-keys.ts');
        $translations = [];

        $files = (new Filesystem)->allFiles($langPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($langPath . DIRECTORY_SEPARATOR, '', $file->getRealPath());
            $parts = explode(DIRECTORY_SEPARATOR, $relativePath);

            if (count($parts) < 2) {
                continue; // Skip if there's no subdirectory like "en/auth.php"
            }

            array_shift($parts);

            $baseKey = str_replace('.php', '', implode('.', $parts)); // Remaining path becomes key prefix

            $array = include $file->getRealPath();
            if (! is_array($array)) {
                continue;
            }

            $flat = $this->flatten($array, $baseKey);
            foreach (array_keys($flat) as $key) {
                $translations[(string) $key] = '';
            }
        }

        ksort($translations);

        // Ensure output directory exists
        (new Filesystem)->ensureDirectoryExists(dirname($outputPath));

        $content = "// ⚠️ This file is auto-generated.\n\n"
            . "export const translations = {\n"
            . collect($translations)->map(fn ($v, $k) => "  \"{$k}\": \"\",")->implode("\n")
            . "\n} as const;\n\n"
            . "export type TranslationKey = keyof typeof translations;\n";

        file_put_contents($outputPath, $content);

        $this->info("✅ Translations exported to: {$outputPath}");
    }

    protected function flatten(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;
            if (is_array($value)) {
                $result += $this->flatten($value, $newKey);
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}