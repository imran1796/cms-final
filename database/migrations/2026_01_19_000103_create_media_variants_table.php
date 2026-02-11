<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('media_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('media_id');

            $table->string('preset_key')->nullable();
            $table->string('transform_key'); // stable hash key (preset or query)
            $table->json('transform')->nullable();

            $table->string('disk')->default('local');
            $table->string('path'); // disk path to variant
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['media_id', 'transform_key']);
            $table->index(['media_id', 'preset_key']);

            $table->foreign('media_id')->references('id')->on('media')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_variants');
    }
};
