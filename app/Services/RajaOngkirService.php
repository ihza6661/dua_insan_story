<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RajaOngkirService
{
    private $apiKey;
    protected $baseUrl;
    protected $accountType;

    public function __construct()
    {
        $this->apiKey = config('rajaongkir.api_key');
        $this->baseUrl = config('rajaongkir.base_url');
        $this->accountType = config('rajaongkir.account_type');
    }

    protected function request($method, $path, $data = [])
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');

        $response = Http::withHeaders([
            'key' => $this->apiKey,
        ])->asForm();

        if (strtolower($method) === 'get' && !empty($data)) {
            $response = $response->get($url, $data);
        } else {
            $response = $response->$method($url, $data);
        }

        Log::info('Raja Ongkir API Request URL: ' . $url);
        Log::info('Raja Ongkir API Response Status: ' . $response->status());
        Log::info('Raja Ongkir API Raw Response Body: ' . $response->body());

        return $response->json();
    }

    public function getProvinces()
    {
        return $this->request('get', 'province');
    }

    public function getCities($provinceId = null)
    {
        $data = [];
        if ($provinceId) {
            $data['province'] = $provinceId;
        }
        return $this->request('get', 'city', $data);
    }

    public function getSubdistricts($cityId = null)
    {
        if ($this->accountType === 'starter') {
            return ['error' => 'Subdistrict feature is not available for Starter account.'];
        }

        $data = [];
        if ($cityId) {
            $data['city'] = $cityId;
        }
        return $this->request('get', 'subdistrict', $data);
    }

    public function getCost($origin, $destination, $weight, $courier, $destinationType = null)
    {
        $data = [
            'origin' => $origin,
            'destination' => $destination,
            'weight' => $weight, // in grams
            'courier' => $courier, // e.g., 'jne', 'pos', 'tiki'
        ];

        if ($destinationType) {
            $data['destination_type'] = $destinationType;
        }

        return $this->request('post', 'calculate/domestic-cost', $data);
    }

    public function getCityByPostalCode(string $postalCode)
    {
        // This is a conceptual implementation. RajaOngkir's public API might not directly support this.
        // This assumes an endpoint or a way to resolve postal code to city_id.
        // A more robust solution might involve a local database of postal codes to city IDs.
        $cities = $this->getCities();
        if (isset($cities['rajaongkir']['results'])) {
            foreach ($cities['rajaongkir']['results'] as $city) {
                if ($city['postal_code'] == $postalCode) {
                    return $city;
                }
            }
        }
        return null;
    }
}