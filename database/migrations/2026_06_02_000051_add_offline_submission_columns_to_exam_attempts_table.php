<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_attempts', 'local_finished_at')) {
                $table->dateTime('local_finished_at')->nullable()->after('last_synced_at')->index();
            }

            if (! Schema::hasColumn('exam_attempts', 'upload_received_at')) {
                $table->dateTime('upload_received_at')->nullable()->after('local_finished_at')->index();
            }

            if (! Schema::hasColumn('exam_attempts', 'submission_checksum')) {
                $table->string('submission_checksum', 128)->nullable()->after('cached_payload_hash')->index();
            }

            if (! Schema::hasColumn('exam_attempts', 'idempotency_key')) {
                $table->string('idempotency_key', 120)->nullable()->after('submission_checksum')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            foreach ([
                'idempotency_key',
                'submission_checksum',
                'upload_received_at',
                'local_finished_at',
            ] as $column) {
                if (Schema::hasColumn('exam_attempts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
