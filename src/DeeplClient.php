<?php

namespace PavelZanek\LaravelDeepl;

use InvalidArgumentException;
use PavelZanek\LaravelDeepl\Clients\DeeplClientV2;
use PavelZanek\LaravelDeepl\Contracts\V2\DeeplClientInterface;

/**
 * @method \PavelZanek\LaravelDeepl\Clients\V2\DeeplTextTranslationClient textTranslation(string $text = '')
 * @method \PavelZanek\LaravelDeepl\Clients\V2\DeeplDocumentTranslationClient documentTranslation()
 * @method \PavelZanek\LaravelDeepl\Clients\V2\DeeplGlossaryClient glossary()
 * @method \PavelZanek\LaravelDeepl\Clients\V2\DeeplLanguagesClient languages()
 * @method \PavelZanek\LaravelDeepl\Clients\V2\DeeplUsageClient usage()
 */
class DeeplClient
{
    protected DeeplClientInterface $client;

    protected bool $useCache;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [])
    {
        /** @var array<string, mixed> $config */
        $config = $config ?: config('laravel-deepl');
        $this->client = $this->createClient($config);
        $this->useCache = (bool) $config['enable_translation_cache'];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function createClient(array $config): DeeplClientInterface
    {
        /** @var string $apiVersion */
        $apiVersion = $config['api_version'];

        return match ($apiVersion) {
            'v2' => new DeeplClientV2,
            default => throw new InvalidArgumentException("Unsupported API version: {$apiVersion}"),
        };
    }

    /**
     * Dynamically handle calls to the client methods.
     *
     * @param  string  $method  The name of the method being called.
     * @param  array<array-key, mixed>  $arguments  The arguments passed to the method.
     * @return mixed The result of the called method on the client.
     */
    public function __call(string $method, array $arguments): mixed
    {
        $clientMethod = $this->client->$method(...$arguments);

        return method_exists($clientMethod, 'withoutCache') && !$this->useCache
            ? $clientMethod->withoutCache()
            : $clientMethod;
    }
}
