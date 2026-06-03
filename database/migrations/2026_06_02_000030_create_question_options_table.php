<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->text('label');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('order_no')->default(1);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['question_id', 'order_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_options');
    }
};
