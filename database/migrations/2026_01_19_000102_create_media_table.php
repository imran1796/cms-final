<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('space_id');
            $table->unsignedBigInteger('folder_id')->nullable();

            $table->string('disk')->default('local');
            $table->string('path');      // disk path to original file
            $table->string('filename');  // original filename
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->default(0);

            $table->string('checksum')->nullable(); // sha1
            $table->string('kind')->default('file'); // image/video/file
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration')->nullable(); // seconds for video/audio

            $table->json('meta')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['space_id', 'folder_id']);
            $table->index(['space_id', 'kind']);
            $table->index(['space_id', 'created_at']);

            $table->foreign('space_id')->references('id')->on('spaces')->onDelete('cascade');
            $table->foreign('folder_id')->references('id')->on('media_folders')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
