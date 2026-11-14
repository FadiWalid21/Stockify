<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Unit\Services\UnitService;
use App\Http\Controllers\Controller;
use App\Http\Resources\UnitResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function __construct(
        private readonly UnitService $unitService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $units = $this->unitService->getAll(
            $request->query()
        );

        return UnitResource::collection($units)->response();
    }
}
