<?php

use DeepL\TextResult;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use PavelZanek\LaravelDeepl\DeeplClient;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\InvalidOptionException;

it('can translate all language files in a folder using TranslateFolderCommand', function () {
    // Set up a mock DeeplClient
    $deeplClientMock = Mockery::mock(DeeplClient::class);
    $deeplClientMock->shouldReceive('translateText')
        ->andReturn(new TextResult(
            text: 'Translated text',
            detectedSourceLang: 'en',
            billedCharacters: 10,
        ));

    $this->app->instance(DeeplClient::class, $deeplClientMock);

    // Ensure the source directory exists
    $sourceLangDir = lang_path('en');
    if (! File::exists($sourceLangDir)) {
        File::makeDirectory($sourceLangDir, 0755, true);
    }

    // Create a temporary PHP language file
    $tempPhpFilePath = $sourceLangDir.'/test.php';
    File::put($tempPhpFilePath, "<?php\n\nreturn ['key' => 'Original text'];");

    // Create a temporary JSON language file
    $tempJsonFilePath = $sourceLangDir.'/test.json';
    File::put($tempJsonFilePath, json_encode(['key' => 'Original text'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    // Create a subdirectory with another language file
    $subDir = $sourceLangDir.'/sub';
    if (! File::exists($subDir)) {
        File::makeDirectory($subDir, 0755, true);
    }
    $tempSubFilePath = $subDir.'/sub_test.php';
    File::put($tempSubFilePath, "<?php\n\nreturn ['sub_key' => 'Original sub text'];");

    // Run the command and check the return value
    $exitCode = Artisan::call('deepl:translate-folder', [
        'folder' => $sourceLangDir,
        '--sourceLang' => 'en',
        '--targetLang' => 'cs',
    ]);
    expect($exitCode)->toBe(Command::SUCCESS);

    // Verify the translated files were created
    $targetLangDir = lang_path('cs');

    // Check PHP file
    $translatedPhpFilePath = $targetLangDir.'/test.php';
    expect(File::exists($translatedPhpFilePath))->toBeTrue();
    $translatedPhpContent = include $translatedPhpFilePath;
    expect($translatedPhpContent)->toBe(['key' => 'Translated text']);

    // Check JSON file
    $translatedJsonFilePath = $targetLangDir.'/test.json';
    expect(File::exists($translatedJsonFilePath))->toBeTrue();
    $translatedJsonContent = json_decode(File::get($translatedJsonFilePath), true);
    expect($translatedJsonContent)->toBe(['key' => 'Translated text']);

    // Check subdirectory file
    $translatedSubFilePath = $targetLangDir.'/sub/sub_test.php';
    expect(File::exists($translatedSubFilePath))->toBeTrue();
    $translatedSubContent = include $translatedSubFilePath;
    expect($translatedSubContent)->toBe(['sub_key' => 'Translated text']);

    // Clean up
    File::delete($tempPhpFilePath);
    File::delete($tempJsonFilePath);
    File::delete($tempSubFilePath);
    File::deleteDirectory($sourceLangDir);
    File::deleteDirectory($targetLangDir);
});

it('fails if the folder does not exist', function () {
    // Run the command with a non-existing folder and check the return value
    $exitCode = Artisan::call('deepl:translate-folder', [
        'folder' => lang_path('non_existent'),
        '--sourceLang' => 'en',
        '--targetLang' => 'cs',
    ]);
    expect($exitCode)->toBe(Command::FAILURE);
});

it('fails if folderPath, sourceLang, or targetLang is not a string', function () {
    // Set up an array of invalid inputs to test
    $invalidInputs = [
        ['folder' => null, '--sourceLang' => 'en', '--targetLang' => 'cs'],
        ['folder' => lang_path('en'), '--sourceLang' => null, '--targetLang' => 'cs'],
        ['folder' => lang_path('en'), '--sourceLang' => 'en', '--targetLang' => null],
        ['folder' => 123, '--sourceLang' => 'en', '--targetLang' => 'cs'], // folderPath is not a string
        ['folder' => lang_path('en'), '--sourceLang' => 123, '--targetLang' => 'cs'], // sourceLang is not a string
        ['folder' => lang_path('en'), '--sourceLang' => 'en', '--targetLang' => 123], // targetLang is not a string
    ];

    foreach ($invalidInputs as $input) {
        // Run the command with invalid inputs and check the return value
        $exitCode = Artisan::call('deepl:translate-folder', $input);
        expect($exitCode)->toBe(Command::FAILURE);
    }
});

it('fails if unrecognized arguments are provided', function () {
    $this->expectException(InvalidArgumentException::class);

    // Attempt to run the command with an unrecognized argument
    Artisan::call('deepl:translate-folder', [
        'folder' => lang_path('en'),
        '--sourceLang' => 'en',
        '--targetLang' => 'cs',
        'extraArgument' => 'unexpected',  // Unrecognized argument
    ]);
});

it('fails if unrecognized options are provided', function () {
    $this->expectException(InvalidOptionException::class);

    // Attempt to run the command with an unrecognized option
    Artisan::call('deepl:translate-folder', [
        'folder' => lang_path('en'),
        '--sourceLang' => 'en',
        '--targetLang' => 'cs',
        '--extraOption' => 'unexpected',  // Unrecognized option
    ]);
});

it('can run with --with-pint option in local environment', function () {
    // Mock the environment to 'local'
    $this->app['env'] = 'local';

    // Mock the DeeplClient
    $deeplClientMock = Mockery::mock(DeeplClient::class);
    $deeplClientMock->shouldReceive('translateText')
        ->andReturn(new TextResult(
            text: 'Translated text',
            detectedSourceLang: 'en',
            billedCharacters: 10,
        ));

    $this->app->instance(DeeplClient::class, $deeplClientMock);

    // Mock the Process class to simulate Pint execution
    $processMock = Mockery::mock('overload:'.Symfony\Component\Process\Process::class);
    $processMock->shouldReceive('setTimeout')->andReturnNull();
    $processMock->shouldReceive('run')->andReturnNull();
    $processMock->shouldReceive('isSuccessful')->andReturnTrue();
    $processMock->shouldReceive('getOutput')->andReturn('Pint output');

    // Ensure the source directory exists
    $sourceLangDir = lang_path('en');
    if (! File::exists($sourceLangDir)) {
        File::makeDirectory($sourceLangDir, 0755, true);
    }

    // Create a temporary PHP language file
    $tempPhpFilePath = $sourceLangDir.'/test.php';
    File::put($tempPhpFilePath, "<?php\n\nreturn ['key' => 'Original text'];");

    // Run the command with --with-pint option
    $exitCode = Artisan::call('deepl:translate-folder', [
        'folder' => $sourceLangDir,
        '--sourceLang' => 'en',
        '--targetLang' => 'cs',
        '--with-pint' => true,
    ]);
    expect($exitCode)->toBe(Command::SUCCESS);

    // Clean up
    File::delete($tempPhpFilePath);
    File::deleteDirectory($sourceLangDir);
});
