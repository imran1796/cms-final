<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->index(['status', 'published_at', 'id'], 'entries_status_published_at_id_idx');
            $table->index(['status', 'unpublish_at', 'id'], 'entries_status_unpublish_at_id_idx');
            $table->index(['space_id', 'collection_id', 'status', 'published_at', 'id'], 'entries_space_collection_status_published_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->dropIndex('entries_status_published_at_id_idx');
            $table->dropIndex('entries_status_unpublish_at_id_idx');
            $table->dropIndex('entries_space_collection_status_published_id_idx');
        });
    }
};
