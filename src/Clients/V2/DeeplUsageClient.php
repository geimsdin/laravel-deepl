<?php

namespace PavelZanek\LaravelDeepl\Clients\V2;

use GuzzleHttp\Client;

class DeeplUsageClient
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get the usage and quota information.
     *
     * @return mixed Usage and quota details
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getUsage(): mixed
    {
        $response = $this->client->get('usage');

        return json_decode($response->getBody(), true);
    }
}
