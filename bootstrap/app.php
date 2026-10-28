<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
| cPanel document root is public_html. When .qpos-web-root sits beside
| artisan, that folder is the website and there is no /public URL.
| A marker in the sibling public_html folder is the split layout.
| Local installs have no marker, so they keep the public directory.
*/
$markerInside = $app->basePath().DIRECTORY_SEPARATOR.'.qpos-web-root';
$cpanelWebRoot = dirname($app->basePath()).DIRECTORY_SEPARATOR.'public_html';
if (is_file($markerInside)) {
    $app->instance('path.public', $app->basePath());
} elseif (is_file($cpanelWebRoot.DIRECTORY_SEPARATOR.'.qpos-web-root')) {
    $app->instance('path.public', $cpanelWebRoot);
}

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
