<?php

namespace PavelZanek\LaravelDeepl\Services\Builders\Glossary;

use DeepL\GlossaryEntries;
use DeepL\GlossaryInfo;
use PavelZanek\LaravelDeepl\DeeplClient;

/**
 * Class CreateGlossaryBuilder
 *
 * Helps build and execute glossary creation with method chaining.
 */
class CreateGlossaryBuilder
{
    /**
     * The translator instance.
     */
    protected DeeplClient $translator;

    /**
     * The glossary name.
     */
    protected ?string $name = null;

    /**
     * The source language code.
     */
    protected ?string $sourceLang = null;

    /**
     * The target language code.
     */
    protected ?string $targetLang = null;

    /**
     * The glossary entries.
     *
     * @var array<string, string>
     */
    protected array $entries = [];

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
     * Sets the glossary name.
     *
     * @param  string  $name  The glossary name.
     * @return $this
     */
    public function name(string $name): self
    {
        $this->name = $name;

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
     * Adds a single glossary entry.
     *
     * @param  string  $source  The source term.
     * @param  string  $target  The target term.
     * @return $this
     */
    public function addEntry(string $source, string $target): self
    {
        $this->entries[$source] = $target;

        return $this;
    }

    /**
     * Adds multiple glossary entries from an associative array.
     *
     * @param  array<string, string>  $entries  Associative array of source => target terms.
     * @return $this
     */
    public function addEntries(array $entries): self
    {
        foreach ($entries as $source => $target) {
            $this->addEntry($source, $target);
        }

        return $this;
    }

    /**
     * Adds multiple glossary entries from a GlossaryEntries object.
     *
     * @param  GlossaryEntries  $glossaryEntries  The GlossaryEntries object.
     * @return $this
     */
    public function addEntriesFromGlossaryEntries(GlossaryEntries $glossaryEntries): self
    {
        foreach ($glossaryEntries->getEntries() as $source => $target) {
            $this->addEntry($source, $target);
        }

        return $this;
    }

    /**
     * Executes the glossary creation.
     *
     * @throws \DeepL\DeepLException
     */
    public function create(): GlossaryInfo
    {
        if (! $this->name) {
            throw new \InvalidArgumentException('Glossary name is required.');
        }

        if (! $this->sourceLang) {
            throw new \InvalidArgumentException('Source language is required.');
        }

        if (! $this->targetLang) {
            throw new \InvalidArgumentException('Target language is required.');
        }

        if (empty($this->entries)) {
            throw new \InvalidArgumentException('At least one glossary entry is required.');
        }

        return $this->translator->createGlossary(
            $this->name,
            $this->sourceLang,
            $this->targetLang,
            GlossaryEntries::fromEntries($this->entries)
        );
    }
}
