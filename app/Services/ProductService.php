<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Class ProductService
 *
 * Business logic layer for Product operations.
 */
class ProductService
{
    /**
     * ProductService constructor.
     */
    public function __construct(
        protected ProductRepositoryInterface $productRepository
    ) {}

    /**
     * Create a new product.
     */
    public function createProduct(array $validatedData): Product
    {
        return $this->productRepository->create($validatedData);
    }

    /**
     * Update an existing product.
     */
    public function updateProduct(Product $product, array $validatedData): Product
    {
        return $this->productRepository->update($product, $validatedData);
    }

    /**
     * Delete a product.
     *
     * @throws Exception
     */
    public function deleteProduct(Product $product): void
    {
        // Check for dependencies
        if ($this->productRepository->hasDependencies($product)) {
            throw new Exception('Produk tidak dapat dihapus karena sudah ada dalam pesanan atau keranjang belanja pelanggan.');
        }

        // Load relationships
        $product->load('variants.images');

        // Delete all variant images
        foreach ($product->variants as $variant) {
            foreach ($variant->images as $image) {
                $this->deleteProductImage($image);
            }
        }

        // Delete the product
        $this->productRepository->delete($product);
    }

    /**
     * Add image to product variant.
     */
    public function addImageToVariant(ProductVariant $variant, UploadedFile $imageFile, array $data): ProductImage
    {
        // Ensure the product-images directory exists
        $directory = 'product-images';
        $disk = Storage::disk('public');

        if (! $disk->exists($directory)) {
            $disk->makeDirectory($directory, 0777, true);
        }

        $path = $imageFile->store($directory, 'public');

        // Unset featured flag from other images if this is featured
        if (! empty($data['is_featured'])) {
            $variant->images()->where('is_featured', true)->update(['is_featured' => false]);
        }

        return $variant->images()->create([
            'image' => $path,
            'alt_text' => $data['alt_text'] ?? null,
            'is_featured' => $data['is_featured'] ?? false,
        ]);
    }

    /**
     * Delete a product image.
     */
    public function deleteProductImage(ProductImage $image): void
    {
        Storage::disk('public')->delete($image->image);
        $image->delete();
    }

    /**
     * Get paginated active products with filters (delegated to repository).
     */
    public function getPaginatedActiveProducts(
        ?string $searchTerm = null,
        ?string $categorySlug = null,
        ?string $minPrice = null,
        ?string $maxPrice = null,
        ?string $sort = 'latest'
    ): LengthAwarePaginator {
        $filters = [
            'search' => $searchTerm,
            'category_slug' => $categorySlug,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'sort' => $sort,
        ];

        return $this->productRepository->getPaginatedActiveProducts($filters, 10);
    }

    /**
     * Find publicly visible product by ID or slug.
     */
    public function findPubliclyVisibleProduct(int|string $identifier): Product
    {
        // If identifier is numeric, treat as ID
        if (is_numeric($identifier)) {
            return $this->productRepository->findActiveProduct((int) $identifier, ['category', 'variants.images', 'template']);
        }
        
        // Otherwise treat as slug
        return $this->productRepository->findActiveProductBySlug($identifier, ['category', 'variants.images', 'template']);
    }
}
