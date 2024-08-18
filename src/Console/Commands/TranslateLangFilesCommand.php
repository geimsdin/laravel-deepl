<?php

namespace PavelZanek\LaravelDeepl\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PavelZanek\LaravelDeepl\DeeplClient;

class TranslateLangFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deepl:translate
                            {file : Path to the file to translate}
                            {--sourceLang=en : Source language (default: en)}
                            {--targetLang=cs : Target language (default: cs)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Translate Laravel localization files using DeepL';

    public function __construct(
        private readonly DeeplClient $client
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle(): int
    {
        $sourceLang = $this->option('sourceLang');
        $targetLang = $this->option('targetLang');
        $filePath = $this->argument('file');

        if (! is_string($filePath) || ! is_string($sourceLang) || ! is_string($targetLang)) {
            $this->error('Invalid arguments provided.');

            return self::FAILURE;
        }

        if (! File::exists($filePath)) {
            $this->error("Source file does not exist: {$filePath}");

            return self::FAILURE;
        }

        $isJson = pathinfo($filePath, PATHINFO_EXTENSION) === 'json';
        $targetFilePath = str_replace("/{$sourceLang}/", "/{$targetLang}/", $filePath);
        $targetDir = dirname($targetFilePath);

        // Ensure the target directory exists
        if (! File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        if ($isJson) {
            // Handle JSON translation
            $translations = json_decode(File::get($filePath), true);
            $existingTranslations = File::exists($targetFilePath) ? json_decode(File::get($targetFilePath), true) : [];
        } else {
            // Handle PHP translation
            $translations = include $filePath;
            $existingTranslations = File::exists($targetFilePath) ? include $targetFilePath : [];
        }

        $mergedTranslations = array_merge($existingTranslations, $this->translateArray($translations, $existingTranslations, $sourceLang, $targetLang));

        if ($isJson) {
            // Write JSON translations
            File::put($targetFilePath, json_encode($mergedTranslations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); // @phpstan-ignore-line
        } else {
            // Write PHP translations
            $formattedContent = "<?php\n\nreturn ".$this->arrayToShortSyntax($mergedTranslations).";\n";
            File::put($targetFilePath, $formattedContent);
        }

        $this->info("Translations have been successfully written to: {$targetFilePath}");

        return self::SUCCESS;
    }

    /**
     * Translate an array of strings.
     *
     * @param  array<string, mixed>  $translations
     * @param  array<string, mixed>  $existingTranslations
     * @return array<string, mixed>
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function translateArray(array $translations, array $existingTranslations, string $sourceLang, string $targetLang): array
    {
        $translatedContent = [];

        foreach ($translations as $key => $value) {
            if (is_array($value)) {
                // Recursive translation for nested arrays
                $translatedContent[$key] = $this->translateArray($value, $existingTranslations[$key] ?? [], $sourceLang, $targetLang); // @phpstan-ignore-line
            } else {
                // Skip translation if the key already exists in the target file
                if (isset($existingTranslations[$key])) {
                    $translatedContent[$key] = $existingTranslations[$key];

                    continue;
                }

                // Pattern to match placeholders, including spaces around them
                $pattern = '/(\s*:[a-zA-Z_]+\s*)/';
                $parts = preg_split($pattern, $value, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY); // @phpstan-ignore-line

                $translatedParts = [];
                foreach ($parts as $part) { // @phpstan-ignore-line
                    // Translate only non-placeholder parts
                    if (preg_match($pattern, $part)) {
                        $translatedParts[] = $part; // Keep placeholder parts intact
                    } else {
                        $translatedParts[] = $this->client->textTranslation($part)
                            ->sourceLang($sourceLang)
                            ->targetLang($targetLang)
                            ->getTranslation();
                    }
                }

                // Reassemble the parts into the final translated string
                $translatedContent[$key] = implode('', $translatedParts);
            }
        }

        return $translatedContent;
    }

    /**
     * Convert an array to PHP short array syntax.
     *
     * @param  array<string, mixed>  $array
     */
    private function arrayToShortSyntax(array $array): string
    {
        $export = var_export($array, true);
        $export = preg_replace("/^(\s*)array \(/m", '$1[', $export);
        $export = preg_replace("/\)(,?)$/m", ']$1', $export); // @phpstan-ignore-line

        return is_string($export) ? $export : '';
    }
}
