## Настройка Laravel Auth bridge

Модель пользователя должна наследовать `Cartalyst\Sentinel\Users\EloquentUser` либо одновременно реализовывать `Cartalyst\Sentinel\Users\UserInterface` и `Illuminate\Contracts\Auth\Authenticatable`.

Добавьте guard и provider в `config/auth.php`:

```php
'defaults' => [
    'guard' => 'web',
],

'guards' => [
    'web' => [
        'driver' => 'sentinel-session',
        'provider' => 'sentinel',
    ],
],

'providers' => [
    'sentinel' => [
        'driver' => 'sentinel',
        'model' => App\Models\User::class,
    ],
],
```

Укажите ту же модель в `config/cartalyst.sentinel.php`:

```php
'users' => [
    'model' => App\Models\User::class,
],
```

После этого `Sentinel`, `Auth`, `$request->user()`, middleware `auth` и Laravel policies используют одну Sentinel-сессию. Отдельная Laravel session guard запись не создаётся.
