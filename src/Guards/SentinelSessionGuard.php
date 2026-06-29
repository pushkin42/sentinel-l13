<?php

namespace Cartalyst\Sentinel\Guards;

use Cartalyst\Sentinel\Users\UserInterface;
use Illuminate\Auth\SessionGuard;
use Cartalyst\Sentinel\Sentinel;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Support\Facades\Auth;

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
        $sentinelUser = $this->sentinel->getUser(false);

        if ($sentinelUser) {
            $user = $this->provider->retrieveById($sentinelUser->getAuthIdentifier());
            if ($user) {
                $this->setUser($user);
                return $user;
            }
        }

        $user = parent::user();

        if ($user && !$this->sentinel->check()) {
            $this->sentinel->login($user);
        } elseif (!$user && $this->sentinel->check()) {
            $this->sentinel->logout();
        }

        return $user;
    }

    public function attempt(array $credentials = [], $remember = false)
    {
        if ($user = $this->sentinel->authenticate($credentials, $remember)) {
            $this->login($user, $remember);
            return true;
        }
        return false;
    }

    public function getPersistenceCode(?UserInterface $user = null): null|string
    {
        $user = $user ?? $this->user();
        if (!$user) return null;

        return $this->sentinel->getPersistenceRepository()->getPersistenceCodeFor($user);
    }

    public function getActivationCode(?UserInterface $user = null): ?string
    {
        $user = $user ?? $this->user();
        if (!$user) return null;

        return $this->sentinel->getActivationRepository()->getActivationCodeFor($user);
    }

    public function isActivated(?UserInterface $user = null): ?bool
    {
        $user = $user ?? $this->user();
        if (!$user) return null;

        return $this->sentinel->getActivationRepository()->completed($user);
    }
}
