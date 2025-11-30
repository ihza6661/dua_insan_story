<?php

namespace App\Services;

use App\Models\AddOn;
use Exception;

class AddOnService
{
    public function createAddOn(array $data): AddOn
    {
        return AddOn::create($data);
    }

    public function updateAddOn(AddOn $addOn, array $data): AddOn
    {
        $addOn->update($data);

        return $addOn;
    }

    public function deleteAddOn(AddOn $addOn): void
    {
        if ($addOn->products()->exists()) {
            throw new Exception('Item tambahan tidak dapat dihapus karena terhubung dengan produk.');
        }
        $addOn->delete();
    }
}
