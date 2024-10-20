<?php

namespace PavelZanek\LaravelDeepl\Services\Builders;

use DeepL\DeepLException;
use PavelZanek\LaravelDeepl\DeeplClient;

/**
 * Class LanguageBuilder
 *
 * Helps build and execute language retrieval with method chaining.
 */
class LanguageBuilder
{
    /**
     * The translator instance.
     */
    protected DeeplClient $translator;

    /**
     * Indicates whether to retrieve source languages.
     */
    protected bool $retrieveSource = false;

    /**
     * Indicates whether to retrieve target languages.
     */
    protected bool $retrieveTarget = false;

    /**
     * Indicates whether to retrieve glossary languages.
     */
    protected bool $retrieveGlossary = false;

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
     * Sets the builder to retrieve source languages.
     *
     * @return $this
     */
    public function source(): self
    {
        $this->retrieveSource = true;

        return $this;
    }

    /**
     * Sets the builder to retrieve target languages.
     *
     * @return $this
     */
    public function target(): self
    {
        $this->retrieveTarget = true;

        return $this;
    }

    /**
     * Sets the builder to retrieve glossary-supported languages.
     *
     * @return $this
     */
    public function glossary(): self
    {
        $this->retrieveGlossary = true;

        return $this;
    }

    /**
     * Executes the language retrieval based on set flags.
     *
     * @return array<string, array<array-key, \DeepL\GlossaryLanguagePair|\DeepL\Language>> The retrieved languages.
     *
     * @throws DeepLException
     */
    public function get(): array
    {
        $result = [];

        if ($this->retrieveSource) {
            $result['source_languages'] = $this->translator->getSourceLanguages();
        }

        if ($this->retrieveTarget) {
            $result['target_languages'] = $this->translator->getTargetLanguages();
        }

        if ($this->retrieveGlossary) {
            $result['glossary_languages'] = $this->translator->getGlossaryLanguages();
        }

        return $result;
    }
}
