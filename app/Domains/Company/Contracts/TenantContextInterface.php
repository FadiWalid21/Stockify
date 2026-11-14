<?php

namespace App\Domains\Company\Contracts;

interface TenantContextInterface
{
    public function setCompanyId(int $companyId): void;

    public function getCompanyId(): ?int;

    public function hasCompany(): bool;

    public function clearCompanyId(): void;
}