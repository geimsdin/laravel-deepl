<?php

namespace PavelZanek\LaravelDeepl\Services\Builders;

use DeepL\DeepLException;
use DeepL\DocumentStatus;
use PavelZanek\LaravelDeepl\DeeplClient;

/**
 * Class DocumentTranslationBuilder
 *
 * Helps build and execute document translations with method chaining.
 */
class DocumentTranslationBuilder
{
    /**
     * The translator instance.
     */
    protected DeeplClient $translator;

    /**
     * The path to the input document.
     */
    protected ?string $inputFile = null;

    /**
     * The path to save the translated document.
     */
    protected ?string $outputFile = null;

    /**
     * The source language code.
     */
    protected ?string $sourceLang = null;

    /**
     * The target language code.
     */
    protected ?string $targetLang = null;

    /**
     * Additional options for the translation.
     *
     * @var array<array-key, mixed>
     */
    protected array $options = [];

    /**
     * Constructor.
     *
     * @param  DeeplClient  $translator  The translator instance.
     */
    public function __construct(DeeplClient $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Sets the input document path.
     *
     * @param  string  $file  The path to the input document.
     * @return $this
     */
    public function inputFile(string $file): self
    {
        $this->inputFile = $file;

        return $this;
    }

    /**
     * Sets the output document path.
     *
     * @param  string  $file  The path to save the translated document.
     * @return $this
     */
    public function outputFile(string $file): self
    {
        $this->outputFile = $file;

        return $this;
    }

    /**
     * Sets the source language code.
     *
     * @param  string  $lang  The source language code.
     * @return $this
     */
    public function sourceLang(string $lang): self
    {
        $this->sourceLang = $lang;

        return $this;
    }

    /**
     * Sets the target language code.
     *
     * @param  string  $lang  The target language code.
     * @return $this
     */
    public function targetLang(string $lang): self
    {
        $this->targetLang = $lang;

        return $this;
    }

    /**
     * Adds additional options for the translation.
     *
     * @param  array<array-key, mixed>  $options  The options to add.
     * @return $this
     */
    public function options(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Enables document minification.
     *
     * @param  bool  $enable  Whether to enable minification.
     * @return $this
     */
    public function enableMinification(bool $enable = true): self
    {
        $this->options['enable_document_minification'] = $enable;

        return $this;
    }

    /**
     * Executes the document translation.
     *
     *
     * @throws DeepLException
     */
    public function translate(): DocumentStatus
    {
        if (! $this->inputFile) {
            throw new \InvalidArgumentException('Input path is required.');
        }

        if (! $this->outputFile) {
            throw new \InvalidArgumentException('Output path is required.');
        }

        if (! $this->targetLang) {
            throw new \InvalidArgumentException('Target language is required.');
        }

        return $this->translator->translateDocument(
            inputFile: $this->inputFile,
            outputFile: $this->outputFile,
            sourceLang: $this->sourceLang,
            targetLang: $this->targetLang,
            options: $this->options
        );
    }
}
