<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\RoleOrPermissionMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withProviders([
        App\Providers\AuthServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        //
        // PPM Custom Middleware Registration
        // FAZA A: Spatie Setup + Middleware
        //
        
        // Role-based middleware
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'admin' => AdminMiddleware::class,
        ]);
        
        // Global middleware dla wszystkich requests
        $middleware->web(append: [
            // Add any global web middleware here if needed
        ]);
        
        // API middleware
        $middleware->api(prepend: [
            // API-specific middleware
        ]);
        
        // Authenticated middleware group
        $middleware->group('auth', [
            'auth:sanctum', // Sanctum auth dla API
        ]);
        
        // Admin middleware group (role-based)
        $middleware->group('admin', [
            'auth',
            'role:Admin',
        ]);
        
        // Manager middleware group (hierarchical)
        $middleware->group('manager', [
            'auth', 
            'role:Admin,Manager',
        ]);
        
        // Editor middleware group
        $middleware->group('editor', [
            'auth',
            'role:Admin,Manager,Editor', 
        ]);
        
        // API access group
        $middleware->group('api_access', [
            'auth:sanctum',
            'permission:api.access',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
        // PPM Exception Handling
        // FAZA A: Spatie Setup + Middleware
        //
        
        // Handle authentication exceptions
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Unauthenticated',
                    'message' => 'You must be logged in to access this resource.'
                ], 401);
            }
            
            return redirect()->guest(route('login'));
        });
        
        // Handle authorization exceptions (403)
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Unauthorized', 
                    'message' => $e->getMessage() ?: 'You do not have permission to access this resource.'
                ], 403);
            }
            
            return redirect()->route('dashboard')
                ->withErrors(['access' => 'You do not have permission to access this resource.']);
        });
        
        // Handle role/permission exceptions from Spatie
        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Permission denied',
                    'message' => $e->getMessage()
                ], 403);
            }
            
            return redirect()->route('dashboard')
                ->withErrors(['access' => 'You do not have the required permissions.']);
        });
    })
    ->create();
