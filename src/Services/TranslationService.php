<?php

namespace PavelZanek\LaravelDeepl\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use PavelZanek\LaravelDeepl\DeeplClient;

class TranslationService
{
    public function __construct(
        private DeeplClient $client
    ) {}

    /**
     * Recursively translate all files in a folder.
     *
     * @throws \DeepL\DeepLException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function translateFolder(string $folderPath, string $sourceLang, string $targetLang): void
    {
        if (! File::exists($folderPath) || ! File::isDirectory($folderPath)) {
            throw new \Exception("Folder does not exist: {$folderPath}");
        }

        /** @var string $folderPath */
        $folderPath = realpath($folderPath);
        $baseLangPath = realpath(lang_path());

        if ($folderPath === $baseLangPath) {
            throw new \Exception("Cannot translate the root '{$baseLangPath}' folder. Please specify a subfolder, like 'lang/en'.");
        }

        $files = File::allFiles($folderPath);

        foreach ($files as $file) {
            $this->translateFile($file->getPathname(), $sourceLang, $targetLang);
        }
    }

    /**
     * Translate a single file.
     *
     * @param  array<int, string>  $returnTranslations
     * @return array<array-key, mixed>
     *
     * @throws \DeepL\DeepLException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function translateFile(
        string $filePath,
        string $sourceLang,
        string $targetLang,
        array $returnTranslations = [],
        bool $skipFileExistsCheck = false
    ): array {
        if (! File::exists($filePath)) {
            if ($skipFileExistsCheck) {
                return [];
            }

            throw new \Exception("Source file does not exist: {$filePath}");
        }

        $isJson = pathinfo($filePath, PATHINFO_EXTENSION) === 'json';
        $baseName = basename($filePath);

        if ($isJson && $baseName === "{$sourceLang}.json") {
            // Handle JSON files in the root lang directory
            $targetFilePath = dirname($filePath)."/{$targetLang}.json";
        } else {
            // Handle files (JSON with non-convenient file names and PHP files) in language subdirectories
            $targetFilePath = str_replace("/{$sourceLang}/", "/{$targetLang}/", $filePath);
            if ($filePath === $targetFilePath) {
                // JSON file, we can skip it
                return [];
            }
        }

        // Load source translations
        $translations = $this->loadTranslations($filePath);

        // Load existing target translations
        $existingTranslations = File::exists($targetFilePath)
            ? $this->loadTranslations($targetFilePath)
            : [];

        // Merge existing translations with new translations from the source file
        $mergedTranslations = array_merge(
            $existingTranslations,
            $this->translateArray($translations, $existingTranslations, $sourceLang, $targetLang)
        );

        // If specific keys are provided, filter the translations accordingly
        $filteredTranslations = [];
        if (! empty($returnTranslations)) {
            foreach ($returnTranslations as $key) {
                $filteredTranslations[$key] = $this->getTranslationByKey($mergedTranslations, $key);
            }
        }

        // Save merged translations
        $this->saveTranslations($targetFilePath, $mergedTranslations);

        return $filteredTranslations;
    }

    /**
     * Load translations from a file.
     *
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    private function loadTranslations(string $filePath): array
    {
        if (! File::exists($filePath)) {
            throw new \Exception("File does not exist: {$filePath}");
        }

        $isJson = pathinfo($filePath, PATHINFO_EXTENSION) === 'json';

        if ($isJson) {
            $translations = json_decode(File::get($filePath), true);
        } else {
            $translations = include $filePath;
        }

        if (! is_array($translations)) {
            throw new \Exception("Invalid translation file format: {$filePath}");
        }

        return $translations;
    }

    /**
     * Translate an array of strings.
     *
     * @param  array<string, mixed>  $translations
     * @param  array<string, mixed>  $existingTranslations
     * @return array<string, mixed>
     *
     * @throws \DeepL\DeepLException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function translateArray(array $translations, array|string $existingTranslations, string $sourceLang, string $targetLang): array
    {
        $translatedContent = [];

        if (is_string($existingTranslations)) {
            return $translatedContent;
        }

        foreach ($translations as $key => $value) {
            if (is_array($value)) {
                /** @var array<array-key, mixed> $value */
                $value = $value;
                /** @var array<array-key, mixed> $existingTranslationsArray */
                $existingTranslationsArray = $existingTranslations[$key] ?? [];
                $translatedContent[$key] = $this->translateArray($value, $existingTranslationsArray, $sourceLang, $targetLang);
            } else {
                /** @var string $value */
                $value = $value;
                if (isset($existingTranslations[$key])) {
                    $translatedContent[$key] = $existingTranslations[$key];
                } else {
                    // Translate the string individually
                    $translatedContent[$key] = $this->translateTextWithPlaceholders($value, $sourceLang, $targetLang);
                }
            }
        }

        return $translatedContent;
    }

    /**
     * Translate text with placeholders, ensuring placeholders are preserved.
     *
     * @throws \DeepL\DeepLException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function translateTextWithPlaceholders(string $text, string $sourceLang, string $targetLang): string
    {
        $pattern = '/(:\w+)/';
        $placeholders = [];

        // Replace placeholders with tokens
        /** @var string $textWithTokens */
        $textWithTokens = preg_replace_callback($pattern, function ($matches) use (&$placeholders) {
            $token = '__PLACEHOLDER_'.count($placeholders).'__';
            $placeholders[$token] = $matches[0];

            return $token;
        }, $text);

        // Translate text with tokens
        $translatedText = $this->translateText($textWithTokens, $sourceLang, $targetLang);

        // Replace tokens back with original placeholders
        $translatedText = str_replace(array_keys($placeholders), array_values($placeholders), $translatedText);

        return $translatedText;
    }

    /**
     * Translate a given text from source language to target language.
     *
     * @throws \DeepL\DeepLException
     */
    private function translateText(string $text, string $sourceLang, string $targetLang): string
    {
        $translation = $this->client->translateText($text, $sourceLang, $targetLang);

        return $translation->text; // @phpstan-ignore-line
    }

    /**
     * Get a specific translation by a dot-notated key, excluding the first part (filename).
     *
     * @param  array<string, mixed>  $translations
     */
    private function getTranslationByKey(array $translations, string $key): mixed
    {
        $keyParts = explode('.', $key, 2);
        $remainingKey = $keyParts[1] ?? $key;

        return Arr::get($translations, $remainingKey);
    }

    /**
     * Save translations to a file.
     *
     * @param  array<string, mixed>  $translations
     *
     * @throws \Exception
     */
    private function saveTranslations(string $filePath, array $translations): void
    {
        $isJson = pathinfo($filePath, PATHINFO_EXTENSION) === 'json';

        // Ensure the directory exists
        $dir = dirname($filePath);
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        if ($isJson) {
            $content = json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            if ($content === false) {
                throw new \Exception("Failed to encode JSON for file: {$filePath}");
            }
            File::put($filePath, $content);
        } else {
            $content = "<?php\n\nreturn ".$this->arrayToShortSyntax($translations).";\n";
            File::put($filePath, $content);
        }
    }

    /**
     * Convert an array to PHP short array syntax.
     *
     * @param  array<string, mixed>  $array
     */
    private function arrayToShortSyntax(array $array): string
    {
        $export = var_export($array, true);
        /** @var string $export */
        $export = preg_replace("/^(\s*)array \(/m", '$1[', $export);
        $export = preg_replace("/\)(,?)$/m", ']$1', $export);

        return is_string($export) ? $export : '';
    }
}
