<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // PostgreSQL'de unsigned yok; foreignId yeterli. AFTER kullanmıyoruz.
            if (! Schema::hasColumn('teams', 'owner_id')) {
                $table->foreignId('owner_id')
                      ->constrained('users')
                      ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            if (Schema::hasColumn('teams', 'owner_id')) {
                // Önce FK'yi düşürelim (Laravel otomatik isim veriyor; dropConstrainedForeignId kullanmak güvenli)
                $table->dropConstrainedForeignId('owner_id');
            }
        });
    }
};
