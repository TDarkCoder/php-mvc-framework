<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Services\AccessToken;

use Exception;
use TDarkCoder\Framework\Enums\SessionKeys;

trait AuthorizeTokens
{
    /**
     * @throws Exception
     */
    public function authorizeToken(): void
    {
        $plainToken = bin2hex(random_bytes(32));
        $lifetime = (int) config('auth.token_lifetime');

        $attributes = [
            'user_id' => $this->{$this->primaryKey},
            'token' => AccessToken::hash($plainToken),
            'device' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ];

        if ($lifetime > 0) {
            $attributes['expires_at'] = date('Y-m-d H:i:s', time() + $lifetime);
        }

        AccessToken::create($attributes);

        session()->regenerate();
        session()->set(SessionKeys::AuthToken->value, $plainToken);
    }

    public function authorizeWithToken(string $token): ?static
    {
        $accessToken = AccessToken::findOne(['token' => AccessToken::hash($token)]);

        if (is_null($accessToken) || $accessToken->isExpired()) {
            return null;
        }

        return static::findOne([$this->primaryKey => $accessToken->user_id]);
    }

    public function logout(): void
    {
        $token = session()->get(SessionKeys::AuthToken->value);

        if (is_string($token)) {
            AccessToken::findOne(['token' => AccessToken::hash($token)])?->delete();
        }

        session()->invalidate();
    }
}
