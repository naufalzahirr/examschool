<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'silap_id', 'silap_user_id', 'user_id', 'nip', 'name', 'nama_lengkap',
        'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'kontak', 'alamat', 'photo',
        'is_active', 'meta',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function syncFromSilap(array $row): self
    {
        $name = trim((string) ($row['nama_lengkap'] ?? $row['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Nama guru kosong.');
        }

        $silapId = isset($row['id']) ? (int) $row['id'] : ($row['silap_id'] ?? null);
        $teacher = $silapId ? self::where('silap_id', $silapId)->first() : null;
        if (!$teacher && !empty($row['nip'])) {
            $teacher = self::where('nip', $row['nip'])->first();
        }

        $payload = [
            'silap_id' => $silapId,
            'silap_user_id' => $row['user_id'] ?? $row['silap_user_id'] ?? null,
            'user_id' => $row['local_user_id'] ?? $teacher?->user_id,
            'nip' => $row['nip'] ?? null,
            'name' => $name,
            'nama_lengkap' => $name,
            'tempat_lahir' => $row['tempat_lahir'] ?? null,
            'tanggal_lahir' => $row['tanggal_lahir'] ?? null,
            'jenis_kelamin' => $row['jenis_kelamin'] ?? null,
            'kontak' => $row['kontak'] ?? null,
            'alamat' => $row['alamat'] ?? null,
            'photo' => $row['photo'] ?? null,
            'is_active' => (bool) ($row['is_active'] ?? true),
            'meta' => ['source' => 'silap', 'raw' => $row],
        ];

        if ($teacher) {
            $teacher->update($payload);
            return $teacher;
        }

        return self::create($payload);
    }
}
