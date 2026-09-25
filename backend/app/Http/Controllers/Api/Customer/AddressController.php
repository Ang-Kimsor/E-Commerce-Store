<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Address\StoreAddressRequest;
use App\Http\Requests\Customer\Address\UpdateAddressRequest;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($request->user()->addresses()->latest()->paginate(15));
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $makeDefault = (bool) ($validated['is_default'] ?? false);

        $data = [
            'label' => $validated['label'] ?? null,
            'name' => $validated['name'] ?? null,
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

        $address = $request->user()->addresses()->create($data);

        if ($makeDefault) {
            $this->markAsDefault($request->user()->id, $address->id);
        }

        return response()->json($address, 201);
    }

    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        $this->authorizeAddress($request, $address);

        $validated = $request->validated();
        $makeDefault = (bool) ($validated['is_default'] ?? false);

        $data = [
            'label' => $validated['label'] ?? null,
            'name' => $validated['name'] ?? null,
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
            $this->markAsDefault($request->user()->id, $address->id);
        }

        return response()->json($address);
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        $this->authorizeAddress($request, $address);

        $address->delete();

        return response()->json(status: 204);
    }

    protected function authorizeAddress(Request $request, Address $address): void
    {
        abort_unless($address->customer_id === $request->user()->id, 403);
    }

    protected function markAsDefault(int $userId, int $addressId): void
    {
        Address::where('customer_id', $userId)->update(['is_default' => false]);

        Address::where('customer_id', $userId)->whereKey($addressId)->update(['is_default' => true]);
    }
}
