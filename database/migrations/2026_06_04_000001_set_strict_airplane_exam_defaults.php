<?php

use App\Models\SchoolSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $current = SchoolSetting::getValue('default_exam_lock_mode', null);
        if ($current === null || $current === 'standard') {
            SchoolSetting::setValue('default_exam_lock_mode', 'strict_airplane');
        }
        SchoolSetting::setValue('default_exam_exit_policy', 'proctor_code');
    }

    public function down(): void
    {
        if (SchoolSetting::getValue('default_exam_lock_mode') === 'strict_airplane') {
            SchoolSetting::setValue('default_exam_lock_mode', 'standard');
        }
    }
};
