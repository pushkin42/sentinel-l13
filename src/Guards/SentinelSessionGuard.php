<?php

namespace Cartalyst\Sentinel\Guards;

use Illuminate\Auth\SessionGuard;
use Cartalyst\Sentinel\Sentinel;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class SentinelSessionGuard extends SessionGuard
{
    protected $sentinel;

    public function __construct($name, Sentinel $sentinel, $provider, $session, $request)
    {
        parent::__construct($name, $provider, $session, $request);
        $this->sentinel = $sentinel;
    }

    public function loginUsingId($id, $remember = false)
    {
        $user = $this->provider->retrieveById($id);

        if (!$user) return false;

        return $this->login($user, $remember);
    }

    public function login(AuthenticatableContract $user, $remember = false)
    {
        parent::login($user, $remember);
        return ($this->sentinel->login($user, $remember));
    }

    public function user()
    {
        $user = parent::user();

        if ($user && !$this->sentinel->check()) {
            $this->sentinel->login($user);
        } elseif (!$user && $this->sentinel->check()) {
            $this->sentinel->logout();
        }

        return $user ?? $this->sentinel->check(false);
    }

    public function attempt(array $credentials = [], $remember = false)
    {
        if ($user = $this->sentinel->authenticate($credentials, $remember)) {
            $this->login($user, $remember);
            return true;
        }
        return false;
    }
}
