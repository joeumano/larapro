<?php

namespace App\Http\Middleware;

use App\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class checkActiveCompany
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tmp = explode('/', URL::current());
        $alias = end($tmp);
        $company = Company::where('subdomain', $alias)->first();

        if ($company->active == 1) {
            return $next($request);
        } else {
            return redirect('/');
        }
    }
}
