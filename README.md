# Hydro Raindrop

Hydro Raindrop was built to protect against phishing, hacking, and illegal attempts to access your clients' data. Hydro has easy to implement APIs and a beautiful mobile app available for your users on Android and iOS. The best part is, the integration is 100% FREE for you and your users!

[Read more about Hydro Raindrop](https://www.hydrogenplatform.com/hydro-raindrop)

## Requirements

- PHP 7.1
- Laravel 5.8
- The Hydro App ([iOS](https://itunes.apple.com/app/id1406519814) or [Android](https://play.google.com/store/apps/details?id=com.hydrogenplatform.hydro))

## Laravel

This package allows developers to integrate a second layer of security (Multi Factor Authentication) to their apps.

## Register

Before you can use the service, you need to create a developer account at [www.hydrogenplatform.com](https://www.hydrogenplatform.com/). 
You can create a new application to obtain a `Client ID`, `Client Secret` and `Application ID`.

By default you can use the Sandbox environment, you can apply for a production environment any time through the [www.hydrogenplatform.com](https://www.hydrogenplatform.com/) website.

## Installation

Install the package using the following composer command:

```
composer require adrenth/laravel-hydro-raindrop
```

### Install assets

Publish the public assets:

```
php artisan vendor:publish --tag=public --force
```

### Install configuration

Publish the configuration file `app/hydro-raindrop.php`:

```
php artisan vendor:publish --tag=config
```

### Authentication Routes (optional)

To add Laravels' default Authentication routes, execute this command:

```
php artisan make:auth
```

Please see the official documentation on this subject: https://laravel.com/docs/5.8/authentication

### Environment configuration

Add the environment variables to your `.env.example` file:

```
HYDRO_RAINDROP_CLIENT_ID = "[Client ID here]"
HYDRO_RAINDROP_SECRET = "[Client Secret here]"
HYDRO_RAINDROP_APPLICATION_ID = "[Application ID here]"
HYDRO_RAINDROP_ENVIRONMENT = "sandbox"
```

> Don't commit sensitive information to your repositories. Your `.env` file should contain the actual credentials and should be ignored by Git.

Look for the `app/raindrop.php` file and review the configuration.

After changing you configuration, don't forget to run the following command which clears the configuration cache.

```
php artisan config:cache
```

### Run database migrations

Run the database migrations.

```
php artisan migrate
```

This will add the column `hydro_id`, `hydro_raindrop_enabled` and `hydro_raindrop_confirmed` to the `users` database table (table name is configurable, check `config/raindrop.php`).

### Middleware

Add the `raindrop` middleware to the `App/Http/Kernel`:

```
protected $routeMiddleware = [
    // ..
    'hydro-raindrop' => \Adrenth\LaravelHydroRaindrop\Middleware::class
];
```

## Usage

Now add the `raindrop` middleware to the routes you'd like to protect with Hydro Raindrop MFA.

```
Route::get('/admin', function () {
    return view('admin.index');
})->middleware(['auth', 'hydro-raindrop']);
```
> Note that the `hydro-raindrop` middleware only works with an authenticated session. So it should be used in combination with the `auth` middleware.

### Throttling / Lockout after x attempts

Unless you need something really fancy, you can probably use Laravel's [route throttle middleware](https://laravel.com/docs/5.8/middleware) for that:

```
Route::get('/admin', function () {
    return view('admin.index');
})->middleware(['auth', 'hydro-raindrop', 'throttle']);
```

## Overriding Package Views

It is possible to override the views provided by this package. 

Please see the Laravel documentation page: https://laravel.com/docs/5.8/packages#views

## Further reading

For more info on Hydro or MFA and how it’s changing the world, check out the following:

* [Hydro’s Official Site](https://www.hydrogenplatform.com/).
* [Hydro’s Medium Blog](https://medium.com/hydrogen-api).
* [Hydro MFA Client Side Raindrop API](https://www.hydrogenplatform.com/docs/hydro/v1/).
* Become a part of the fastest growing Community! [Join Hydro Community](https://github.com/HydroCommunity).
* Are you a developer interested in expanding the Hydro ecosystem and earning bounties? [Visit Hydro HCDP Github Page](https://github.com/HydroBlockchain/hcdp/issues).
* Follow Hydro on [Telegram](https://t.me/projecthydro), [Facebook](https://www.facebook.com/hydrogenplatform), [Twitter](https://twitter.com/hydrogenapi) or [Instagram](https://www.instagram.com/hydrogenplatform/).

Looking for a drop-in solution? Hydro Raindrop is also available for the following Content Management Systems:

- [OctoberCMS](https://octobercms.com/plugin/hydrocommunity-raindrop)
- [Joomla](https://extensions.joomla.org/extension/hydro-raindrop-mfa/)
- [WordPress](https://nl.wordpress.org/plugins/wp-hydro-raindrop/)
- [Drupal](https://www.drupal.org/project/hydro_mfa)
