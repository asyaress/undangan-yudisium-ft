<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureAdmin;
use App\Models\YudisiumPeriod;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
        );

        $middleware->alias([
            'admin' => EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if (! $request->is('checkin/search') && ! $request->is('checkin/confirm')) {
                return null;
            }

            $event = YudisiumPeriod::query()->find($request->input('event_id'));
            $parameters = $event ? ['slug' => $event->slug] : [];

            return redirect()
                ->route('checkin.form', $parameters)
                ->withInput($request->except('_token'))
                ->with('checkin_error', 'Sesi halaman sudah kedaluwarsa. Silakan masukkan NIM kembali.');
        });
    })->create();
