<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefreshToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'token_hash',
        'expires_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Set the token and automatically generate its hash
     */
    public function setTokenAttribute($value)
    {
        $this->attributes['token'] = $value;
        $this->attributes['token_hash'] = hash('sha256', $value);
    }

    /**
     * Get the user that owns the refresh token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the token is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Find a token by its value (searches by hash)
     */
    public static function findByToken(string $token): ?self
    {
        $hash = hash('sha256', $token);
        return static::where('token_hash', $hash)->first();
    }
}
