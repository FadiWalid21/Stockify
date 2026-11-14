<?php

namespace App\Http\Middleware;

use App\Domains\Company\Contracts\TenantContextInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetTenantContext
{
    public function __construct(
        private TenantContextInterface $tenantContext
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->company_id) {
            abort(403, 'You are not associated with a company.');
        }

        $this->tenantContext->setCompanyId($user->company_id);

        try {
            return $next($request);
        } finally {
            $this->tenantContext->clearCompanyId();
        }
    }
}
