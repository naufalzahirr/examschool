<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (! Schema::hasColumn('exams', 'package_disk')) {
                $table->string('package_disk', 40)->default('public')->after('package_path');
            }

            if (! Schema::hasColumn('exams', 'package_public_url')) {
                $table->text('package_public_url')->nullable()->after('package_disk');
            }

            if (! Schema::hasColumn('exams', 'package_is_encrypted')) {
                $table->boolean('package_is_encrypted')->default(true)->after('package_public_url');
            }

            if (! Schema::hasColumn('exams', 'package_cipher')) {
                $table->string('package_cipher', 40)->nullable()->after('package_is_encrypted');
            }

            if (! Schema::hasColumn('exams', 'package_plain_checksum')) {
                $table->string('package_plain_checksum', 80)->nullable()->after('package_checksum')->index();
            }

            if (! Schema::hasColumn('exams', 'package_unlock_key_encrypted')) {
                $table->text('package_unlock_key_encrypted')->nullable()->after('package_cipher');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            foreach ([
                'package_unlock_key_encrypted',
                'package_plain_checksum',
                'package_cipher',
                'package_is_encrypted',
                'package_public_url',
                'package_disk',
            ] as $column) {
                if (Schema::hasColumn('exams', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
