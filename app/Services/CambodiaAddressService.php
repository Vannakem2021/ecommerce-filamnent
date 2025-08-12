<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CambodiaAddressService
{
    private const POSTAL_CODES_URL = 'https://cdn.jsdelivr.net/gh/seanghay/cambodia-postal-codes@main/cambodia-postal-codes.json';
    private const CACHE_KEY = 'cambodia_postal_codes';
    private const CACHE_DURATION = 60 * 60 * 24; // 24 hours

    /**
     * Get all postal codes data from cache or remote source.
     */
    private function getPostalCodesData()
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_DURATION, function () {
            try {
                $response = Http::timeout(30)->get(self::POSTAL_CODES_URL);
                
                if ($response->successful()) {
                    return $response->json();
                }
                
                Log::error('Failed to fetch Cambodia postal codes', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                return [];
            } catch (\Exception $e) {
                Log::error('Exception while fetching Cambodia postal codes', [
                    'message' => $e->getMessage()
                ]);
                
                return [];
            }
        });
    }

    /**
     * Get all provinces.
     */
    public function getProvinces()
    {
        $data = $this->getPostalCodesData();
        
        return collect($data)->map(function ($province) {
            return [
                'id' => $province['id'],
                'name_kh' => $province['name'],
                'name_en' => $this->translateProvinceName($province['name']),
                'districts_count' => count($province['districts'])
            ];
        })->sortBy('name_en')->values();
    }

    /**
     * Get districts for a specific province.
     */
    public function getDistricts($provinceId)
    {
        $data = $this->getPostalCodesData();
        
        $province = collect($data)->firstWhere('id', $provinceId);
        
        if (!$province) {
            return collect();
        }
        
        return collect($province['districts'])->map(function ($district) {
            return [
                'id' => $district['id'],
                'no' => $district['no'],
                'name_kh' => $district['location_kh'],
                'name_en' => $district['location_en'],
                'communes_count' => count($district['codes'])
            ];
        })->sortBy('name_en')->values();
    }

    /**
     * Get communes for a specific district.
     */
    public function getCommunes($districtId)
    {
        $data = $this->getPostalCodesData();
        
        foreach ($data as $province) {
            $district = collect($province['districts'])->firstWhere('id', $districtId);
            
            if ($district) {
                return collect($district['codes'])->map(function ($commune) {
                    return [
                        'name_kh' => $commune['km'],
                        'name_en' => $commune['en'],
                        'postal_code' => $commune['code']
                    ];
                })->sortBy('name_en')->values();
            }
        }
        
        return collect();
    }

    /**
     * Get postal code for a specific area.
     */
    public function getPostalCode($provinceId, $districtId, $communeName)
    {
        $communes = $this->getCommunes($districtId);
        
        $commune = $communes->firstWhere('name_kh', $communeName);
        
        return $commune ? $commune['postal_code'] : null;
    }

    /**
     * Search areas by query string.
     */
    public function searchAreas($query)
    {
        $data = $this->getPostalCodesData();
        $results = collect();
        
        foreach ($data as $province) {
            foreach ($province['districts'] as $district) {
                foreach ($district['codes'] as $commune) {
                    // Search in Khmer and English names
                    if (
                        stripos($commune['km'], $query) !== false ||
                        stripos($commune['en'], $query) !== false ||
                        stripos($district['location_kh'], $query) !== false ||
                        stripos($district['location_en'], $query) !== false ||
                        stripos($province['name'], $query) !== false
                    ) {
                        $results->push([
                            'province_id' => $province['id'],
                            'province_name_kh' => $province['name'],
                            'province_name_en' => $this->translateProvinceName($province['name']),
                            'district_id' => $district['id'],
                            'district_name_kh' => $district['location_kh'],
                            'district_name_en' => $district['location_en'],
                            'commune_name_kh' => $commune['km'],
                            'commune_name_en' => $commune['en'],
                            'postal_code' => $commune['code']
                        ]);
                    }
                }
            }
        }
        
        return $results->take(20); // Limit results
    }

    /**
     * Translate province name from Khmer to English (basic mapping).
     */
    private function translateProvinceName($khmerName)
    {
        $translations = [
            'រាជធានីភ្នំពេញ' => 'Phnom Penh',
            'ខេត្តបន្ទាយមានជ័យ' => 'Banteay Meanchey',
            'ខេត្តបាត់ដំបង' => 'Battambang',
            'ខេត្តកំពង់ចាម' => 'Kampong Cham',
            'ខេត្តកំពង់ឆ្នាំង' => 'Kampong Chhnang',
            'ខេត្តកំពង់ស្ពឺ' => 'Kampong Speu',
            'ខេត្តកំពង់ធំ' => 'Kampong Thom',
            'ខេត្តកំពត' => 'Kampot',
            'ខេត្តកណ្ដាល' => 'Kandal',
            'ខេត្តកោះកុង' => 'Koh Kong',
            'ខេត្តក្រចេះ' => 'Kratie',
            'ខេត្តមណ្ឌលគិរី' => 'Mondulkiri',
            'ខេត្តឧត្តរមានជ័យ' => 'Oddar Meanchey',
            'ខេត្តប៉ៃលិន' => 'Pailin',
            'ខេត្តព្រះវិហារ' => 'Preah Vihear',
            'ខេត្តព្រៃវែង' => 'Prey Veng',
            'ខេត្តពោធិ៍សាត់' => 'Pursat',
            'ខេត្តរតនគិរី' => 'Ratanakiri',
            'ខេត្តសៀមរាប' => 'Siem Reap',
            'ខេត្តព្រះសីហនុ' => 'Preah Sihanouk',
            'ខេត្តស្ទឹងត្រែង' => 'Stung Treng',
            'ខេត្តស្វាយរៀង' => 'Svay Rieng',
            'ខេត្តតាកែវ' => 'Takeo',
            'ខេត្តត្បូងឃ្មុំ' => 'Tboung Khmum',
            'ខេត្តកែប' => 'Kep'
        ];
        
        return $translations[$khmerName] ?? $khmerName;
    }

    /**
     * Clear the postal codes cache.
     */
    public function clearCache()
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Validate if a postal code exists.
     */
    public function isValidPostalCode($postalCode)
    {
        $data = $this->getPostalCodesData();
        
        foreach ($data as $province) {
            foreach ($province['districts'] as $district) {
                foreach ($district['codes'] as $commune) {
                    if ($commune['code'] === $postalCode) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
}
