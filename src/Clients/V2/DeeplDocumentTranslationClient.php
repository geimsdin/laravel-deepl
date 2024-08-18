<?php

namespace PavelZanek\LaravelDeepl\Clients\V2;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\File;
use Psr\Http\Message\StreamInterface;

class DeeplDocumentTranslationClient
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Uploads a document for translation.
     *
     * @param  array<string, string|null>  $options
     * @return mixed Document ID and key
     *
     * @throws GuzzleException
     */
    public function uploadDocument(string $filePath, string $targetLang, ?string $sourceLang = null, array $options = []): mixed
    {
        $multipart = [
            [
                'name' => 'file',
                'contents' => fopen($filePath, 'r'),
                'filename' => basename($filePath),
            ],
            [
                'name' => 'target_lang',
                'contents' => $targetLang,
            ],
        ];

        if ($sourceLang) {
            $multipart[] = [
                'name' => 'source_lang',
                'contents' => $sourceLang,
            ];
        }

        foreach ($options as $key => $value) {
            if ($value !== null) {
                $multipart[] = [
                    'name' => $key,
                    'contents' => $value,
                ];
            }
        }

        $response = $this->client->post('document', [
            'multipart' => $multipart,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Checks the status of a document translation.
     *
     * @return mixed Status information
     *
     * @throws GuzzleException
     */
    public function getDocumentStatus(string $documentId, string $documentKey): mixed
    {
        $response = $this->client->get("document/{$documentId}", [
            'query' => [
                'document_key' => $documentKey,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Downloads the translated document.
     *
     * @return StreamInterface The translated document content
     *
     * @throws GuzzleException
     */
    public function downloadTranslatedDocument(string $documentId, string $documentKey): StreamInterface
    {
        $filePath = storage_path('app/public/'.$documentId.'_translated.'.pathinfo($documentId, PATHINFO_EXTENSION));

        $response = $this->client->post("document/{$documentId}/result", [
            'query' => [
                'document_key' => $documentKey,
            ],
            'sink' => $filePath,
        ]);

        $translatedDocument = $response->getBody();

        if (File::exists($filePath)) {
            File::delete($filePath);
        }

        return $translatedDocument;
    }
}
