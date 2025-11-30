<?php

namespace App\Services;

use App\Models\ProductCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductCategoryService
{
    public function createCategory(array $data): ProductCategory
    {
        $data['slug'] = Str::slug($data['name']);

        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $data['image'] = $data['image']->store('product-categories', 'public');
        }

        return ProductCategory::create($data);
    }

    public function updateCategory(ProductCategory $category, array $data): ProductCategory
    {
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $data['image']->store('product-categories', 'public');
        }

        $category->update($data);

        return $category->fresh();
    }
}
