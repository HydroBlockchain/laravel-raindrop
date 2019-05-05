<?php

declare(strict_types=1);

namespace Adrenth\LaravelHydroRaindrop;

use Adrenth\LaravelHydroRaindrop\Contracts\UserHelper as UserHelperInterface;
use Adrenth\Raindrop;
use Adrenth\LaravelHydroRaindrop\Console\ResetHydro;
use Adrenth\LaravelHydroRaindrop\Listeners\DestroySession;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

/**
 * Class RaindropServiceProvider
 *
 * @package Adrenth\LaravelHydroRaindrop
 */
class ServiceProvider extends EventServiceProvider
{
    /**
     * @var array
     */
    protected $listen = [
        Logout::class => [
            DestroySession::class,
        ],
    ];

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        parent::boot();

        $this->publishes([
            __DIR__ . '/../resources/assets' => public_path('vendor/hydro-raindrop'),
        ], 'public');

        $this->publishes([
            __DIR__ . '/../config/hydro-raindrop.php' => config_path('hydro-raindrop.php'),
        ], 'config');

        $this->loadMigrationsFrom(__DIR__ . '/../migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'hydro-raindrop');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ResetHydro::class
            ]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function provides(): array
    {
        return array_merge(
            parent::provides(),
            [
                'hydro-raindrop'
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function register(): void
    {
        parent::register();

        $this->app->bind(Raindrop\ApiSettings::class, static function () {
            return new Raindrop\ApiSettings(
                (string) config('hydro-raindrop.api.client_id', ''),
                (string) config('hydro-raindrop.api.client_secret', ''),
                config('hydro-raindrop.api.environment', 'sandbox') === 'sandbox'
                    ? new Raindrop\Environment\SandboxEnvironment()
                    : new Raindrop\Environment\ProductionEnvironment()
            );
        });

        $this->app->bind(Api\CacheTokenStorage::class, static function () {
            return new Api\CacheTokenStorage(
                resolve('cache.store')
            );
        });

        $this->app->alias(
            Api\CacheTokenStorage::class,
            Raindrop\TokenStorage\TokenStorage::class
        );

        $this->app->bind(Raindrop\Client::class, static function () {
            return new Raindrop\Client(
                resolve(Raindrop\ApiSettings::class),
                resolve(Raindrop\TokenStorage\TokenStorage::class),
                (string) config('hydro-raindrop.api.application_id', '')
            );
        });

        $this->app->singleton(MfaSession::class, static function () {
            return new MfaSession(
                resolve(Raindrop\Client::class),
                (int) config('hydro-raindrop.mfa_lifetime', 90),
                (int) config('hydro-raindrop.mfa_verification_lifetime', 0)
            );
        });

        $this->app->singleton(MfaHandler::class, static function () {
            return new MfaHandler(
                resolve(MfaSession::class),
                resolve(Request::class),
                resolve(Raindrop\Client::class),
                resolve(LoggerInterface::class)
            );
        });

        $this->app->bind(UserHelper::class, static function () {
            return new UserHelper(resolve(Raindrop\Client::class));
        });

        $this->app->alias(UserHelper::class, UserHelperInterface::class);
    }
}
