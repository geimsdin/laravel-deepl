<?php

namespace PavelZanek\LaravelDeepl\Clients;

use GuzzleHttp\Client;
use PavelZanek\LaravelDeepl\Clients\V2\DeeplDocumentTranslationClient;
use PavelZanek\LaravelDeepl\Clients\V2\DeeplGlossaryClient;
use PavelZanek\LaravelDeepl\Clients\V2\DeeplLanguagesClient;
use PavelZanek\LaravelDeepl\Clients\V2\DeeplTextTranslationClient;
use PavelZanek\LaravelDeepl\Clients\V2\DeeplUsageClient;
use PavelZanek\LaravelDeepl\Contracts\V2\DeeplClientInterface;

class DeeplClientV2 implements DeeplClientInterface
{
    protected Client $client;

    public function __construct()
    {
        /** @var array<string, mixed> $config */
        $config = config('laravel-deepl');
        $baseUri = $config['api_type'] === 'pro'
            ? 'https://api.deepl.com/'
            : 'https://api-free.deepl.com/';

        $this->client = new Client([
            'base_uri' => $baseUri.'v2/',
            'headers' => [
                'Authorization' => 'DeepL-Auth-Key '.$config['api_key'],
            ],
            'timeout' => $config['timeout'],
            'retry' => $config['retry_on_failures'],
        ]);
    }

    public function textTranslation(string $text): DeeplTextTranslationClient
    {
        return new DeeplTextTranslationClient($this->client, $text);
    }

    public function documentTranslation(): DeeplDocumentTranslationClient
    {
        return new DeeplDocumentTranslationClient($this->client);
    }

    public function glossary(): DeeplGlossaryClient
    {
        return new DeeplGlossaryClient($this->client);
    }

    public function languages(): DeeplLanguagesClient
    {
        return new DeeplLanguagesClient($this->client);
    }

    public function usage(): DeeplUsageClient
    {
        return new DeeplUsageClient($this->client);
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }
}
