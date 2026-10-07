# About this PHP MVC framework
> **Note:** This repository contains the core code of the PHP MVC framework. If you want to build an application using it, visit the main [PHP MVC Application repository](https://github.com/TDarkCoder/php-mvc-application).

This Framework is a simple and educational PHP MVC (Model-View-Controller) framework designed for learning purposes. It provides a basic structure for building web applications using the MVC architectural pattern. While this framework may not be production-ready, it serves as a valuable educational resource for understanding the fundamentals of MVC and web development in PHP.

> If you want to contribute, feel free to do so.

## Features

- **Routing:** Closures, controllers or plain views, route parameters with type casting, named routes, groups with prefixes and middleware, 404 and 405 handling.
- **Middleware:** Global, route and controller middleware running through a pipeline with `$next`.
- **Requests and Responses:** Raw query, form, JSON and array input, `_method` spoofing, and a `Response` object with JSON and redirect helpers.
- **Validation:** Rule strings such as `required|email|unique:App\Models\User`, custom messages, closure and class based rules, automatic redirect back with errors and old input.
- **Views:** Plain PHP templates rendered in an isolated scope, layouts, dot notation and an `e()` escaping helper.
- **Models:** A small PDO based active record with fillable and hidden attributes, hydration and route model binding.
- **Sessions and Csrf:** Hardened cookies, flash data, Csrf tokens with timing safe comparison.
- **Authentication:** Hashed access tokens with optional expiry through the `Authenticatable` contract.
- **Forms:** A form builder that emits the Csrf and `_method` fields for you.

## Installation

```bash
composer require php-mvc/framework
```

Requires PHP 8.1 or newer with the `pdo`, `json` and `mbstring` extensions.

## Bootstrapping

`public/index.php`:

```php
<?php

use TDarkCoder\Framework\Application;

require __DIR__ . '/../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

$app = new Application(dirname(__DIR__), require __DIR__ . '/../config.php');

require __DIR__ . '/../routes.php';

$app->run();
```

`Application::handle()` returns the `Response` without sending it, which is what the tests use.

### Configuration

```php
<?php

use App\Models\User;
use TDarkCoder\Framework\Http\Middleware\VerifyCsrfToken;

return [
    'debug' => env('APP_DEBUG', false),

    'database' => [
        'dsn' => env('DB_DSN', 'mysql:host=127.0.0.1;dbname=app;charset=utf8mb4'),
        'username' => env('DB_USERNAME'),
        'password' => env('DB_PASSWORD'),
        'options' => [],            // extra PDO options
        'migrations' => null,       // defaults to <root>/migrations
    ],

    'auth' => [
        'model' => User::class,     // must implement Contracts\Authenticatable
        'token_lifetime' => 60 * 60 * 24 * 30, // seconds, omit for tokens that never expire
    ],

    'middlewares' => [
        VerifyCsrfToken::class,     // global middleware
    ],

    'session' => [],                // options passed to session_start()
    'views' => ['path' => null],    // defaults to <root>/views
];
```

The database connection is opened on first use, so pages that never touch the database do not connect.

## Routing

```php
$router = app()->router;

$router->get('/', 'home');                                   // renders views/home.php
$router->get('/users', [UsersController::class, 'index']);
$router->post('/users', [UsersController::class, 'store'])->middleware(Authenticate::class);
$router->put('/users/{id}', [UsersController::class, 'update']);
$router->delete('/users/{id}', [UsersController::class, 'destroy']);
$router->get('/users/{user}', fn (User $user) => $user)->name('users.show');

$router->group(['prefix' => '/admin', 'middleware' => [Authenticate::class]], function ($router) {
    $router->get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
});

route('users.show', ['user' => 5, 'tab' => 'posts']); // /users/5?tab=posts
```

Handler arguments are resolved by reflection. A parameter typed `Request` receives the request, route parameters are matched by name and then by position and cast to `int`, `float` or `bool`, and a parameter typed with a `Model` subclass is loaded by primary key or ends in a 404.

Handlers may return a string, an array or `JsonSerializable` object for JSON, or a `Response`. A request for a known path with the wrong method receives a 405, `HEAD` falls back to `GET` and trailing slashes are ignored.

## Controllers and middleware

```php
class UsersController extends Controller
{
    protected array $middlewares = [
        Authenticate::class => ['store', 'update', 'destroy'],
        Throttle::class,        // applies to every action
    ];

    public function store(Request $request): Response
    {
        $data = $request->validate([
            'name' => 'required|min:2|max:100',
            'email' => 'required|email|unique:' . User::class,
            'password' => 'required|min:8|confirmed',
        ]);

        User::create($data);

        return redirect(route('users.index'))->with('status', 'User created');
    }
}
```

Middleware implements `Contracts\Middleware`:

```php
class Authenticate implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (is_null(app()->user)) {
            return redirect('/login');
        }

        return $next($request);
    }
}
```

## Requests, validation and responses

`Request` exposes `all()`, `get()`, `input()`, `only()`, `except()`, `has()`, `header()`, `method()`, `path()`, `wantsJson()` and `isJson()`. Input is kept raw, so escape it on output with `e()`.

`$request->validate()` returns the validated attributes or throws a `ValidationException`. The application turns that into a redirect back with the errors and old input flashed, or into a 422 JSON response when the client accepts JSON. Password fields and the Csrf token are never flashed. In views use `old('email')` and `request()->getError('email')`.

Available rules: `required`, `nullable`, `string`, `number`, `numeric`, `integer`, `boolean`, `email`, `min:n`, `max:n`, `gte:n`, `lte:n`, `in:a,b`, `regex:/.../`, `match:field`, `confirmed` and `unique:Model[,column[,ignoredId]]`. Register your own with `Validator::extend('even', fn ($attribute, $value) => $value % 2 === 0, 'The :attribute must be even')` or a class implementing `Contracts\Rule`.

Responses: `response('text', 201)`, `Response::json($data)`, `redirect('/path')`, `back()`, and `abort(404)` to raise an HTTP error.

## Views

Templates live in `views/` and are plain PHP files rendered in an isolated scope. `view('users.show', ['user' => $user])` renders `views/users/show.php`. Set a layout with `app()->view->layout('main')`; the layout in `views/layouts/main.php` receives the page as `$content`. Error pages are looked up as `views/_errors/{status}.php`, then `views/_errors.php`, then the built in template. With `debug` enabled the original exception is shown on 500 pages.

## Models

```php
class User extends Model implements Authenticatable
{
    use AuthorizeTokens;

    protected array $fillable = ['name', 'email', 'password'];
    protected array $hidden = ['password'];

    public function table(): string
    {
        return 'users';
    }
}

$user = User::create(['name' => 'Aziz', 'email' => 'a@b.co', 'password' => password_hash('secret', PASSWORD_DEFAULT)]);
$user->update(['name' => 'Azizbek']);
User::findOne(['email' => 'a@b.co']);
User::findOrFail(['id' => 5]);
User::findAll(['role' => null]);
User::all();
$user->toArray();       // without hidden attributes
$user->delete();
```

Every query returns model instances. Models are `JsonSerializable` and implement `ArrayAccess`, so `$user['name']` keeps working in templates. Column names are validated as plain identifiers and values are bound with their PDO types.

## Migrations

Migration files return an object implementing `Contracts\Migration` and are applied in file name order. The runner targets MySQL.

```php
// migrations/2024_01_01_create_users_table.php
return new class implements Migration {
    public function up(): string
    {
        return 'CREATE TABLE users (...)';
    }

    public function down(): string
    {
        return 'DROP TABLE users';
    }
};
```

```php
// migrate.php
$migrator = $app->database->migrator();

match (MigrationFlags::tryFrom($argv[1] ?? 'up')) {
    MigrationFlags::Down => $migrator->rollback(),
    MigrationFlags::Refresh => $migrator->refresh(),
    default => $migrator->run(),
};
```

Each migration is recorded as soon as it succeeds and removed again when rolled back.

## Sessions, Csrf and authentication

Sessions start with HttpOnly, SameSite and strict mode cookies, and the Secure flag on HTTPS. `session()->set()`, `get()`, `setFlash()` and `getFlash()` cover the basics; flashed values survive exactly one following request. `session()->regenerate()` rotates the id and `invalidate()` wipes everything.

`VerifyCsrfToken` accepts the token from a `_token` field or an `X-CSRF-TOKEN` header. Extend it and set `$except` to skip paths such as `/webhooks/*`.

The `AuthorizeTokens` trait implements `Contracts\Authenticatable`. `$user->authorizeToken()` logs in by storing a hashed token in `access_tokens` and regenerating the session; `app()->user` is loaded on the next request, `$user->logout()` deletes the token and invalidates the session. The `access_tokens` table needs `user_id`, `token`, `device` and, for `auth.token_lifetime`, a nullable `expires_at` column.

## Forms

```php
<?php $form = new Form(); ?>
<?= $form->start(route('users.update', ['id' => $user->id]), 'PUT') ?>
    <?= $form->input('name')->required()->placeholder('Full name') ?>
    <?= $form->input('email', 'Email address')->email() ?>
    <?= $form->input('password')->password() ?>
    <?= $form->select('role', ['admin' => 'Admin', 'user' => 'User']) ?>
    <?= $form->checkbox('newsletter', 'Send me news') ?>
    <?= $form->textarea('bio') ?>
<?= $form->end() ?>
```

`start()` emits the Csrf field for non-GET forms and a `_method` field for PUT, PATCH and DELETE. Fields render old input and validation errors with Bootstrap classes, and escape everything they print.

## Helpers

`app()`, `basePath()`, `config()`, `env()`, `request()`, `session()`, `view()`, `route()`, `redirect()`, `back()`, `response()`, `abort()`, `old()`, `csrf_token()`, `csrf_field()`, `method_field()`, `e()` and `dd()`.

## Development

```bash
composer install
composer test       # PHPUnit
composer analyse    # PHPStan level 6
```

## Upgrading from earlier versions

- `redirect()` and `back()` return a `Response` and must be returned from the handler; `Application::run()` no longer calls `exit`.
- `Request::validate()` returns the validated data and throws `ValidationException` instead of redirecting itself.
- Middleware implements `Contracts\Middleware` with `handle(Request $request, Closure $next): Response` and returns `$next($request)` to continue.
- Interfaces moved to the `Contracts` namespace: `Middleware`, `Migration`, `Router`, `Session`, `View`, plus the new `Authenticatable` and `Rule`.
- The user model must implement `Contracts\Authenticatable`; `config('auth.model')` replaces `config('user')`, which still works.
- `Model::all()` and `findAll()` return model instances instead of arrays.
- Access tokens are stored hashed, so existing logins are invalidated once.
- `Form::text()` was renamed to `textarea()` and `Form::start()` adds the Csrf field itself.
- `env()` returns `null` instead of an empty string for missing variables and casts `true`, `false` and `null`.
- Session keys are prefixed with an underscore and the Csrf token no longer shares a key with the auth token.
