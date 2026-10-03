<?php

namespace Cartalyst\Sentinel\Guards;

use Cartalyst\Sentinel\Sentinel;
use Cartalyst\Sentinel\Users\UserInterface;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;

/**
 * Exposes Sentinel through Laravel's Auth API without maintaining a second session.
 *
 * Sentinel remains the source of truth. Both APIs therefore read and mutate the
 * same in-memory user and Sentinel persistence record.
 */
class SentinelSessionGuard implements StatefulGuard
{
    use GuardHelpers;

    protected bool $viaRemember = false;

    public function __construct(
        protected string $name,
        protected Sentinel $sentinel,
        UserProvider $provider,
        protected Dispatcher $events,
    ) {
        $this->provider = $provider;
    }

    public function user(): ?Authenticatable
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $user = $this->sentinel->getUser();

        $this->viaRemember = $this->sentinel
            ->getPersistenceRepository()
            ->viaRemember();

        return $this->user = $user instanceof Authenticatable ? $user : null;
    }

    public function attempt(array $credentials = [], $remember = false): bool
    {
        $this->events->dispatch(new Attempting($this->name, $credentials, $remember));
        $user = $this->sentinel->authenticate($credentials, (bool) $remember, true);

        if (! $user instanceof Authenticatable) {
            $candidate = $this->provider->retrieveByCredentials($credentials);
            $this->events->dispatch(new Failed($this->name, $candidate, $credentials));

            return false;
        }

        $this->user = $user;
        $this->events->dispatch(new Login($this->name, $user, (bool) $remember));
        $this->events->dispatch(new Authenticated($this->name, $user));

        return true;
    }

    public function once(array $credentials = []): bool
    {
        $user = $this->sentinel->authenticate($credentials, false, false);

        if (! $user instanceof Authenticatable) {
            return false;
        }

        $this->user = $user;
        $this->events->dispatch(new Authenticated($this->name, $user));

        return true;
    }

    public function login(Authenticatable $user, $remember = false): void
    {
        if (! $this->sentinel->login($this->sentinelUser($user), (bool) $remember)) {
            throw new InvalidArgumentException('Sentinel could not log in the given user.');
        }

        $this->user = $user;
        $this->events->dispatch(new Login($this->name, $user, (bool) $remember));
        $this->events->dispatch(new Authenticated($this->name, $user));
    }

    public function loginUsingId($id, $remember = false): Authenticatable|false
    {
        $user = $this->provider->retrieveById($id);

        if (! $user) {
            return false;
        }

        $this->login($user, $remember);

        return $user;
    }

    public function onceUsingId($id): Authenticatable|false
    {
        $user = $this->provider->retrieveById($id);

        if (! $user) {
            return false;
        }

        $this->sentinel->setUser($this->sentinelUser($user));
        $this->user = $user;
        $this->events->dispatch(new Authenticated($this->name, $user));

        return $user;
    }

    public function validate(array $credentials = []): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        return $user !== null && $this->provider->validateCredentials($user, $credentials);
    }

    public function viaRemember(): bool
    {
        return $this->viaRemember;
    }

    public function setUser(Authenticatable $user): static
    {
        $this->sentinel->setUser($this->sentinelUser($user));
        $this->user = $user;

        return $this;
    }

    public function logout(): void
    {
        $user = $this->user();

        $this->sentinel->logout();
        $this->user = null;
        $this->viaRemember = false;

        if ($user) {
            $this->events->dispatch(new Logout($this->name, $user));
        }
    }

    public function getPersistenceCode(?UserInterface $user = null): ?string
    {
        $user ??= $this->sentinel->getUser();

        return $user
            ? $this->sentinel->getPersistenceRepository()->getPersistenceCodeFor($user)
            : null;
    }

    public function getActivationCode(?UserInterface $user = null): ?string
    {
        $user ??= $this->sentinel->getUser();

        return $user
            ? $this->sentinel->getActivationRepository()->getActivationCodeFor($user)
            : null;
    }

    public function isActivated(?UserInterface $user = null): ?bool
    {
        $user ??= $this->sentinel->getUser();

        return $user
            ? $this->sentinel->getActivationRepository()->completed($user)
            : null;
    }

    protected function sentinelUser(Authenticatable $user): UserInterface
    {
        if (! $user instanceof UserInterface) {
            throw new InvalidArgumentException('The user must implement Sentinel UserInterface.');
        }

        return $user;
    }
}
