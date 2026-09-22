<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\{Exceptions,Middleware};
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use App\Http\Middleware\{ActiveAccount,AdminAccess,LabRequestForgery};
use Illuminate\Http\Request;
return Application::configure(basePath:dirname(__DIR__))
    ->withRouting(web:__DIR__.'/../routes/web.php',commands:__DIR__.'/../routes/console.php',health:'/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(replace:[PreventRequestForgery::class=>LabRequestForgery::class],append:[\App\Http\Middleware\WorkspaceResponse::class,ActiveAccount::class]);
        $middleware->alias(['system_admin'=>AdminAccess::class,'lab.auth'=>\App\Http\Middleware\LabAuthenticate::class]);
        $trusted=array_filter(explode(',',env('TRUSTED_PROXIES','')));
        if ($trusted) $middleware->trustProxies(at:$trusted);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function ($response) {
            if (in_array($response->getStatusCode(),[403,419])) {
                App\Services\Audit::record('permission.denied','route',request()->path(),'denied',['status'=>$response->getStatusCode()]);
            }
            return $response;
        });
    })->create();
