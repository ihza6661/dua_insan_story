<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\RajaOngkirService;

class RajaOngkirController extends Controller
{
    protected $rajaOngkirService;

    public function __construct(RajaOngkirService $rajaOngkirService)
    {
        $this->rajaOngkirService = $rajaOngkirService;
    }

    public function getProvinces()
    {
        $provinces = $this->rajaOngkirService->getProvinces();

        if ($provinces) {
            return response()->json(['success' => true, 'data' => $provinces]);
        } else {
            return response()->json(['success' => false, 'message' => 'Failed to retrieve provinces'], 500);
        }
    }

    public function getCities(string $provinceId)
    {
        $cities = $this->rajaOngkirService->getCities($provinceId);

        if ($cities) {
            return response()->json(['success' => true, 'data' => $cities]);
        } else {
            return response()->json(['success' => false, 'message' => 'Failed to retrieve cities'], 500);
        }
    }
}
