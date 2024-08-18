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
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->text('text');
            $table->text('translated_text');
            $table->string('source_lang');
            $table->string('target_lang');
            $table->json('options')->nullable();
            $table->timestamps();

            $table->unique(['text', 'source_lang', 'target_lang', 'options'], 'unique_translation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
