<?php

namespace App\Services;

use App\Models\Attribute;
use Exception;

class AttributeService
{
    public function createAttribute(array $data): Attribute
    {
        return Attribute::create($data);
    }

    public function updateAttribute(Attribute $attribute, array $data): Attribute
    {
        $attribute->update($data);

        return $attribute;
    }

    public function deleteAttribute(Attribute $attribute): void
    {
        if ($attribute->attributeValues()->exists()) {
            throw new Exception('Atribut tidak dapat dihapus karena memiliki nilai-nilai terkait.');
        }
        $attribute->delete();
    }
}
