<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['exam_id', 'classroom_id']);
            $table->index('classroom_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_classrooms');
    }
};
