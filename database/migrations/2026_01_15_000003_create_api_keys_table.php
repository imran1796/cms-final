<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('space_id')->nullable();
            $table->string('name', 160);

            // sha256 hash of token (64 hex chars)
            $table->string('token_hash', 64)->unique();

            // scopes JSON: { collections: [], permissions: [] }
            $table->json('scopes')->nullable();

            $table->timestamps();

            $table->foreign('space_id')->references('id')->on('spaces')->nullOnDelete();
            $table->index(['space_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
