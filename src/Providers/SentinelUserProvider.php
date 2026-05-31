<?php

namespace Cartalyst\Sentinel\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher;

class SentinelUserProvider implements UserProvider
{
    public function __construct(
        protected Hasher $hasher,
        protected string $model,
    )
    {
    }

    public function retrieveById($identifier): ?Authenticatable
    {
        return $this->newModelQuery()->find($identifier);
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return $this->newModelQuery()
            ->whereKey($identifier)
            ->where('remember_token', $token)
            ->first();
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        $user->forceFill(['remember_token' => $token])->save();
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $credentials = array_filter(
            $credentials,
            fn($key) => !str_contains($key, 'password'),
            ARRAY_FILTER_USE_KEY
        );

        if (empty($credentials)) {
            return null;
        }

        $query = $this->newModelQuery();

        foreach ($credentials as $key => $value) {
            $query->where($key, $value);
        }

        return $query->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return isset($credentials['password'])
            && $this->hasher->check($credentials['password'], $user->getAuthPassword());
    }

    public function rehashPasswordIfRequired(
        Authenticatable $user,
        array           $credentials,
        bool            $force = false
    ): void
    {
        if (!isset($credentials['password'])) {
            return;
        }

        if ($force || $this->hasher->needsRehash($user->getAuthPassword())) {
            $user->forceFill([
                'password' => $this->hasher->make($credentials['password']),
            ])->save();
        }
    }

    protected function newModelQuery()
    {
        return (new $this->model)->newQuery();
    }
}
