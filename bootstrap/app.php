<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RejectInvalidJson;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->api(prepend: [
            RejectInvalidJson::class,
        ]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // API errors are always JSON, even when the client did not send "Accept: application/json".
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api', 'api/*') || $request->expectsJson());

        // A short 404 message for the API, e.g. {"message": "Item not found."}
        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if (! $request->is('api', 'api/*')) {
                return null;
            }

            $previous = $exception->getPrevious();
            $resourceName = $previous instanceof ModelNotFoundException ? class_basename($previous->getModel()) : 'Resource';

            return response()->json(['message' => "{$resourceName} not found."], 404);
        });
    })->create();
