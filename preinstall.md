## Перед использованием:

- Настроить конфиг авторизации на нужный провайдер (SentinelUserProvider из этого пакета)
- Настроить cartalyst.sentinel.php так чтобы он ссылался на App\Models\User.php или что у вас там

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
