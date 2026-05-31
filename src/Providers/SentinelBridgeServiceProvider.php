<?php

namespace Sentinel\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Hashing\Hasher;
use Cartalyst\Sentinel\Laravel\Auth\SentinelUserProvider;

class SentinelBridgeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Auth::provider('sentinel', function ($app, array $config) {
            return new SentinelUserProvider(
                $app['hash'],
                $config['model']
            );
        });
      
        Auth::extend('sentinel-session', function ($app, $name, $config) {
            $provider = Auth::createUserProvider($config['provider']);
            return new SentinelSessionGuard(
                $name,
                $app['sentinel'],
                $provider,
                $app['session.store'],
                $app['request']
            );
        });
    }
}
