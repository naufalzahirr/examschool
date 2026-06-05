<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_participants', function (Blueprint $table) {
            $table->unique(['exam_id', 'device_id'], 'exam_participants_exam_device_unique');
        });
    }

    public function down(): void
    {
        Schema::table('exam_participants', function (Blueprint $table) {
            $table->dropUnique('exam_participants_exam_device_unique');
        });
    }
};
