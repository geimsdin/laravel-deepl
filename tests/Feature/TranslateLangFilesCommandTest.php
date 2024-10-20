<?php

use DeepL\TextResult;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use PavelZanek\LaravelDeepl\DeeplClient;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\InvalidOptionException;

it('can translate a PHP language file using TranslateLangFilesCommand', function () {
    // Set up a mock DeeplClient
    $deeplClientMock = Mockery::mock(DeeplClient::class);
    $deeplClientMock->shouldReceive('translateText')
        ->andReturn(new TextResult(
            text: 'Translated text',
            detectedSourceLang: 'en',
            billedCharacters: 10,
        ));

    $this->app->instance(DeeplClient::class, $deeplClientMock);

    // Ensure the directory exists
    $langDir = lang_path('en');
    if (! File::exists($langDir)) {
        File::makeDirectory($langDir, 0755, true);
    }

    // Create a temporary PHP language file to translate
    $tempFilePath = $langDir.'/testPest.php';
    File::put($tempFilePath, "<?php\n\nreturn ['key' => 'Original text'];");

    // Run the command and check the return value
    $exitCode = Artisan::call('deepl:translate', [
        'file' => $tempFilePath,
        '--sourceLang' => 'en',
        '--targetLang' => 'cs',
    ]);
    expect($exitCode)->toBe(Command::SUCCESS);

    // Verify the translated file was created
    $translatedFilePath = lang_path('cs/testPest.php');
    expect(File::exists($translatedFilePath))->toBeTrue();

    // Verify the content of the translated file
    $translatedContent = include $translatedFilePath;
    expect($translatedContent)->toBe([
        'key' => 'Translated text',
    ]);

    // Clean up
    File::delete($tempFilePath);
    File::delete($translatedFilePath);
});

it('can translate a JSON language file using TranslateLangFilesCommand', function () {
    // Set up a mock DeeplClient
    $deeplClientMock = Mockery::mock(DeeplClient::class);
    $deeplClientMock->shouldReceive('translateText')
        ->andReturn(new TextResult(
            text: 'Translated text',
            detectedSourceLang: 'en',
            billedCharacters: 10,
        ));

    $this->app->instance(DeeplClient::class, $deeplClientMock);

    // Ensure the directory exists
    $langDir = lang_path('en');
    if (! File::exists($langDir)) {
        File::makeDirectory($langDir, 0755, true);
    }

    // Create a temporary JSON language file to translate
    $tempJsonFilePath = $langDir.'/test.json';
    File::put($tempJsonFilePath, json_encode(['key' => 'Original text'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    // Run the command and check the return value
    $exitCode = Artisan::call('deepl:translate', [
        'file' => $tempJsonFilePath,
        '--sourceLang' => 'en',
        '--targetLang' => 'cs',
    ]);
    expect($exitCode)->toBe(Command::SUCCESS);

    // Verify the translated file was created
    $translatedJsonFilePath = lang_path('cs/test.json');
    expect(File::exists($translatedJsonFilePath))->toBeTrue();

    // Verify the content of the translated file
    $translatedJsonContent = json_decode(File::get($translatedJsonFilePath), true);
    expect($translatedJsonContent)->toBe([
        'key' => 'Translated text',
    ]);

    // Clean up
    File::delete($tempJsonFilePath);
    File::delete($translatedJsonFilePath);
});

it('fails if unrecognized arguments are provided', function () {
    $this->expectException(InvalidArgumentException::class);

    // Attempt to run the command with an unrecognized argument
    Artisan::call('deepl:translate', [
        'file' => lang_path('en/test.php'),
        '--sourceLang' => 'en',
        '--targetLang' => 'cs',
        'extraArgument' => 'unexpected',  // Unrecognized argument
    ]);
});

it('fails if unrecognized options are provided', function () {
    $this->expectException(InvalidOptionException::class);

    // Attempt to run the command with an unrecognized option
    Artisan::call('deepl:translate', [
        'file' => lang_path('en/test.php'),
        '--sourceLang' => 'en',
        '--targetLang' => 'cs',
        '--extraOption' => 'unexpected',  // Unrecognized option
    ]);
});

it('fails if filePath, sourceLang, or targetLang is not a string', function () {
    // Set up an array of invalid inputs to test
    $invalidInputs = [
        ['file' => null, '--sourceLang' => 'en', '--targetLang' => 'cs'],
        ['file' => lang_path('en/test.php'), '--sourceLang' => null, '--targetLang' => 'cs'],
        ['file' => lang_path('en/test.php'), '--sourceLang' => 'en', '--targetLang' => null],
        ['file' => 123, '--sourceLang' => 'en', '--targetLang' => 'cs'], // filePath is not a string
        ['file' => lang_path('en/test.php'), '--sourceLang' => 123, '--targetLang' => 'cs'], // sourceLang is not a string
        ['file' => lang_path('en/test.php'), '--sourceLang' => 'en', '--targetLang' => 123], // targetLang is not a string
    ];

    foreach ($invalidInputs as $input) {
        // Run the command with invalid inputs and check the return value
        $exitCode = Artisan::call('deepl:translate', $input);
        expect($exitCode)->toBe(Command::FAILURE);
    }
});

it('fails if the source file does not exist', function () {
    // Run the command with a non-existing file and check the return value
    $exitCode = Artisan::call('deepl:translate', [
        'file' => lang_path('en/non_existent.php'),
        '--sourceLang' => 'en',
        '--targetLang' => 'cs',
    ]);
    expect($exitCode)->toBe(Command::FAILURE);
});

it('fails if the file path is not a string', function () {
    // Mock incorrect file path input
    $exitCode = Artisan::call('deepl:translate', [
        'file' => null,  // Not a string
        '--sourceLang' => 'en',
        '--targetLang' => 'cs',
    ]);
    expect($exitCode)->toBe(Command::FAILURE);
});
