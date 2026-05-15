<?php

declare(strict_types=1);

namespace GrobinSon\LaravelMcpServer\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Sanctum\PersonalAccessToken;

class SanctumTokenResolver
{
    /**
     * Resolve a Sanctum personal access token to its owning user.
     *
     * Returns null if the token is invalid, missing, or doesn't belong to a user.
     */
    public function resolve(string $token): ?Authenticatable
    {
        $accessToken = PersonalAccessToken::findToken($token);

        if (! $accessToken) {
            return null;
        }

        $tokenable = $accessToken->tokenable;

        if (! $tokenable instanceof Authenticatable) {
            return null;
        }

        return $tokenable;
    }
}
