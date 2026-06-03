<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('question_bank_items') && ! Schema::hasColumn('question_bank_items', 'visibility')) {
            Schema::table('question_bank_items', function (Blueprint $table) {
                $table->string('visibility', 20)->default('school')->index()->after('difficulty');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('question_bank_items') && Schema::hasColumn('question_bank_items', 'visibility')) {
            Schema::table('question_bank_items', function (Blueprint $table) {
                $table->dropColumn('visibility');
            });
        }
    }
};
