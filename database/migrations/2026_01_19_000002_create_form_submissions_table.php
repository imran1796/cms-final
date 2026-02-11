<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('space_id');
            $table->unsignedBigInteger('form_id');
            $table->string('status')->default('new'); // new/processed/spam
            $table->json('data');
            $table->json('meta')->nullable(); // ip, user_agent, headers subset
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['space_id', 'form_id', 'created_at']);
            $table->index(['space_id', 'status']);

            $table->foreign('space_id')->references('id')->on('spaces')->onDelete('cascade');
            $table->foreign('form_id')->references('id')->on('forms')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
