<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            // Takıma ve oluşturan kişiye bağla
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');

            // Atanan kişi (opsiyonel)
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            // Kanban durumu
            $table->string('status')->default('todo'); // todo | in_progress | done

            // Sıralama ve planlama alanları
            $table->unsignedInteger('position')->default(0);
            $table->dateTime('due_at')->nullable();

            // Sprint 4 gereksinimleri
            $table->timestamp('edited_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Performans indeksleri
            $table->index(['team_id', 'status', 'position']);
            $table->index(['assignee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
