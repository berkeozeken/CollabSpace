<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Eski isimler varsa yeniye çevir
            if (Schema::hasColumn('tasks', 'assigned_to') && !Schema::hasColumn('tasks', 'assignee_id')) {
                $table->renameColumn('assigned_to', 'assignee_id');
            }
            if (Schema::hasColumn('tasks', 'created_by') && !Schema::hasColumn('tasks', 'creator_id')) {
                $table->renameColumn('created_by', 'creator_id');
            }

            // Planlama alanları (yoksa ekle)
            if (!Schema::hasColumn('tasks', 'position')) {
                $table->unsignedInteger('position')->default(0)->after('status');
            }
            if (!Schema::hasColumn('tasks', 'due_at')) {
                $table->timestamp('due_at')->nullable()->after('position');
            }
            if (!Schema::hasColumn('tasks', 'edited_at')) {
                $table->timestamp('edited_at')->nullable()->after('due_at');
            }
            if (!Schema::hasColumn('tasks', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'assignee_id') && !Schema::hasColumn('tasks', 'assigned_to')) {
                $table->renameColumn('assignee_id', 'assigned_to');
            }
            if (Schema::hasColumn('tasks', 'creator_id') && !Schema::hasColumn('tasks', 'created_by')) {
                $table->renameColumn('creator_id', 'created_by');
            }
            if (Schema::hasColumn('tasks', 'updated_by')) {
                $table->dropConstrainedForeignId('updated_by');
            }
            if (Schema::hasColumn('tasks', 'edited_at')) $table->dropColumn('edited_at');
            if (Schema::hasColumn('tasks', 'due_at'))   $table->dropColumn('due_at');
            if (Schema::hasColumn('tasks', 'position')) $table->dropColumn('position');
        });
    }
};
