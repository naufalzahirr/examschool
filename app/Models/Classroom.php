<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'id', 'term_id', 'nama_kelas', 'tingkat', 'meta',
    ];

    protected $casts = [
        'term_id' => 'integer',
        'tingkat' => 'integer',
        'meta' => 'array',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'classroom_id');
    }

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_classrooms')->withTimestamps();
    }

    public static function syncFromSilap(array $row): self
    {
        $id = isset($row['id']) ? (int) $row['id'] : (isset($row['classroom_id']) ? (int) $row['classroom_id'] : null);
        if (!$id) {
            throw new \InvalidArgumentException('ID classroom kosong.');
        }

        $namaKelas = trim((string) ($row['nama_kelas'] ?? $row['class_name'] ?? $row['kelas'] ?? ''));
        if ($namaKelas === '') {
            throw new \InvalidArgumentException('Nama kelas kosong.');
        }

        return self::updateOrCreate(
            ['id' => $id],
            [
                'term_id' => isset($row['term_id']) ? (int) $row['term_id'] : null,
                'nama_kelas' => $namaKelas,
                'tingkat' => isset($row['tingkat']) ? (int) $row['tingkat'] : self::inferTingkat($namaKelas),
                'meta' => ['source' => 'silap', 'raw' => $row],
            ]
        );
    }

    public static function inferTingkat(string $namaKelas): ?int
    {
        $upper = strtoupper(trim($namaKelas));
        return match (true) {
            str_starts_with($upper, 'XII') => 12,
            str_starts_with($upper, 'XI') => 11,
            str_starts_with($upper, 'X ') || $upper === 'X' || str_starts_with($upper, 'X-') => 10,
            default => null,
        };
    }
}
