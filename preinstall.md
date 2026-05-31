## Перед использованием:

- Настроить конфиг авторизации на нужный провайдер (SentinelUserProvider из этого пакета)
- Настроить cartalyst.sentinel.php так чтобы он ссылался на App\Models\User.php или что у вас там
- Добавить в любимый сервис-провайдер эти строки:
        
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
                $app['session'],
                $app['request']
            );
        });

- Добавить в конфиг auth.php что-то типа:

            'guards' => [
                'web' => [
                    'driver' => 'session',
                    'provider' => 'users',
                ],
                'sentinel-session' => [
                    'driver' => 'sentinel-session',
                    'provider' => 'sentinel',
                ]
            ],
