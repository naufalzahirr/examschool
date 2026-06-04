<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (! Schema::hasColumn('exams', 'lock_mode')) {
                $table->string('lock_mode', 40)->default('standard')->after('shuffle_options')->index();
            }

            if (! Schema::hasColumn('exams', 'exit_policy')) {
                $table->string('exit_policy', 40)->default('after_submit')->after('lock_mode')->index();
            }

            if (! Schema::hasColumn('exams', 'offline_exit_code_salt')) {
                $table->string('offline_exit_code_salt', 120)->nullable()->after('exit_policy');
            }

            if (! Schema::hasColumn('exams', 'offline_exit_code_hash')) {
                $table->string('offline_exit_code_hash', 128)->nullable()->after('offline_exit_code_salt');
            }

            if (! Schema::hasColumn('exams', 'offline_exit_code_encrypted')) {
                $table->text('offline_exit_code_encrypted')->nullable()->after('offline_exit_code_hash');
            }

            if (! Schema::hasColumn('exams', 'offline_exit_code_generated_at')) {
                $table->dateTime('offline_exit_code_generated_at')->nullable()->after('offline_exit_code_encrypted');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            foreach ([
                'offline_exit_code_generated_at',
                'offline_exit_code_encrypted',
                'offline_exit_code_hash',
                'offline_exit_code_salt',
                'exit_policy',
                'lock_mode',
            ] as $column) {
                if (Schema::hasColumn('exams', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
