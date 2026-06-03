<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_participants', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_participants', 'package_queue_joined_at')) {
                $table->dateTime('package_queue_joined_at')->nullable()->after('meta')->index();
            }

            if (! Schema::hasColumn('exam_participants', 'package_queue_started_at')) {
                $table->dateTime('package_queue_started_at')->nullable()->after('package_queue_joined_at')->index();
            }

            if (! Schema::hasColumn('exam_participants', 'package_queue_expires_at')) {
                $table->dateTime('package_queue_expires_at')->nullable()->after('package_queue_started_at')->index();
            }

            if (! Schema::hasColumn('exam_participants', 'package_queue_token')) {
                $table->string('package_queue_token', 120)->nullable()->after('package_queue_expires_at')->index();
            }

            if (! Schema::hasColumn('exam_participants', 'package_download_started_at')) {
                $table->dateTime('package_download_started_at')->nullable()->after('package_queue_token')->index();
            }

            if (! Schema::hasColumn('exam_participants', 'package_download_finished_at')) {
                $table->dateTime('package_download_finished_at')->nullable()->after('package_download_started_at')->index();
            }

            if (! Schema::hasColumn('exam_participants', 'package_download_attempts_count')) {
                $table->unsignedInteger('package_download_attempts_count')->default(0)->after('package_download_finished_at');
            }

            if (! Schema::hasColumn('exam_participants', 'package_unlock_key_issued_at')) {
                $table->dateTime('package_unlock_key_issued_at')->nullable()->after('package_download_attempts_count')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_participants', function (Blueprint $table) {
            foreach ([
                'package_unlock_key_issued_at',
                'package_download_attempts_count',
                'package_download_finished_at',
                'package_download_started_at',
                'package_queue_token',
                'package_queue_expires_at',
                'package_queue_started_at',
                'package_queue_joined_at',
            ] as $column) {
                if (Schema::hasColumn('exam_participants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
