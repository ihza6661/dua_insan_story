<?php

namespace App\DataTransferObjects;

/**
 * Class ProductData
 *
 * Data Transfer Object for product operations.
 */
class ProductData
{
    /**
     * ProductData constructor.
     */
    public function __construct(
        public readonly int $categoryId,
        public readonly string $name,
        public readonly string $description,
        public readonly float $basePrice,
        public readonly int $weight,
        public readonly int $minOrderQuantity,
        public readonly bool $isActive = true,
    ) {}

    /**
     * Create from array (typically from validated request).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            categoryId: (int) $data['category_id'],
            name: $data['name'],
            description: $data['description'],
            basePrice: (float) $data['base_price'],
            weight: (int) $data['weight'],
            minOrderQuantity: (int) ($data['min_order_quantity'] ?? 1),
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }

    /**
     * Convert to array for database operations.
     */
    public function toArray(): array
    {
        return [
            'category_id' => $this->categoryId,
            'name' => $this->name,
            'description' => $this->description,
            'base_price' => $this->basePrice,
            'weight' => $this->weight,
            'min_order_quantity' => $this->minOrderQuantity,
            'is_active' => $this->isActive,
        ];
    }
}
