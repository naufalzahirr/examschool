<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'event', 'auditable_type', 'auditable_id', 'ip_address', 'user_agent', 'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $event, mixed $auditable = null, array $properties = []): self
    {
        $request = request();

        return self::create([
            'user_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => is_object($auditable) ? get_class($auditable) : null,
            'auditable_id' => is_object($auditable) && isset($auditable->id) ? $auditable->id : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'properties' => $properties ?: null,
        ]);
    }
}
