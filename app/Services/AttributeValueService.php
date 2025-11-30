<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Exception;

class AttributeValueService
{
    public function createAttributeValue(Attribute $attribute, array $data): AttributeValue
    {
        return $attribute->attributeValues()->create($data);
    }

    public function updateAttributeValue(AttributeValue $value, array $data): AttributeValue
    {
        $value->update($data);

        return $value;
    }

    public function deleteAttributeValue(AttributeValue $value): void
    {
        if ($value->productVariants()->exists()) {
            throw new Exception('Nilai atribut tidak dapat dihapus karena digunakan oleh produk.');
        }
        $value->delete();
    }
}
