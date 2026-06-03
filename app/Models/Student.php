<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Student extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'silap_id', 'term_id', 'classroom_id', 'silap_user_id',
        'nis', 'name', 'nama_lengkap', 'class_name', 'password', 'is_active',
        'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'agama', 'alamat', 'kontak', 'photo',
        'nama_ayah', 'pekerjaan_ayah', 'kontak_ayah', 'nama_ibu', 'pekerjaan_ibu', 'kontak_ibu',
        'nama_wali_murid', 'kontak_wali', 'alamat_orangtua', 'alamat_wali', 'meta',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'tanggal_lahir' => 'date',
            'meta' => 'array',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ExamParticipant::class);
    }

    public static function syncFromSilap(array $row, ?string $defaultPassword = null): self
    {
        $nis = strtoupper(trim((string) ($row['nis'] ?? '')));
        if ($nis === '') {
            throw new \InvalidArgumentException('NIS kosong.');
        }

        $name = trim((string) ($row['nama_lengkap'] ?? $row['name'] ?? '')) ?: $nis;
        $classroomId = $row['classroom_id'] ?? null;
        $className = $row['class_name'] ?? $row['kelas'] ?? null;
        if (!$className && $classroomId) {
            $className = Classroom::find($classroomId)?->nama_kelas;
        }
        $student = self::where('nis', $nis)->first();

        $payload = [
            'silap_id' => isset($row['id']) ? (int) $row['id'] : ($row['silap_id'] ?? null),
            'term_id' => $row['term_id'] ?? null,
            'classroom_id' => $classroomId,
            'silap_user_id' => $row['user_id'] ?? $row['silap_user_id'] ?? null,
            'nis' => $nis,
            'name' => $name,
            'nama_lengkap' => $name,
            'class_name' => $className,
            'jenis_kelamin' => $row['jenis_kelamin'] ?? null,
            'tempat_lahir' => $row['tempat_lahir'] ?? null,
            'tanggal_lahir' => $row['tanggal_lahir'] ?? null,
            'agama' => $row['agama'] ?? null,
            'alamat' => $row['alamat'] ?? null,
            'kontak' => $row['kontak'] ?? null,
            'photo' => $row['photo'] ?? null,
            'nama_ayah' => $row['nama_ayah'] ?? null,
            'pekerjaan_ayah' => $row['pekerjaan_ayah'] ?? null,
            'kontak_ayah' => $row['kontak_ayah'] ?? null,
            'nama_ibu' => $row['nama_ibu'] ?? null,
            'pekerjaan_ibu' => $row['pekerjaan_ibu'] ?? null,
            'kontak_ibu' => $row['kontak_ibu'] ?? null,
            'nama_wali_murid' => $row['nama_wali_murid'] ?? null,
            'kontak_wali' => $row['kontak_wali'] ?? null,
            'alamat_orangtua' => $row['alamat_orangtua'] ?? null,
            'alamat_wali' => $row['alamat_wali'] ?? null,
            'is_active' => (bool) ($row['is_active'] ?? true),
            'meta' => array_filter([
                'source' => 'silap',
                'raw' => $row,
            ]),
        ];

        $password = $row['password'] ?? $defaultPassword;
        if (!$student && !$password) {
            // Default produksi awal: NIS sebagai password pertama. Sekolah sebaiknya mewajibkan reset/ganti password berikutnya.
            $password = $nis;
        }
        if ($password) {
            $payload['password'] = $password;
        }

        if ($student) {
            $student->update($payload);
            if ($password) {
                $student->tokens()->delete();
            }
            return $student;
        }

        return self::create($payload);
    }
}
