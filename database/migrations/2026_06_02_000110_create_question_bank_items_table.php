<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_bank_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('question_code', 40)->unique();
            $table->string('subject')->nullable()->index();
            $table->string('grade_level')->nullable()->index();
            $table->string('topic')->nullable()->index();
            $table->string('difficulty')->default('sedang')->index();
            $table->string('visibility', 20)->default('school')->index();
            $table->string('type')->index();
            $table->text('title');
            $table->text('description')->nullable();
            $table->decimal('points', 8, 2)->default(1);
            $table->json('options')->nullable();
            $table->json('answer_key')->nullable();
            $table->text('correct_text')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['subject', 'grade_level', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_bank_items');
    }
};
