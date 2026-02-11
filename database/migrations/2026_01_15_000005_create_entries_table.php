<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('entries', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('space_id')->nullable();
            $table->unsignedBigInteger('collection_id');

            $table->string('status', 30)->default('draft');
            $table->timestamp('published_at')->nullable();

            $table->json('data')->nullable();

            /**
             * MySQL: generated columns for indexing/search.
             * SQLite (tests): fallback to normal nullable columns.
             */
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                // slug generated from data.slug if exists
                $table->string('slug', 190)->nullable()
                    ->storedAs("json_unquote(json_extract(`data`, '$.slug'))");

                // title generated from data.title if exists
                $table->string('title', 190)->nullable()
                    ->storedAs("json_unquote(json_extract(`data`, '$.title'))");
            } else {
                $table->string('slug', 190)->nullable();
                $table->string('title', 190)->nullable();
            }

            $table->timestamps();

            $table->foreign('space_id')->references('id')->on('spaces')->nullOnDelete();
            $table->foreign('collection_id')->references('id')->on('collections')->cascadeOnDelete();

            $table->index(['space_id', 'collection_id']);
            $table->index(['space_id', 'status']);
            $table->index(['space_id', 'slug']);
            $table->index(['space_id', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entries');
    }
};
