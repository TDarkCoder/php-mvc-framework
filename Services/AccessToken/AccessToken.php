<?php

namespace TDarkCoder\Framework\Services\AccessToken;

use TDarkCoder\Framework\Database\Model;

class AccessToken extends Model
{
    protected array $fillable = [
        'user_id',
        'token',
        'device',
        'expires_at',
    ];

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public function isExpired(): bool
    {
        $expiresAt = $this->expires_at;

        return !is_null($expiresAt) && strtotime((string) $expiresAt) < time();
    }

    public function table(): string
    {
        return 'access_tokens';
    }
}
