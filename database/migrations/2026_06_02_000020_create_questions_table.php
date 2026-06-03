<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->string('question_code', 30)->nullable()->unique();
            $table->string('type')->default('multiple_choice');
            $table->text('title');
            $table->text('description')->nullable();
            $table->boolean('required')->default(true);
            $table->decimal('points', 8, 2)->default(1);
            $table->unsignedInteger('order_no')->default(1);
            $table->text('correct_text')->nullable();
            $table->json('answer_key')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['exam_id', 'order_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
