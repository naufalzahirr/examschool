<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        if (Schema::hasTable('school_settings')) {
            DB::table('school_settings')->updateOrInsert(
                ['key' => 'default_exam_exit_policy'],
                [
                    'value' => 'after_submit',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            DB::table('school_settings')->where('key', 'offline_exit_code_length')->delete();
        }

        if (! Schema::hasTable('exams')) {
            return;
        }

        $updates = [
            'exit_policy' => 'after_submit',
            'updated_at' => $now,
        ];

        $legacyCodeColumns = [
            'offline_exit_code_salt',
            'offline_exit_code_hash',
            'offline_exit_code_encrypted',
            'offline_exit_code_generated_at',
        ];

        foreach ($legacyCodeColumns as $column) {
            if (Schema::hasColumn('exams', $column)) {
                $updates[$column] = null;
            }
        }

        $query = DB::table('exams')->where('exit_policy', 'proctor_code');
        foreach ($legacyCodeColumns as $column) {
            if (Schema::hasColumn('exams', $column)) {
                $query->orWhereNotNull($column);
            }
        }

        $query->update($updates);
    }

    public function down(): void
    {
        if (Schema::hasTable('school_settings')) {
            DB::table('school_settings')->updateOrInsert(
                ['key' => 'default_exam_exit_policy'],
                [
                    'value' => 'after_submit',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
};
