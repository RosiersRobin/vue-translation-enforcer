<?php

namespace Robinrosiers\VueTranslationEnforcer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ListMissingTranslations extends Command
{
    protected $signature = 'vte:show-missing';
    protected $description = 'List missing translation keys';

    public function handle(): int
    {
        $langPath = lang_path();
        $locales = collect(File::directories($langPath))
            ->mapWithKeys(fn ($dir) => [basename($dir) => $dir]);

        if ($locales->isEmpty()) {
            $this->error('No language folders found in: ' . $langPath);
            return Command::FAILURE;
        }

        $translations = [];

        foreach ($locales as $locale => $dir) {
            $files = File::allFiles($dir);

            foreach ($files as $file) {
                $keyPrefix = str_replace(['/', '.php'], ['.', ''], $file->getRelativePathname());
                $array = File::getRequire($file->getRealPath());

                if (!is_array($array)) {
                    continue;
                }

                $flat = $this->flatten($array, $keyPrefix);

                foreach ($flat as $key => $value) {
                    $translations[$key][$locale] = $value;
                }
            }
        }

        if (empty($translations)) {
            $this->warn('No translations found.');
            return Command::SUCCESS;
        }

        ksort($translations);
        $locales = $locales->keys()->sort()->values()->all();
        $headers = array_merge(['Key'], $locales);

        $rows = [];

        foreach ($translations as $key => $values) {
            if (count($values) === count($locales)) {
                continue;
            }

            $row = [$key];

            foreach ($locales as $locale) {
                $value = $values[$locale] ?? null;
                $row[] = $value ?? '<fg=red;options=bold>missing</>';
            }

            $rows[] = $row;
        }

        if (empty($rows)) {
            $this->info('✅ All translations are present across all languages.');
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->info('Missing translations found:');
        $this->table($headers, $rows);

        return Command::SUCCESS;
    }

    protected function flatten(array $array, string $prefix = ''): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            $fullKey = $prefix ? "$prefix.$key" : $key;

            if (is_array($value)) {
                $results += $this->flatten($value, $fullKey);
            } else {
                $results[$fullKey] = $value;
            }
        }

        return $results;
    }
}