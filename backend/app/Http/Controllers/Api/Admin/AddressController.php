<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Address\StoreAdminAddressRequest;
use App\Http\Requests\Admin\Address\UpdateAdminAddressRequest;
use App\Models\Address;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    public function store(StoreAdminAddressRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $makeDefault = (bool) ($validated['is_default'] ?? false);
        $data = [
            'customer_id' => $validated['customer_id'],
            'label' => $validated['label'] ?? null,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'address_line_1' => $validated['address_line_1'] ?? null,
            'address_line_2' => $validated['address_line_2'] ?? null,
            'village' => $validated['village'] ?? null,
            'commune' => $validated['commune'] ?? null,
            'district' => $validated['district'] ?? null,
            'province' => $validated['province'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'is_default' => $makeDefault,
        ];
        $address = Address::create($data);
        if ($makeDefault) {
            Address::where('customer_id', $address->customer_id)
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }
        return response()->json($address, 201);
    }

    public function update(UpdateAdminAddressRequest $request, Address $address): JsonResponse
    {
        $validated = $request->validated();
        $makeDefault = (bool) ($validated['is_default'] ?? false);
        $data = [
            'label' => $validated['label'] ?? null,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'address_line_1' => $validated['address_line_1'] ?? null,
            'address_line_2' => $validated['address_line_2'] ?? null,
            'village' => $validated['village'] ?? null,
            'commune' => $validated['commune'] ?? null,
            'district' => $validated['district'] ?? null,
            'province' => $validated['province'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'is_default' => $makeDefault,
        ];
        $address->update($data);
        if ($makeDefault) {
            Address::where('customer_id', $address->customer_id)
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }
        return response()->json($address);
    }

    public function destroy(Address $address): JsonResponse
    {
        $address->delete();
        return response()->json(null, 204);
    }
}
