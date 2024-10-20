<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(config('laravel-deepl.translation_cache_table', 'translations_cache'), function (Blueprint $table) {
            $table->id();
            $table->text('text');
            $table->string('text_hash', 64); // SHA256 hash length
            $table->text('translated_text');
            $table->string('source_lang', 8);
            $table->string('target_lang', 8);
            $table->json('options')->nullable();
            $table->string('options_hash', 32); // MD5 hash length
            $table->string('detected_source_lang', 8)->nullable();
            $table->integer('billed_characters')->nullable();
            $table->timestamps();

            $table->index(['text_hash', 'source_lang', 'target_lang', 'options_hash'], 'translation_lookup_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('laravel-deepl.translation_cache_table', 'translations_cache'));
    }
};
