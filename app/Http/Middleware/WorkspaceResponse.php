<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Transport adaptation only: existing routes, policies and Blade remain authoritative. */
class WorkspaceResponse
{
    public function handle(Request $request, Closure $next)
    {
        $request->attributes->set('intravault.settings',[]);
        try { $response = $next($request); }
        finally { $request->attributes->remove('intravault.settings'); }
        if ($request->header('X-IntraVault') === 'workspace' && $request->isMethod('GET')) {
            $response->headers->set('Cache-Control','private, no-store');
            $response->setVary(['X-IntraVault','X-IntraVault-Fragment','X-IntraVault-Group'],false);
        }
        if ($request->header('X-IntraVault') === 'workspace' && !$request->isMethod('GET')
            && in_array($response->getStatusCode(), [302, 303], true)) {
            return response('', 204)->header('X-IntraVault-Location', $response->headers->get('Location'));
        }
        return $response;
    }
}
