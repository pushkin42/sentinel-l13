<?php

namespace Cartalyst\Sentinel\Tests\Guards;

use Cartalyst\Sentinel\Guards\SentinelSessionGuard;
use Cartalyst\Sentinel\Sentinel;
use Cartalyst\Sentinel\Persistences\PersistenceRepositoryInterface;
use Cartalyst\Sentinel\Users\UserInterface;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Events\Dispatcher;
use Mockery;
use PHPUnit\Framework\TestCase;

class SentinelSessionGuardTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_exposes_a_user_resolved_by_sentinel_and_caches_it(): void
    {
        $user = $this->user();
        $persistences = Mockery::mock(PersistenceRepositoryInterface::class);
        $persistences->shouldReceive('viaRemember')->once()->andReturnFalse();
        $sentinel = Mockery::mock(Sentinel::class);
        $sentinel->shouldReceive('getUser')->once()->andReturn($user);
        $sentinel->shouldReceive('getPersistenceRepository')->once()->andReturn($persistences);

        $guard = $this->guard($sentinel);

        $this->assertSame($user, $guard->user());
        $this->assertSame($user, $guard->user());
    }

    public function test_attempt_authenticates_only_once_through_sentinel(): void
    {
        $credentials = ['email' => 'user@example.com', 'password' => 'secret'];
        $user = $this->user();
        $sentinel = Mockery::mock(Sentinel::class);
        $sentinel->shouldReceive('authenticate')
            ->once()
            ->with($credentials, true, true)
            ->andReturn($user);
        $sentinel->shouldNotReceive('login');

        $guard = $this->guard($sentinel);

        $this->assertTrue($guard->attempt($credentials, true));
        $this->assertSame($user, $guard->user());
        $this->assertFalse($guard->viaRemember());
    }

    public function test_laravel_login_is_persisted_by_sentinel_only_once(): void
    {
        $user = $this->user();
        $sentinel = Mockery::mock(Sentinel::class);
        $sentinel->shouldReceive('login')->once()->with($user, false)->andReturn($user);

        $guard = $this->guard($sentinel);
        $guard->login($user);

        $this->assertSame($user, $guard->user());
    }

    public function test_logout_clears_both_interfaces_without_resolving_recursively(): void
    {
        $user = $this->user();
        $persistences = Mockery::mock(PersistenceRepositoryInterface::class);
        $persistences->shouldReceive('viaRemember')->once()->andReturnFalse();
        $sentinel = Mockery::mock(Sentinel::class);
        $sentinel->shouldReceive('getUser')->once()->andReturn($user);
        $sentinel->shouldReceive('getPersistenceRepository')->once()->andReturn($persistences);
        $sentinel->shouldReceive('logout')->once()->andReturnTrue();

        $guard = $this->guard($sentinel);
        $guard->logout();

        $this->assertFalse($guard->hasUser());
    }

    public function test_set_user_is_visible_to_sentinel(): void
    {
        $user = $this->user();
        $sentinel = Mockery::mock(Sentinel::class);
        $sentinel->shouldReceive('setUser')->once()->with($user);

        $guard = $this->guard($sentinel);

        $this->assertSame($guard, $guard->setUser($user));
        $this->assertSame($user, $guard->user());
    }

    private function guard(Sentinel $sentinel): SentinelSessionGuard
    {
        return new SentinelSessionGuard(
            'web',
            $sentinel,
            Mockery::mock(UserProvider::class),
            new Dispatcher(new Container()),
        );
    }

    private function user(): UserInterface&Authenticatable
    {
        return Mockery::mock(UserInterface::class, Authenticatable::class);
    }
}
