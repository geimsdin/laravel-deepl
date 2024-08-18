<?php

use GuzzleHttp\Psr7\Response;
use PavelZanek\LaravelDeepl\Enums\V2\SourceLanguage;
use PavelZanek\LaravelDeepl\Enums\V2\TargetLanguage;
use PavelZanek\LaravelDeepl\Models\Translation;

it('stores translation in the database', function () {
    $deeplClient = createDeeplClientWithMockedResponse([
        new Response(200, [], json_encode(['translations' => [['text' => 'Hallo']]])),
    ]);

    expect(Translation::count())->toBe(0);

    $result = $deeplClient->textTranslation('Hello')
        ->sourceLang(SourceLanguage::ENGLISH->value)
        ->targetLang(TargetLanguage::GERMAN->value)
        ->getTranslation();

    expect($result)->toBe('Hallo')
        ->and(Translation::count())->toBe(1)
        ->and(Translation::first())->text->toBe('Hello')
        ->and(Translation::first())->translated_text->toBe('Hallo')
        ->and(Translation::first())->source_lang->toBe(SourceLanguage::ENGLISH->value)
        ->and(Translation::first())->target_lang->toBe(TargetLanguage::GERMAN->value);
});

it('retrieves translation from the database if it exists', function () {
    // Insert a translation manually into the database
    Translation::create([
        'text' => 'Hello',
        'translated_text' => 'Hallo',
        'source_lang' => $sourceLang = SourceLanguage::ENGLISH->value,
        'target_lang' => TargetLanguage::GERMAN->value,
        'options' => json_encode(['source_lang' => $sourceLang]),
    ]);

    // Ensure there is only one record
    expect(Translation::count())->toBe(1);

    // Create a mock client that should not be called
    $deeplClient = createDeeplClientWithMockedResponse([
        // We don't expect this to be called
        new Response(500),
    ]);

    // Perform translation
    $result = $deeplClient->textTranslation('Hello')
        ->sourceLang(SourceLanguage::ENGLISH->value)
        ->targetLang(TargetLanguage::GERMAN->value)
        ->getTranslation();

    // Ensure no additional records were added
    expect($result)->toBe('Hallo')
        ->and(Translation::count())->toBe(1);
});

it('bypasses cache when withoutCache is used', function () {
    // Insert a translation manually into the database
    Translation::create([
        'text' => 'Hello',
        'translated_text' => 'Hallo',
        'source_lang' => SourceLanguage::ENGLISH->value,
        'target_lang' => TargetLanguage::GERMAN->value,
        'options' => json_encode([]),
    ]);

    // Create a mock client with a different response
    $deeplClient = createDeeplClientWithMockedResponse([
        new Response(200, [], json_encode(['translations' => [['text' => 'Hallo Welt']]])),
    ]);

    // Perform translation with cache disabled
    $result = $deeplClient->textTranslation('Hello')
        ->sourceLang(SourceLanguage::ENGLISH->value)
        ->targetLang(TargetLanguage::GERMAN->value)
        ->withoutCache()
        ->getTranslation();

    // Ensure a new translation was not added to the database
    expect($result)->toBe('Hallo Welt')
        ->and(Translation::count())->toBe(1);
});
