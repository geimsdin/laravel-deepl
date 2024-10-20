<?php

namespace PavelZanek\LaravelDeepl\Console\Commands;

use Illuminate\Console\Command;
use PavelZanek\LaravelDeepl\Services\TranslationService;
use PavelZanek\LaravelDeepl\Traits\Console\RunsPint;

class TranslateLangFolderCommand extends Command
{
    use RunsPint;

    /**
     * @var string
     */
    protected $signature = 'deepl:translate-folder
                            {folder : Path to the folder to translate}
                            {--sourceLang=en : Source language (default: en)}
                            {--targetLang=cs : Target language (default: cs)}
                            {--with-pint : Run Pint after translation (only in local environment)}';

    /**
     * @var string
     */
    protected $description = 'Translate all localization files in a folder using DeepL';

    public function __construct(
        private readonly TranslationService $translationService
    ) {
        parent::__construct();
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle(): int
    {
        $sourceLang = $this->option('sourceLang');
        $targetLang = $this->option('targetLang');
        $folderPath = $this->argument('folder');

        if (! is_string($folderPath) || ! is_string($sourceLang) || ! is_string($targetLang)) {
            $this->error('Invalid arguments provided.');

            return self::FAILURE;
        }

        /** @var string $folderPath */
        $folderPath = realpath($folderPath);
        $baseLangPath = realpath(lang_path());

        // Skip if the provided folder is the root lang folder
        if ($folderPath === $baseLangPath) {
            $this->warn("Skipping translation for the root '{$baseLangPath}' folder. Please specify a subfolder, like 'lang/en'.");

            return self::SUCCESS;
        }

        try {
            $this->translationService->translateFolder($folderPath, $sourceLang, $targetLang);

            $this->info('All files in the folder have been successfully translated.');

            $this->maybeRunPint();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
