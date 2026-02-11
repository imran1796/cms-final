<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('saved_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('space_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('resource'); // collection handle OR "assets"
            $table->string('name');
            $table->json('config'); // columns, filters, sort, limit
            $table->timestamps();

            $table->index(['space_id', 'user_id', 'resource']);
            $table->unique(['space_id', 'user_id', 'resource', 'name'], 'saved_views_unique_name');

            $table->foreign('space_id')->references('id')->on('spaces')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_views');
    }
};
