<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            // Kolom integrasi SILAP. Nama field dibuat dekat dengan dump `siswa.sql` agar sinkron API mudah.
            $table->unsignedBigInteger('silap_id')->nullable()->unique();
            $table->unsignedBigInteger('term_id')->nullable()->index();
            $table->unsignedBigInteger('classroom_id')->nullable()->index();
            $table->unsignedBigInteger('silap_user_id')->nullable()->index();

            // Kolom login dan tampilan lokal aplikasi ujian.
            $table->string('nis', 40)->unique();
            $table->string('name');
            $table->string('nama_lengkap')->nullable();
            $table->string('class_name')->nullable()->index();
            $table->string('password');
            $table->boolean('is_active')->default(true)->index();

            // Kolom profil mengikuti format SILAP.
            $table->string('jenis_kelamin')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('agama')->nullable();
            $table->text('alamat')->nullable();
            $table->string('kontak')->nullable();
            $table->string('photo')->nullable();
            $table->string('nama_ayah', 80)->nullable();
            $table->string('pekerjaan_ayah', 80)->nullable();
            $table->string('kontak_ayah', 40)->nullable();
            $table->string('nama_ibu', 80)->nullable();
            $table->string('pekerjaan_ibu', 80)->nullable();
            $table->string('kontak_ibu', 40)->nullable();
            $table->string('nama_wali_murid', 80)->nullable();
            $table->string('kontak_wali', 40)->nullable();
            $table->string('alamat_orangtua')->nullable();
            $table->string('alamat_wali')->nullable();

            $table->json('meta')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index(['classroom_id', 'is_active']);
            $table->index(['class_name', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
