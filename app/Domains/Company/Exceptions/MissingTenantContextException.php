<?php

namespace App\Domains\Company\Exceptions;

use RuntimeException;

class MissingTenantContextException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'A company context is required to perform this operation.'
        );
    }
}