<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('spaces', function (Blueprint $table) {
            $table->id();

            $table->string('handle', 80)->unique();
            $table->string('name', 160);

            $table->json('settings')->nullable();
            $table->string('storage_prefix', 255);

            // Recommended future-proof fields
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();

            $table->timestamps();

            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spaces');
    }
};
