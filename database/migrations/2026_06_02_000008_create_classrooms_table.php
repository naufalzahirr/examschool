<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            // ID sengaja mengikuti ID classroom dari SILAP agar sinkron API mudah dan classroom_id siswa tetap cocok.
            $table->id();
            $table->unsignedBigInteger('term_id')->nullable()->index();
            $table->string('nama_kelas', 100);
            $table->unsignedTinyInteger('tingkat')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['term_id', 'nama_kelas']);
            $table->index(['term_id', 'tingkat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
