<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // PostgreSQL 'after()' desteklemez; basitçe ekleyelim.
            if (! Schema::hasColumn('teams', 'name')) {
                $table->string('name')->default(''); // boş tabloysa güvenli
            }
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            if (Schema::hasColumn('teams', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};
