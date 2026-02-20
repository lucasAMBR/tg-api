<?php

namespace App\Http\Controllers\Address;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Address\UpdateAddressRequest;
use App\Models\Address;
use App\Services\Address\AddressService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected AddressService $addressService){}

    public function store(StoreAddressRequest $request)
    {
        $new_address = $this->addressService->store($request->validated());

        return ApiResponse::success($new_address, "Address registered with success!", 201);
    }

    public function update(Address $address, UpdateAddressRequest $request)
    {
        $this->authorize('update', $address);

        $address = $this->addressService->update($request->validated(), $address);

        return ApiResponse::success($address, "Address updated with success!");
    }

    public function delete(Address $address)
    {
        $this->authorize('delete', $address);

        $this->addressService->delete($address);

        return ApiResponse::success(message: "Address deleted with success");
    }

}
