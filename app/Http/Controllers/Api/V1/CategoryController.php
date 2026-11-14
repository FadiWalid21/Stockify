<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Category\Models\Category;
use App\Domains\Category\Services\CategoryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService
    ) {}

    /**
     * Display a paginated list of categories.
     */
    public function index(Request $request): JsonResponse
    {
        $categories = $this->categoryService->getAll(
            $request->query()
        );

        return CategoryResource::collection($categories)->response();
    }

    /**
     * Store a new category.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create(
            $request->validated()
        );

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display a specific category.
     */
    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category);
    }

    /**
     * Update an existing category.
     */
    public function update(
        UpdateCategoryRequest $request,
        Category $category
    ): CategoryResource {
        $category = $this->categoryService->update(
            $category,
            $request->validated()
        );

        return new CategoryResource($category);
    }

    /**
     * Delete a category.
     */
    public function destroy(Category $category): JsonResponse
    {
        $this->categoryService->delete($category);

        return response()->json(null, 204);
    }
}
