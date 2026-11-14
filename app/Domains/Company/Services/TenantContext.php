<?php

namespace App\Domains\Company\Services;

use App\Domains\Company\Contracts\TenantContextInterface;

final class TenantContext implements TenantContextInterface
{
    private ?int $companyId = null;

    public function setCompanyId(int $companyId): void
    {
        $this->companyId = $companyId;
    }

    public function getCompanyId(): ?int
    {
        return $this->companyId;
    }

    public function hasCompany(): bool
    {
        return $this->companyId !== null;
    }

    public function clearCompanyId(): void
    {
        $this->companyId = null;
    }
}