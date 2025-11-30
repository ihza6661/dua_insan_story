<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RajaOngkirService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RajaOngkirController extends Controller
{
    protected $rajaOngkirService;

    public function __construct(RajaOngkirService $rajaOngkirService)
    {
        $this->rajaOngkirService = $rajaOngkirService;
    }

    public function getProvinces()
    {
        $response = $this->rajaOngkirService->getProvinces();
        if (! isset($response['rajaongkir'])) {
            Log::error('Raja Ongkir API Error: "rajaongkir" key not found in response.', ['response' => $response]);

            return response()->json(['error' => 'Failed to retrieve provinces from Raja Ongkir.'], 500);
        }

        return response()->json($response['rajaongkir']);
    }

    public function getCities(Request $request)
    {
        $provinceId = $request->query('province_id');
        $response = $this->rajaOngkirService->getCities($provinceId);
        if (! isset($response['rajaongkir'])) {
            Log::error('Raja Ongkir API Error: "rajaongkir" key not found in response.', ['response' => $response]);

            return response()->json(['error' => 'Failed to retrieve cities from Raja Ongkir.'], 500);
        }

        return response()->json($response['rajaongkir']);
    }

    public function getSubdistricts(Request $request)
    {
        $cityId = $request->query('city_id');
        $response = $this->rajaOngkirService->getSubdistricts($cityId);
        if (! isset($response['rajaongkir'])) {
            Log::error('Raja Ongkir API Error: "rajaongkir" key not found in response.', ['response' => $response]);

            return response()->json(['error' => 'Failed to retrieve subdistricts from Raja Ongkir.'], 500);
        }

        return response()->json($response['rajaongkir']);
    }

    public function calculateCost(Request $request)
    {
        $request->validate([
            'destination' => 'required|numeric',
            'weight' => 'required|numeric|min:1',
            'courier' => 'required|string',
        ]);

        $originCityId = config('rajaongkir.origin_city_id');
        $destinationCityId = $request->input('destination');
        $weight = $request->input('weight');
        $courier = $request->input('courier');

        if (! $originCityId) {
            return response()->json(['error' => 'Origin city is not configured.'], 500);
        }

        $response = $this->rajaOngkirService->getCost(
            $originCityId,
            $destinationCityId,
            $weight,
            $courier
        );

        if (! isset($response['data'])) {
            Log::error('Raja Ongkir API Error: "data" key not found in response.', ['response' => $response]);

            return response()->json(['error' => 'Failed to calculate shipping cost from Raja Ongkir.'], 500);
        }

        $transformedData = [];
        if (is_array($response['data'])) {
            foreach ($response['data'] as $service) {
                $transformedData[] = [
                    'service' => $service['service'],
                    'description' => $service['description'],
                    'cost' => [
                        [
                            'value' => $service['cost'],
                            'etd' => $service['etd'],
                            'note' => '',
                        ],
                    ],
                ];
            }
        }

        $transformedResponse = [
            'rajaongkir' => [
                'results' => [
                    [
                        'code' => $courier,
                        'name' => $courier,
                        'costs' => $transformedData,
                    ],
                ],
            ],
        ];

        return response()->json($transformedResponse)->header('Access-Control-Allow-Origin', '*');
    }
}
