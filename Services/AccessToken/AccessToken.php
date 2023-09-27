<?php

namespace TDarkCoder\Framework\Services\AccessToken;

use TDarkCoder\Framework\Model;

class AccessToken extends Model
{
    protected array $fillable = [
        'user_id',
        'token',
        'device',
    ];

    public function table(): string
    {
        return 'access_tokens';
    }
}