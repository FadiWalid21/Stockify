<?php

namespace App\Support\Query;

class Pagination
{
    public const DEFAULT_PER_PAGE = 15;

    public const MAX_PER_PAGE = 100;

    public static function perPage(?int $requested = null): int
    {
        $perPage = $requested ?? self::DEFAULT_PER_PAGE;

        return min(
            max($perPage, 1),
            self::MAX_PER_PAGE
        );
    }
}