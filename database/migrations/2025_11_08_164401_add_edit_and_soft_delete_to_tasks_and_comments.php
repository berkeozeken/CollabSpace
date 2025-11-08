<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tasks tablosu varsa uygula
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (!Schema::hasColumn('tasks', 'edited_at')) {
                    $table->timestamp('edited_at')->nullable()->after('updated_at');
                }
                if (!Schema::hasColumn('tasks', 'updated_by')) {
                    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->after('edited_at');
                }
                if (!Schema::hasColumn('tasks', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        // Task comments tablosu varsa uygula
        if (Schema::hasTable('task_comments')) {
            Schema::table('task_comments', function (Blueprint $table) {
                if (!Schema::hasColumn('task_comments', 'edited_at')) {
                    $table->timestamp('edited_at')->nullable()->after('updated_at');
                }
                if (!Schema::hasColumn('task_comments', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (Schema::hasColumn('tasks', 'edited_at')) {
                    $table->dropColumn('edited_at');
                }
                if (Schema::hasColumn('tasks', 'updated_by')) {
                    $table->dropForeign(['updated_by']);
                    $table->dropColumn('updated_by');
                }
                if (Schema::hasColumn('tasks', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }

        if (Schema::hasTable('task_comments')) {
            Schema::table('task_comments', function (Blueprint $table) {
                if (Schema::hasColumn('task_comments', 'edited_at')) {
                    $table->dropColumn('edited_at');
                }
                if (Schema::hasColumn('task_comments', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
    }
};
