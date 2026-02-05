<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'causer_type',
        'causer_id',
        'subject_type',
        'subject_id',
        'event',
        'description',
        'properties',
        'changes',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'changes' => 'array',
        ];
    }

    // Relaciones polimórficas
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeForEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    public function scopeForCauser($query, Model $causer)
    {
        return $query->where('causer_type', get_class($causer))
                     ->where('causer_id', $causer->id);
    }

    public function scopeForSubject($query, Model $subject)
    {
        return $query->where('subject_type', get_class($subject))
                     ->where('subject_id', $subject->id);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByIpAddress($query, string $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    // Accessors
    public function getCauserNameAttribute(): ?string
    {
        return $this->causer?->name ?? $this->causer?->email ?? null;
    }

    public function getSubjectNameAttribute(): ?string
    {
        if (!$this->subject) {
            return null;
        }

        return $this->subject->name 
            ?? $this->subject->title 
            ?? $this->subject->email 
            ?? class_basename($this->subject_type) . ' #' . $this->subject_id;
    }
}
