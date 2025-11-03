<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_user', function (Blueprint $table) {
            // PostgreSQL için sade tutalım: string + default
            if (! Schema::hasColumn('team_user', 'role')) {
                $table->string('role', 20)->default('member')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('team_user', function (Blueprint $table) {
            if (Schema::hasColumn('team_user', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};
