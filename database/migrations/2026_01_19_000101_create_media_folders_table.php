<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('media_folders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('space_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('path'); // virtual full path like "/marketing/banners"
            $table->timestamps();

            $table->index(['space_id', 'parent_id']);
            $table->index(['space_id', 'path']);
            $table->unique(['space_id', 'path']);

            $table->foreign('space_id')->references('id')->on('spaces')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('media_folders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_folders');
    }
};
