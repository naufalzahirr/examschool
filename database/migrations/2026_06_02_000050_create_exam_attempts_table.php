<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('exam_participants')->cascadeOnDelete();
            $table->string('client_attempt_id', 80)->nullable();
            $table->string('device_id')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('last_synced_at')->nullable();
            $table->string('status')->default('started');
            $table->string('cached_payload_hash')->nullable();
            $table->json('answers_snapshot')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['participant_id', 'client_attempt_id']);
            $table->index(['exam_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
