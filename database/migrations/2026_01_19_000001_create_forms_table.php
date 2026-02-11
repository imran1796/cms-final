<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('space_id');
            $table->string('handle');
            $table->string('title');
            $table->json('fields');
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['space_id', 'handle']);
            $table->index(['space_id', 'handle']);

            $table->foreign('space_id')->references('id')->on('spaces')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
