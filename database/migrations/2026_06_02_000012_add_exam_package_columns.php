<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (! Schema::hasColumn('exams', 'package_path')) {
                $table->string('package_path')->nullable()->after('package_version');
            }

            if (! Schema::hasColumn('exams', 'package_checksum')) {
                $table->string('package_checksum', 80)->nullable()->after('package_path')->index();
            }

            if (! Schema::hasColumn('exams', 'package_generated_at')) {
                $table->dateTime('package_generated_at')->nullable()->after('package_checksum')->index();
            }

            if (! Schema::hasColumn('exams', 'package_size_bytes')) {
                $table->unsignedBigInteger('package_size_bytes')->default(0)->after('package_generated_at');
            }

            if (! Schema::hasColumn('exams', 'package_downloads_count')) {
                $table->unsignedBigInteger('package_downloads_count')->default(0)->after('package_size_bytes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            foreach (['package_downloads_count', 'package_size_bytes', 'package_generated_at', 'package_checksum', 'package_path'] as $column) {
                if (Schema::hasColumn('exams', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
