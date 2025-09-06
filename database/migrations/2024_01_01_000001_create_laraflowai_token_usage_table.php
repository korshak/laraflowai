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
        Schema::create('laraflowai_token_usage', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('model');
            $table->integer('prompt_tokens');
            $table->integer('completion_tokens');
            $table->integer('total_tokens');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['provider', 'model']);
            $table->index(['created_at']);
            $table->index(['provider', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laraflowai_token_usage');
    }
};
