<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('space_id')->nullable();

            $table->string('handle', 80);
            $table->enum('type', ['collection', 'singleton', 'tree']);

            // fields: array of objects, each includes id (uuid), handle, label, type, required, options...
            $table->json('fields')->nullable();

            $table->json('settings')->nullable();

            $table->timestamps();

            $table->foreign('space_id')->references('id')->on('spaces')->nullOnDelete();
            $table->unique(['space_id', 'handle']);
            $table->index(['space_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
