<?php

use App\Http\Middleware\EnsureUserType;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserType::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (QueryException $exception, Request $request) {
            $previous = $exception->getPrevious();

            if (
                $previous instanceof \PDOException
                && str_starts_with((string) $previous->getCode(), '2002')
            ) {
                return response()->view('errors.database', status: 503);
            }

            return null;
        });
    })->create();
