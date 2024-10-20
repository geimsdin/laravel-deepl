<?php

use DeepL\GlossaryEntries;
use DeepL\Language;
use DeepL\TextResult;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\File;
use PavelZanek\LaravelDeepl\Enums\V2\Formality;
use PavelZanek\LaravelDeepl\Enums\V2\PreserveFormatting;
use PavelZanek\LaravelDeepl\Enums\V2\SourceLanguage;
use PavelZanek\LaravelDeepl\Enums\V2\SplitSentences;
use PavelZanek\LaravelDeepl\Enums\V2\TargetLanguage;
use PavelZanek\LaravelDeepl\Facades\Deepl;

it('can translate text using Deepl Client with all parameters', function () {
    $deeplClient = createDeeplClientWithMockedResponse([
        new Response(200, [], json_encode([
            'translations' => [
                [
                    'detected_source_language' => SourceLanguage::ENGLISH->value,
                    'text' => 'Hallo',
                    'billed_characters' => 5,
                ],
            ],
        ])),
    ]);

    $result = $deeplClient->translateText()
        ->texts('Hello')
        ->sourceLang(SourceLanguage::ENGLISH->value)
        ->targetLang(TargetLanguage::GERMAN->value)
        ->formality(Formality::PREFER_LESS->value)
        ->preserveFormatting(PreserveFormatting::ENABLED->value)
        ->splitSentences('1')
        ->glossary('example-glossary-id')
        ->withoutCache()
        ->translate();

    expect($result)->toBeInstanceOf(TextResult::class)
        ->and($result->text)->toBe('Hallo')
        ->and($result->detectedSourceLang)->toBe(SourceLanguage::ENGLISH->value)
        ->and($result->billedCharacters)->toBe(5);
});

it('can translate text using Deepl Facade with all parameters', function () {
    // Create a DeeplClient with mocked responses
    $deeplClient = createDeeplClientWithMockedResponse([
        new Response(200, [], json_encode([
            'translations' => [
                [
                    'detected_source_language' => SourceLanguage::ENGLISH->value,
                    'text' => 'Hallo',
                    'billed_characters' => 5,
                ],
            ],
        ])),
    ]);

    // Bind the DeeplClient instance to 'deepl.translator' in the container
    $this->app->instance('deepl.translator', $deeplClient);

    // Now when the Deepl facade resolves 'deepl.translator', it will get $deeplClient
    $result = Deepl::translateText()
        ->texts('Hello')
        ->sourceLang(SourceLanguage::ENGLISH->value)
        ->targetLang(TargetLanguage::GERMAN->value)
        ->formality(Formality::PREFER_LESS->value)
        ->preserveFormatting(PreserveFormatting::ENABLED->value)
        ->splitSentences(SplitSentences::DEFAULT->value)
        ->glossary('example-glossary-id')
        ->withoutCache()
        ->translate();

    expect($result)
        ->toBeInstanceOf(TextResult::class)
        ->and($result->text)->toBe('Hallo')
        ->and($result->detectedSourceLang)->toBe(SourceLanguage::ENGLISH->value);
});

it('can retrieve source languages using Deepl Client', function () {
    $deeplClient = createDeeplClientWithMockedResponse([
        new Response(200, [], json_encode([
            ['language' => 'EN', 'name' => 'English'],
            ['language' => 'DE', 'name' => 'German'],
            ['language' => 'FR', 'name' => 'French'],
        ])),
    ]);

    $sourceLanguages = $deeplClient->getSourceLanguages();

    expect($sourceLanguages)->toBeArray()->toHaveCount(3)
        ->and($sourceLanguages[0]->code)->toBe('EN')
        ->and($sourceLanguages[1]->code)->toBe('DE')
        ->and($sourceLanguages[2]->code)->toBe('FR');
});

it('can retrieve source languages using Deepl Facade', function () {
    Deepl::shouldReceive('getSourceLanguages')->once()->andReturn([
        new Language(name: 'English', code: 'EN', supportsFormality: true),
        new Language(name: 'German', code: 'DE', supportsFormality: true),
        new Language(name: 'French', code: 'FR', supportsFormality: true),
    ]);

    $sourceLanguages = Deepl::getSourceLanguages();

    expect($sourceLanguages)->toBeArray()->toHaveCount(3)
        ->and($sourceLanguages[0]->code)->toBe('EN')
        ->and($sourceLanguages[1]->code)->toBe('DE')
        ->and($sourceLanguages[2]->code)->toBe('FR');
});

it('can retrieve usage data using Deepl Client', function () {
    $deeplClient = createDeeplClientWithMockedResponse([
        new Response(200, [], json_encode([
            'character_count' => 12345,
            'character_limit' => 500000,
        ])),
    ]);

    $usage = $deeplClient->getUsage();

    expect($usage)->toBeInstanceOf(\DeepL\Usage::class)
        ->and($usage->character->count)->toBe(12345)
        ->and($usage->character->limit)->toBe(500000);
});

it('can retrieve usage data including documents using Deepl Client', function () {
    $deeplClient = createDeeplClientWithMockedResponse([
        new Response(200, [], json_encode([
            'character_count' => 12345,
            'character_limit' => 500000,
            'document_count' => 10,
            'document_limit' => 100,
        ])),
    ]);

    $usage = $deeplClient->getUsage();

    expect($usage)->toBeInstanceOf(\DeepL\Usage::class)
        ->and($usage->character->count)->toBe(12345)
        ->and($usage->character->limit)->toBe(500000)
        ->and($usage->document->count)->toBe(10)
        ->and($usage->document->limit)->toBe(100);
});

it('can retrieve usage data using Deepl Facade', function () {
    // Create a mock for UsageDetail
    $usageDetailMock = Mockery::mock('DeepL\UsageDetail');
    $usageDetailMock->count = 12345;
    $usageDetailMock->limit = 500000;

    // Create a mock for Usage
    $usageMock = Mockery::mock('DeepL\Usage');
    $usageMock->character = $usageDetailMock;

    // Mock the getUsage method to return our mocked Usage object
    Deepl::shouldReceive('getUsage')->once()->andReturn($usageMock);

    $usage = Deepl::getUsage();

    expect($usage)->toBeInstanceOf(\DeepL\Usage::class)
        ->and($usage->character->count)->toBe(12345)
        ->and($usage->character->limit)->toBe(500000);
});

it('can create a glossary using Deepl Client', function () {
    $deeplClient = createDeeplClientWithMockedResponse([
        new Response(200, [], json_encode([
            'glossary_id' => 'example-glossary-id',
            'name' => 'My Glossary',
            'ready' => true,
            'source_lang' => 'EN',
            'target_lang' => 'DE',
            'creation_time' => '2022-01-01T12:00:00Z',
            'entry_count' => 2,
        ])),
    ]);

    // Create a GlossaryEntries object from your array
    $entries = GlossaryEntries::fromEntries([
        'hello' => 'hallo',
        'world' => 'welt',
    ]);

    $glossary = $deeplClient->createGlossary(
        'My Glossary',
        SourceLanguage::ENGLISH->value,
        TargetLanguage::GERMAN->value,
        $entries
    );

    expect($glossary)->toBeInstanceOf(\DeepL\GlossaryInfo::class)
        ->and($glossary->glossaryId)->toBe('example-glossary-id')
        ->and($glossary->name)->toBe('My Glossary');
});

it('can retrieve a glossary using Deepl Client', function () {
    $deeplClient = createDeeplClientWithMockedResponse([
        new Response(200, [], json_encode([
            'glossary_id' => 'example-glossary-id',
            'name' => 'My Glossary',
            'ready' => true,
            'source_lang' => 'EN',
            'target_lang' => 'DE',
            'creation_time' => '2022-01-01T12:00:00Z',
            'entry_count' => 2,
        ])),
    ]);

    $glossary = $deeplClient->getGlossary('example-glossary-id');

    expect($glossary)->toBeInstanceOf(\DeepL\GlossaryInfo::class)
        ->and($glossary->glossaryId)->toBe('example-glossary-id')
        ->and($glossary->name)->toBe('My Glossary');
});

it('can delete a glossary using Deepl Client', function () {
    $deeplClient = createDeeplClientWithMockedResponse([
        new Response(204),
    ]);

    $deeplClient->deleteGlossary('example-glossary-id');

    // If no exception is thrown, assume success
    expect(true)->toBeTrue();
});

it('can list glossaries using Deepl Client', function () {
    $deeplClient = createDeeplClientWithMockedResponse([
        new Response(200, [], json_encode([
            'glossaries' => [
                [
                    'glossary_id' => 'glossary-1',
                    'name' => 'Glossary 1',
                    'ready' => true,
                    'source_lang' => 'EN',
                    'target_lang' => 'DE',
                    'creation_time' => '2022-01-01T12:00:00Z',
                    'entry_count' => 10,
                ],
                [
                    'glossary_id' => 'glossary-2',
                    'name' => 'Glossary 2',
                    'ready' => true,
                    'source_lang' => 'EN',
                    'target_lang' => 'FR',
                    'creation_time' => '2022-01-02T12:00:00Z',
                    'entry_count' => 20,
                ],
            ],
        ])),
    ]);

    $glossaries = $deeplClient->listGlossaries();

    expect($glossaries)->toBeArray()->toHaveCount(2)
        ->and($glossaries[0]->glossaryId)->toBe('glossary-1')
        ->and($glossaries[1]->glossaryId)->toBe('glossary-2');
});

it('can upload and translate a document synchronously using Deepl Client', function () {
    // Create a temporary file to act as the document
    $tempFile = tempnam(sys_get_temp_dir(), 'test_document');
    file_put_contents($tempFile, 'This is a test document content.');

    // Create a DeeplClient with mocked responses
    $deeplClient = createDeeplClientWithMockedResponse([
        // Response for document upload
        new Response(200, [], json_encode([
            'document_id' => 'example-document-id',
            'document_key' => 'example-document-key',
        ])),
        // Response for document status (first check)
        new Response(200, [], json_encode([
            'status' => 'done',
            'seconds_remaining' => 0,
            'billed_characters' => 1234,
        ])),
        // Response for downloading the translated document
        new Response(200, [], 'Translated document content'),
    ]);

    // Perform synchronous document translation (method returns void)
    $deeplClient->translateDocument(
        $tempFile,
        $tempFile.'_translated',
        SourceLanguage::ENGLISH->value,
        TargetLanguage::GERMAN->value
    );

    // Verify that the translated file was created and contains the expected content
    expect(file_exists($tempFile.'_translated'))->toBeTrue();

    $content = file_get_contents($tempFile.'_translated');
    expect($content)->toBe('Translated document content');

    // Clean up the temporary files
    File::delete($tempFile);
    File::delete($tempFile.'_translated');
});

it('can check the status of a document translation using Deepl Client', function () {
    $deeplClient = createDeeplClientWithMockedResponse([
        new Response(200, [], json_encode([
            'status' => 'done',
            'billed_characters' => 1234,
        ])),
    ]);

    $documentHandle = new \DeepL\DocumentHandle('example-document-id', 'example-document-key');

    $status = $deeplClient->getDocumentStatus($documentHandle);

    expect($status)->toBeInstanceOf(\DeepL\DocumentStatus::class)
        ->and($status->status)->toBe('done')
        ->and($status->billedCharacters)->toBe(1234);
});

it('can download a translated document using Deepl Client', function () {
    $deeplClient = createDeeplClientWithMockedResponse([
        new Response(200, [], 'Translated document content'),
    ]);

    $documentHandle = new \DeepL\DocumentHandle('example-document-id', 'example-document-key');

    // Generate a temporary file path and delete the file
    $outputFile = tempnam(sys_get_temp_dir(), 'translated_document');
    File::delete($outputFile); // Ensure the file does not exist

    $deeplClient->downloadDocument($documentHandle, $outputFile);

    $content = file_get_contents($outputFile);

    expect($content)->toBe('Translated document content');

    // Clean up the temporary file
    File::delete($outputFile);
});
