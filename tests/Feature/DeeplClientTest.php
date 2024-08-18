<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use PavelZanek\LaravelDeepl\DeeplClient;
use PavelZanek\LaravelDeepl\Enums\V2\Formality;
use PavelZanek\LaravelDeepl\Enums\V2\SourceLanguage;
use PavelZanek\LaravelDeepl\Enums\V2\TargetLanguage;
use PavelZanek\LaravelDeepl\Facades\Deepl;

//function createDeeplClientWithMockedResponse(array $mockedResponses): DeeplClient
//{
//    $mock = new MockHandler($mockedResponses);
//    $handlerStack = HandlerStack::create($mock);
//    $client = new Client(['handler' => $handlerStack]);
//
//    $deeplClient = new DeeplClient;
//    $deeplClient->setClient($client);
//
//    return $deeplClient;
//}

it('can translate text using Deepl Client with all parameters', function () {
    $deeplClient = createDeeplClientWithMockedResponse([
        new Response(200, [], json_encode(['translations' => [['text' => 'Hallo']]])),
    ]);

    $result = $deeplClient->textTranslation('Hello')
        ->sourceLang(SourceLanguage::ENGLISH->value)
        ->targetLang(TargetLanguage::GERMAN->value)
        ->formality(Formality::PREFER_LESS->value)
        ->preserveFormatting()
        ->splitSentences()
        ->context('Greeting')
        ->glossary('example-glossary-id')
        ->withoutCache()
        ->getTranslation();

    expect($result)->toBe('Hallo');
});

it('can translate text using Deepl Facade with all parameters', function () {
    Deepl::shouldReceive('textTranslation')->once()->with('Hello')->andReturnSelf();
    Deepl::shouldReceive('sourceLang')->once()->with(SourceLanguage::ENGLISH->value)->andReturnSelf();
    Deepl::shouldReceive('targetLang')->once()->with(TargetLanguage::GERMAN->value)->andReturnSelf();
    Deepl::shouldReceive('formality')->once()->with(Formality::PREFER_LESS->value)->andReturnSelf();
    Deepl::shouldReceive('preserveFormatting')->once()->andReturnSelf();
    Deepl::shouldReceive('splitSentences')->once()->andReturnSelf();
    Deepl::shouldReceive('context')->once()->with('Greeting')->andReturnSelf();
    Deepl::shouldReceive('glossary')->once()->with('example-glossary-id')->andReturnSelf();
    Deepl::shouldReceive('getTranslation')->once()->andReturn('Hallo');

    $result = Deepl::textTranslation('Hello')
        ->sourceLang(SourceLanguage::ENGLISH->value)
        ->targetLang(TargetLanguage::GERMAN->value)
        ->formality(Formality::PREFER_LESS->value)
        ->preserveFormatting()
        ->splitSentences()
        ->context('Greeting')
        ->glossary('example-glossary-id')
        ->getTranslation();

    expect($result)->toBe('Hallo');
});

it('throws an InvalidArgumentException for unsupported API version', function () {
    Config::set('laravel-deepl.api_version', 'v999');

    expect(fn () => new DeeplClient)
        ->toThrow(InvalidArgumentException::class, 'Unsupported API version: v999');
})->skip('Skipping due to issues with exception handling in the test framework');

it('throws an InvalidArgumentException for unsupported API version - Facade', function () {
    Config::set('laravel-deepl.api_version', 'v999');

    expect(fn () => Deepl::textTranslation('Hello')->targetLang(TargetLanguage::GERMAN->value)->getTranslation())
        ->toThrow(InvalidArgumentException::class, 'Unsupported API version: v999');
})->skip('Skipping due to issues with exception handling in the test framework');

it('can retrieve source languages using Deepl Client', function () {
    $deeplClient = createDeeplClientWithMockedResponse([
        new Response(200, [], json_encode([['language' => 'EN'], ['language' => 'DE'], ['language' => 'FR']])),
    ]);

    $sourceLanguages = $deeplClient->languages()->getSourceLanguages();

    expect($sourceLanguages)->toBe([['language' => 'EN'], ['language' => 'DE'], ['language' => 'FR']]);
});

it('can retrieve source languages using Deepl Facade', function () {
    Deepl::shouldReceive('languages')->once()->andReturnSelf();
    Deepl::shouldReceive('getSourceLanguages')->once()->andReturn(['EN', 'DE', 'FR']);

    $sourceLanguages = Deepl::languages()->getSourceLanguages();

    expect($sourceLanguages)->toBe(['EN', 'DE', 'FR']);
});

it('can retrieve usage data using Deepl Client', function () {
    $deeplClient = createDeeplClientWithMockedResponse([
        new Response(200, [], json_encode(['character_count' => 12345, 'character_limit' => 500000])),
    ]);

    $usage = $deeplClient->usage()->getUsage();

    expect($usage)->toMatchArray([
        'character_count' => 12345,
        'character_limit' => 500000,
    ]);
});

it('can retrieve usage data using Deepl Facade', function () {
    Deepl::shouldReceive('usage')->once()->andReturnSelf();
    Deepl::shouldReceive('getUsage')->once()->andReturn([
        'character_count' => 12345,
        'character_limit' => 500000,
    ]);

    $usage = Deepl::usage()->getUsage();

    expect($usage)->toMatchArray([
        'character_count' => 12345,
        'character_limit' => 500000,
    ]);
});

it('can create a glossary using Deepl Client', function () {
    $deeplClient = createDeeplClientWithMockedResponse([
        new Response(200, [], json_encode([
            'glossary_id' => 'example-glossary-id',
            'name' => 'My Glossary',
            'source_lang' => SourceLanguage::ENGLISH->value,
            'target_lang' => TargetLanguage::GERMAN->value,
        ])),
    ]);

    $glossary = $deeplClient->glossary()->createGlossary(
        'My Glossary',
        SourceLanguage::ENGLISH->value,
        TargetLanguage::GERMAN->value,
        ['hello' => 'hallo', 'world' => 'welt']
    );

    expect($glossary)->toMatchArray([
        'glossary_id' => 'example-glossary-id',
        'name' => 'My Glossary',
    ]);
});

it('can retrieve a glossary using Deepl Client', function () {
    $deeplClient = createDeeplClientWithMockedResponse([
        new Response(200, [], json_encode([
            'glossary_id' => 'example-glossary-id',
            'name' => 'My Glossary',
            'source_lang' => SourceLanguage::ENGLISH->value,
            'target_lang' => TargetLanguage::GERMAN->value,
            'creation_time' => '2022-01-01T12:00:00Z',
            'entry_count' => 2,
        ])),
    ]);

    $glossary = $deeplClient->glossary()->getGlossary('example-glossary-id');

    expect($glossary)->toMatchArray([
        'glossary_id' => 'example-glossary-id',
        'name' => 'My Glossary',
    ]);
});

it('can delete a glossary using Deepl Client', function () {
    $deeplClient = createDeeplClientWithMockedResponse([new Response(204)]);

    $deleted = $deeplClient->glossary()->deleteGlossary('example-glossary-id');

    expect($deleted)->toBeTrue();
});

it('can list glossaries using Deepl Client', function () {
    $deeplClient = createDeeplClientWithMockedResponse([
        new Response(200, [], json_encode(['glossaries' => [
            ['glossary_id' => 'glossary-1', 'name' => 'Glossary 1'],
            ['glossary_id' => 'glossary-2', 'name' => 'Glossary 2'],
        ]])),
    ]);

    $glossaries = $deeplClient->glossary()->listGlossaries();

    expect($glossaries['glossaries'])->toBeArray()->toHaveCount(2)
        ->and($glossaries['glossaries'][0]['glossary_id'])->toBe('glossary-1')
        ->and($glossaries['glossaries'][1]['glossary_id'])->toBe('glossary-2');
});

it('can list language pairs supported by glossaries using Deepl Client', function () {
    $deeplClient = createDeeplClientWithMockedResponse([
        new Response(200, [], json_encode(['supported_languages' => [
            ['source_lang' => 'de', 'target_lang' => 'en'],
            ['source_lang' => 'de', 'target_lang' => 'es'],
        ]])),
    ]);

    $languagePairs = $deeplClient->glossary()->listLanguagePairs();

    expect($languagePairs['supported_languages'])->toBeArray()->toHaveCount(2)
        ->and($languagePairs['supported_languages'][0]['target_lang'])->toBe('en')
        ->and($languagePairs['supported_languages'][1]['target_lang'])->toBe('es');
});

it('can upload a document for translation using Deepl Client', function () {
    // Create a temporary file to act as the document
    $tempFile = tempnam(sys_get_temp_dir(), 'test_document');
    file_put_contents($tempFile, 'This is a test document content.');

    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'document_id' => 'example-document-id',
            'document_key' => 'example-document-key',
        ])),
    ]);

    $deeplClient = createDeeplClientWithMockedResponse([$mock]);
    $documentClient = $deeplClient->documentTranslation();

    $uploadResponse = $documentClient->uploadDocument(
        $tempFile,
        TargetLanguage::GERMAN->value,
        SourceLanguage::ENGLISH->value
    );

    expect($uploadResponse)->toMatchArray([
        'document_id' => 'example-document-id',
        'document_key' => 'example-document-key',
    ]);

    // Clean up the temporary file
    File::delete($tempFile);
});

it('can check the status of a document translation using Deepl Client', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'status' => 'done',
            'billed_characters' => 1234,
        ])),
    ]);

    $deeplClient = createDeeplClientWithMockedResponse([$mock]);
    $documentClient = $deeplClient->documentTranslation();

    $status = $documentClient->getDocumentStatus('example-document-id', 'example-document-key');

    expect($status)->toMatchArray([
        'status' => 'done',
        'billed_characters' => 1234,
    ]);
});

it('can download a translated document using Deepl Client', function () {
    $mock = new MockHandler([
        new Response(200, [], 'Translated document content'),
    ]);

    $deeplClient = createDeeplClientWithMockedResponse([$mock]);
    $documentClient = $deeplClient->documentTranslation();

    $translatedContent = $documentClient->downloadTranslatedDocument('example-document-id', 'example-document-key');

    // Check the actual content directly
    $content = (string) $translatedContent;
    expect($content)->toBe('Translated document content');

    File::delete('example-document-id_translated.');
});
