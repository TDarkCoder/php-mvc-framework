<?php

namespace TDarkCoder\Framework\Services\AccessToken;

use Exception;
use TDarkCoder\Framework\Enums\SessionKeys;
use TDarkCoder\Framework\Model;

trait AuthorizeTokens
{
    public function authorizeWithToken(string $token): ?Model
    {
        $token = AccessToken::findOne(['token' => $token]);

        return static::findOne([$this->primaryKey => $token->user_id]);
    }

    /**
     * @throws Exception
     */
    public function authorizeToken(): void
    {
        $token = AccessToken::create([
            'user_id' => $this->{$this->primaryKey},
            'token' => bin2hex(random_bytes(32)),
            'device' => $_SERVER['HTTP_USER_AGENT'],
        ]);

        app()->session->set(SessionKeys::Token->value, $token->token);
    }

    public function logout(): void
    {
        $token = AccessToken::findOne(['token' => app()->session->get(SessionKeys::Token->value)]);
        $token?->delete();

        app()->session->unset(SessionKeys::Token->value);
    }
}