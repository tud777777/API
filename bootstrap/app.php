<?php

use App\Http\Middleware\CorsMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(CorsMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AccessDeniedHttpException $e){
            return response()->json([
                'code' => 403,
                'message' => 'Forbidden for you'
            ],403);
        });
        $exceptions->render(function (NotFoundHttpException $e){
            return response()->json([
                'code' => 404,
                'message' => 'Not Found'
            ],404);
        });
        $exceptions->render(function (AuthenticationException $e){
            return response()->json([
                'message' => 'Login failed'
            ],403);
        });
        $exceptions->render(function (RouteNotFoundException $e){
            return response()->json([
                'message' => 'Login failed'
            ],403);
        });
        $exceptions->render(function (ValidationException $e){
            return response()->json([
                'error' => [
                    'code' => 422,
                    'message' => 'Validation error',
                    'errors' => $e->errors()
                ]
            ],422);
        });
    })->create();
