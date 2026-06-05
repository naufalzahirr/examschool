<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            // 'scheduled' = pakai jam mulai/selesai (advanced); 'manual' = buka/tutup manual (seperti Google Form)
            $table->string('schedule_mode', 20)->default('scheduled')->after('status');
            // Toggle manual (dipakai saat schedule_mode = manual)
            $table->boolean('manual_download_open')->default(false)->after('schedule_mode');
            $table->boolean('manual_exam_open')->default(false)->after('manual_download_open');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['schedule_mode', 'manual_download_open', 'manual_exam_open']);
        });
    }
};
