<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Services\CambodiaAddressService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AddressController extends Controller
{
    public function __construct(
        private CambodiaAddressService $cambodiaAddressService
    ) {}

    /**
     * Get Cambodia provinces.
     */
    public function getProvinces(): JsonResponse
    {
        try {
            Log::info('Getting provinces...');
            $provinces = $this->cambodiaAddressService->getProvinces();
            Log::info('Provinces count: ' . $provinces->count());

            return response()->json($provinces);
        } catch (\Exception $e) {
            Log::error('Error getting provinces: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load provinces'], 500);
        }
    }

    /**
     * Get districts for a province.
     */
    public function getDistricts(Request $request): JsonResponse
    {
        $request->validate([
            'province_id' => 'required|integer',
        ]);

        $districts = $this->cambodiaAddressService->getDistricts($request->province_id);

        return response()->json($districts);
    }

    /**
     * Get communes for a district.
     */
    public function getCommunes(Request $request): JsonResponse
    {
        $request->validate([
            'district_id' => 'required|integer',
        ]);

        $communes = $this->cambodiaAddressService->getCommunes($request->district_id);

        return response()->json($communes);
    }

    /**
     * Get postal code for selected area.
     */
    public function getPostalCode(Request $request): JsonResponse
    {
        $request->validate([
            'province_id' => 'required|integer',
            'district_id' => 'required|integer',
            'commune_name' => 'required|string',
        ]);

        $postalCode = $this->cambodiaAddressService->getPostalCode(
            $request->province_id,
            $request->district_id,
            $request->commune_name
        );

        return response()->json(['postal_code' => $postalCode]);
    }

    /**
     * Search areas for autocomplete.
     */
    public function searchAreas(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $results = $this->cambodiaAddressService->searchAreas($request->query);

        return response()->json($results);
    }

    /**
     * Get user addresses.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['nullable', Rule::in(['shipping', 'billing'])],
        ]);

        $query = Address::where('user_id', auth()->id());

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $addresses = $query->orderBy('is_default', 'desc')
                          ->orderBy('created_at', 'desc')
                          ->get();

        return response()->json($addresses);
    }

    /**
     * Store a new address.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(Address::validationRules());

        $validated['user_id'] = auth()->id();

        $address = Address::create($validated);

        if ($validated['is_default'] ?? false) {
            $address->setAsDefault();
        }

        return response()->json([
            'message' => 'Address created successfully',
            'address' => $address->fresh()->load('user'),
        ], 201);
    }

    /**
     * Show a specific address.
     */
    public function show(Address $address): JsonResponse
    {
        // Ensure user can only view their own addresses
        if ($address->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        return response()->json($address);
    }

    /**
     * Update an existing address.
     */
    public function update(Request $request, Address $address): JsonResponse
    {
        // Ensure user can only update their own addresses
        if ($address->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate(Address::validationRules());

        $address->update($validated);

        if ($validated['is_default'] ?? false) {
            $address->setAsDefault();
        }

        return response()->json([
            'message' => 'Address updated successfully',
            'address' => $address->fresh()->load('user'),
        ]);
    }

    /**
     * Delete an address.
     */
    public function destroy(Address $address): JsonResponse
    {
        // Ensure user can only delete their own addresses
        if ($address->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $address->delete();

        return response()->json([
            'message' => 'Address deleted successfully',
        ]);
    }

    /**
     * Set an address as default.
     */
    public function setDefault(Address $address): JsonResponse
    {
        // Ensure user can only modify their own addresses
        if ($address->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $address->setAsDefault();

        return response()->json([
            'message' => 'Address set as default successfully',
            'address' => $address->fresh(),
        ]);
    }

    /**
     * Get default address for a specific type.
     */
    public function getDefault(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', Rule::in(['shipping', 'billing'])],
        ]);

        $address = Address::defaultForUserAndType(auth()->id(), $request->type)->first();

        if (!$address) {
            return response()->json([
                'message' => 'No default address found',
                'address' => null
            ], 404);
        }

        return response()->json($address);
    }
}
