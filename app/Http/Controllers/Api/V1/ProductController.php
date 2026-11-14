<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Product\Models\Product;
use App\Domains\Product\Services\ProductService;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    public function index(ProductIndexRequest $request)
    {
        $products = $this->productService->getAll(
            $request->validated()
        );

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create(
            $request->validated()
        );

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product);
    }

    public function update(
        UpdateProductRequest $request,
        Product $product
    ): ProductResource {
        $product = $this->productService->update(
            $product,
            $request->validated()
        );

        return new ProductResource($product);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return response()->json(null, 204);
    }

    public function deactivate(Product $product): ProductResource
    {
        $product = $this->productService->deactivate($product);

        return new ProductResource($product);
    }

    public function activate(Product $product): ProductResource
    {
        $product = $this->productService->activate($product);

        return new ProductResource($product);
    }
}