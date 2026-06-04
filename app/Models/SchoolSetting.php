<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $row = self::where('key', $key)->first();
        if (! $row) {
            return $default;
        }

        $decoded = json_decode((string) $row->value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $row->value;
    }

    public static function setValue(string $key, mixed $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => is_string($value) ? $value : json_encode($value)]
        );
    }

    public static function defaults(): array
    {
        return [
            'school_name' => 'Nama Sekolah',
            'school_address' => '',
            'school_logo' => '',
            'academic_year' => date('Y') . '/' . ((int) date('Y') + 1),
            'semester' => 'Ganjil',
            'principal_name' => '',
            'proctor_name' => '',
            'timezone' => config('app.timezone', 'Asia/Jakarta'),
            'late_tolerance_minutes' => 15,
            'upload_grace_minutes' => 10,
            'package_download_open_hours' => 12,
            'package_download_concurrent_limit' => 50,
            'package_queue_slot_ttl_minutes' => 10,
            'package_download_max_attempts' => 3,
            'default_exam_lock_mode' => 'strict_airplane',
            'default_exam_exit_policy' => 'proctor_code',
            'offline_exit_code_length' => 8,
            'exit_violation_max_allowed' => 3,
            'default_teacher_password' => 'Guru@123',
            'default_student_password_mode' => 'nis',
        ];
    }

    public static function allSettings(): array
    {
        $settings = self::defaults();
        foreach (self::all() as $row) {
            $settings[$row->key] = self::getValue($row->key, $settings[$row->key] ?? null);
        }

        return $settings;
    }
}
