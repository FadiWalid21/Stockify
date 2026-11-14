
<?php

namespace App\Domains\Company\Services;

use App\Domains\AccessControl\Services\DefaultRoleProvisioner;
use App\Domains\Company\Contracts\TenantContextInterface;
use App\Domains\Company\Models\Company;
use App\Domains\User\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterCompanyService
{
    public function __construct(
        private DefaultRoleProvisioner $roleProvisioner,
        private TenantContextInterface $tenantContext
    ) {}

    public function register(
        array $companyData,
        array $userData
    ): User {
        return DB::transaction(function () use ($companyData, $userData) {
            $company = Company::create($companyData);

            $this->tenantContext->setCompanyId($company->id);

            try {
                // Create the first user of the company.
                $user = $company->users()->create($userData);

                // Create the company's default roles and permissions.
                $this->roleProvisioner->provision($company);

                // Find the newly created Owner role.
                $ownerRole = $company->roles()
                    ->where('key', 'owner')
                    ->firstOrFail();

                // Assign the Owner role with its company ID.
                $user->roles()->attach($ownerRole->id, [
                    'company_id' => $company->id,
                ]);

                return $user;
            } finally {
                $this->tenantContext->clearCompanyId();
            }
        });
    }
}
