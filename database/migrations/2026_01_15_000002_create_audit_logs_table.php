<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('space_id')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();

            $table->string('action', 120);
            $table->string('resource', 200);

            $table->json('diff')->nullable();

            $table->timestamps();

            // FK (safe, optional records)
            $table->foreign('space_id')->references('id')->on('spaces')->nullOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();

            // Indexes for timelines
            $table->index(['space_id', 'created_at']);
            $table->index(['actor_id', 'created_at']);
            $table->index(['action']);
            $table->index(['resource']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
