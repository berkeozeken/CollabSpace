<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('message_reads', function (Blueprint $table) {
            $table->id();

            $table->foreignId('message_id')
                  ->constrained('messages')
                  ->onDelete('cascade');

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->timestamp('read_at');

            // Bir kullanıcı bir mesajı bir kez okumuş sayılır
            $table->unique(['message_id', 'user_id'], 'message_reads_message_user_unique');

            // Sorgu performansı için
            $table->index(['user_id', 'read_at'], 'message_reads_user_read_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reads');
    }
};
