<?php

namespace Robinrosiers\VueTranslationEnforcer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Symfony\Component\VarExporter\VarExporter;

class AutoTranslateToToNewLocale extends Command
{
    protected $signature = 'lang:translate {target : Target language code} {--source=en : Source language code}';
    protected $description = 'Translate language files to a new language';

    public function handle(): int
    {
        $source = $this->option('source') ?? 'en';
        $target = $this->argument('target');

        $sourcePath = lang_path($source);
        $targetPath = lang_path($target);

        if (!File::exists($sourcePath)) {
            $this->error("Source language folder '$source' does not exist.");
            return 1;
        }

        File::makeDirectory($targetPath, 0755, true, true);

        $files = File::allFiles($sourcePath);

        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname();
            $sourceArray = File::getRequire($file->getRealPath());
            $translatedArray = $this->translateArray($sourceArray, $source, $target);

            $targetFilePath = $targetPath . '/' . $relativePath;
            File::ensureDirectoryExists(dirname($targetFilePath));
            File::put($targetFilePath, "<?php\n\nreturn " . VarExporter::export($translatedArray) . ";\n");
        }

        $this->info("Translation complete! Language '$target' created.");
        return 0;
    }

    protected function translateArray(array $array, string $sourceLang, string $targetLang): array
    {
        $translated = [];
        foreach ($array as $key => $value) {
            $stringKey = is_int($key) ? (string) $key : $key;

            if (is_array($value)) {
                $translated[$stringKey] = $this->translateArray($value, $sourceLang, $targetLang);
            } else {
                $translated[$stringKey] = $this->translateTextPreservingPlaceholders($value, $sourceLang, $targetLang);
                //usleep(500000); // sleep 0.5s to avoid API rate limits
            }
        }
        return $translated;
    }

    protected function translateTextPreservingPlaceholders($text, $sourceLang, $targetLang)
    {
        preg_match_all('/:([a-zA-Z0-9_]+)/', $text, $matches);

        $placeholders = $matches[0] ?? [];
        $map = [];

        // Use ultra-safe opaque tokens
        $tempText = $text;
        foreach ($placeholders as $i => $placeholder) {
            $token = "[[##-$i-##]]";
            $map[$token] = $placeholder;
            $tempText = str_replace($placeholder, $token, $tempText);
        }

        // Translate the text
        $translated = $this->translate($tempText, $sourceLang, $targetLang);

        // Restore placeholders exactly
        foreach ($map as $token => $original) {
            $translated = str_replace($token, $original, $translated);
        }

        return $translated;
    }

    protected function translate($text, $source, $target)
    {
        try {
            $response = Http::get('https://ftapi.pythonanywhere.com/translate', [
                'text' => $text,
                'sl' => $source,
                'dl' => $target,
            ]);

            if ($response->successful()) {
                return $response->json()['destination-text'];
            }
        } catch (\Exception $e) {
            $this->warn("Translation failed for: $text. Error: " . $e->getMessage());
        }

        return $text; // fallback
    }
}