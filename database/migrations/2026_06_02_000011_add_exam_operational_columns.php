<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (! Schema::hasColumn('exams', 'published_at')) {
                $table->dateTime('published_at')->nullable()->after('status')->index();
            }

            if (! Schema::hasColumn('exams', 'closed_at')) {
                $table->dateTime('closed_at')->nullable()->after('published_at')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'closed_at')) {
                $table->dropColumn('closed_at');
            }

            if (Schema::hasColumn('exams', 'published_at')) {
                $table->dropColumn('published_at');
            }
        });
    }
};
