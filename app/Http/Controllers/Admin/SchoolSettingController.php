<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SchoolSetting;
use Illuminate\Http\Request;

class SchoolSettingController extends Controller
{
    public function edit()
    {
        $settings = SchoolSetting::allSettings();
        return view('settings.school', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'school_name' => ['required', 'string', 'max:180'],
            'school_address' => ['nullable', 'string', 'max:500'],
            'school_logo' => ['nullable', 'string', 'max:500'],
            'academic_year' => ['required', 'string', 'max:30'],
            'semester' => ['required', 'string', 'max:30'],
            'principal_name' => ['nullable', 'string', 'max:150'],
            'proctor_name' => ['nullable', 'string', 'max:150'],
            'timezone' => ['required', 'string', 'max:80'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'upload_grace_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'package_download_open_hours' => ['required', 'integer', 'min:0', 'max:168'],
            'package_download_concurrent_limit' => ['required', 'integer', 'min:1', 'max:1000'],
            'package_queue_slot_ttl_minutes' => ['required', 'integer', 'min:1', 'max:60'],
            'package_download_max_attempts' => ['required', 'integer', 'min:1', 'max:20'],
            'default_exam_lock_mode' => ['required', 'in:standard,strict_kiosk'],
            'default_exam_exit_policy' => ['required', 'in:after_submit,after_time_end,proctor_code'],
            'offline_exit_code_length' => ['required', 'integer', 'min:6', 'max:16'],
            'exit_violation_max_allowed' => ['required', 'integer', 'min:0', 'max:50'],
            'default_teacher_password' => ['nullable', 'string', 'min:6', 'max:80'],
            'default_student_password_mode' => ['required', 'in:nis,custom'],
        ]);

        foreach ($data as $key => $value) {
            SchoolSetting::setValue($key, $value);
        }

        AuditLog::record('settings.school_updated', null, ['keys' => array_keys($data)]);

        return back()->with('success', 'Pengaturan sekolah berhasil disimpan.');
    }
}
