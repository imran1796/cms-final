<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->timestamp('unpublish_at')->nullable()->after('published_at');
            $table->index(['space_id', 'unpublish_at']);
        });
    }

    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->dropIndex(['space_id', 'unpublish_at']);
            $table->dropColumn('unpublish_at');
        });
    }
};
