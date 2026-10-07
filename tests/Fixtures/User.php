<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Tests\Fixtures;

use TDarkCoder\Framework\Contracts\Authenticatable;
use TDarkCoder\Framework\Database\Model;
use TDarkCoder\Framework\Services\AccessToken\AuthorizeTokens;

class User extends Model implements Authenticatable
{
    use AuthorizeTokens;

    protected array $fillable = ['name', 'email', 'password', 'role'];
    protected array $hidden = ['password'];

    public function table(): string
    {
        return 'users';
    }
}
