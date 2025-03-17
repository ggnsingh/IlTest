<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductCollection;
use App\Models\Product;
use App\Services\ProductService;
use Exception;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of the products.
     *
     * @return ProductCollection
     */
    public function index(): ProductCollection
    {
        $products = $this->productService->getAllProducts();

        return new ProductCollection($products);
    }

    /**
     * Store a newly created product in storage.
     *
     * @param StoreProductRequest $request
     * @return ProductResource
     */
    public function store(StoreProductRequest $request): ProductResource
    {
        $product = $this->productService->createProduct($request->validated());

        return new ProductResource($product);
    }

    /**
     * Display the specified product.
     *
     * @param Product $product
     * @return ProductResource
     */
    public function show(Product $product): ProductResource
    {
        return new ProductResource($product);
    }

    /**
     * Update the specified product in storage.
     *
     * @param UpdateProductRequest $request
     * @param Product $product
     * @return ProductResource
     */
    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product = $this->productService->updateProduct($product, $request->validated());

        return new ProductResource($product);
    }

    /**
     * Remove the specified product from storage.
     *
     * @param Product $product
     * @return Response
     * @throws Exception
     */
    public function destroy(Product $product): Response
    {
        $this->productService->deleteProduct($product);

        return response()->noContent();
    }
}
