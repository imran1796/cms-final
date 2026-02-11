<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('revisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('space_id')->nullable()->index();
            $table->unsignedBigInteger('entry_id')->index();
            $table->json('snapshot');
            $table->json('diff')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            // FKs (safe)
            $table->foreign('entry_id')->references('id')->on('entries')->onDelete('cascade');
            $table->foreign('space_id')->references('id')->on('spaces')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisions');
    }
};
