<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('content_tree_nodes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('space_id')->nullable();
            $table->unsignedBigInteger('collection_id');
            $table->unsignedBigInteger('entry_id');
            $table->unsignedBigInteger('parent_id')->nullable(); // self FK
            $table->integer('position')->default(0);
            $table->string('path', 255)->nullable(); // optional but recommended

            $table->timestamps();

            // Indexes
            $table->index(['space_id', 'collection_id', 'parent_id', 'position'], 'ctn_space_coll_parent_pos_idx');
            $table->unique(['space_id', 'collection_id', 'entry_id'], 'ctn_space_coll_entry_unique');
            $table->index(['space_id', 'collection_id'], 'ctn_space_coll_idx');
            $table->index(['path'], 'ctn_path_idx');

            // FKs (keep nullable space_id)
            $table->foreign('space_id')->references('id')->on('spaces')->nullOnDelete();
            $table->foreign('collection_id')->references('id')->on('collections')->cascadeOnDelete();
            $table->foreign('entry_id')->references('id')->on('entries')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('content_tree_nodes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_tree_nodes');
    }
};
