<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // 👈 eklendi

return new class extends Migration {
    public function up(): void
    {
        // Soft deletes kolonu yoksa ekle
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'deleted_at')) {
                $table->softDeletes()->index();
            }
        });

        // PostgreSQL: index varsa oluşturma, yoksa oluştur
        DB::statement('CREATE INDEX IF NOT EXISTS messages_team_id_created_at_index ON messages (team_id, created_at)');
    }

    public function down(): void
    {
        // Soft deletes kaldır
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        // PostgreSQL: index varsa drop et
        DB::statement('DROP INDEX IF EXISTS messages_team_id_created_at_index');
    }
};
