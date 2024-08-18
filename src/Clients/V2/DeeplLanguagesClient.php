<?php

namespace PavelZanek\LaravelDeepl\Clients\V2;

use GuzzleHttp\Client;

class DeeplLanguagesClient
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get the list of available source languages.
     *
     * @return mixed List of source languages
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSourceLanguages(): mixed
    {
        $response = $this->client->get('languages', [
            'query' => ['type' => 'source'],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get the list of available target languages.
     *
     * @return mixed List of target languages
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTargetLanguages(): mixed
    {
        $response = $this->client->get('languages', [
            'query' => ['type' => 'target'],
        ]);

        return json_decode($response->getBody(), true);
    }
}
