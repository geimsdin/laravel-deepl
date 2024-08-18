<?php

namespace PavelZanek\LaravelDeepl\Clients\V2;

use GuzzleHttp\Client;

class DeeplGlossaryClient
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Create a new glossary.
     *
     * @param  array<string, string>  $entries
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createGlossary(string $name, string $sourceLang, string $targetLang, array $entries): mixed
    {
        $formattedEntries = implode("\n", array_map(
            fn ($source, $target) => "$source\t$target",
            array_keys($entries),
            $entries
        ));

        $response = $this->client->post('glossaries', [
            'json' => [
                'name' => $name,
                'source_lang' => $sourceLang,
                'target_lang' => $targetLang,
                'entries' => $formattedEntries,
                'entries_format' => 'tsv',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get details of a specific glossary.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getGlossary(string $glossaryId): mixed
    {
        $response = $this->client->get("glossaries/{$glossaryId}");

        return json_decode($response->getBody(), true);
    }

    /**
     * Delete a glossary.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function deleteGlossary(string $glossaryId): bool
    {
        $this->client->delete("glossaries/{$glossaryId}");

        return true;
    }

    /**
     * List all glossaries.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function listGlossaries(): mixed
    {
        $response = $this->client->get('glossaries');

        return json_decode($response->getBody(), true);
    }

    /**
     * List all glossaries.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function listLanguagePairs(): mixed
    {
        $response = $this->client->get('glossary-language-pairs');

        return json_decode($response->getBody(), true);
    }
}
