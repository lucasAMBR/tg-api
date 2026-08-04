<?php

namespace App\Http\Controllers\Address;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Address\DeleteAddressRequest;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Address\UpdateAddressRequest;
use App\Services\Address\AddressService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    public function __construct(protected AddressService $addressService){}

    #[Endpoint(operationId: 'storeAddress', title: 'Cadastrar endereço', description: '**operationId:** `storeAddress` — Cadastra (ou substitui) o endereço do perfil autenticado. Os dados de logradouro são obtidos a partir do `cep` em uma API externa de CEP; apenas `number` e `complement` vêm do corpo da requisição. Em **201**, `data` segue o schema **Address Resource** (`App\\Http\\Resources\\Addresses\\AddressResource`).')]
    public function store(StoreAddressRequest $request): JsonResponse
    {
        try {
            $new_address = $this->addressService->store($request->validated());

            return ApiResponse::success($new_address, "Address registered with success!", 201);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'showAuthUserAddress', title: 'Consultar endereço do usuário autenticado', description: '**operationId:** `showAuthUserAddress` — Retorna o endereço do perfil ativo do usuário autenticado. Em **200**, `data.has_address` indica se existe endereço cadastrado e `data.address` segue o schema **Address Resource** (`App\\Http\\Resources\\Addresses\\AddressResource`) ou é `null` quando não há endereço.')]
    public function showAuthUserAddress(): JsonResponse
    {
        try {
            $result = $this->addressService->showUserAddress();

            return ApiResponse::success($result, "Address founded");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateAddress', title: 'Atualizar endereço', description: '**operationId:** `updateAddress` — Atualiza o endereço informado. Requer que o registro pertença ao perfil autenticado (`AddressPolicy::update`). Quando `cep` é enviado, os dados de logradouro são reconsultados na API externa de CEP e sobrescrevem os campos correspondentes. Em **200**, `data` segue o schema **Address Resource** (`App\\Http\\Resources\\Addresses\\AddressResource`).')]
    public function update(UpdateAddressRequest $request): JsonResponse
    {
        try {
            $address = $this->addressService->update($request->validated());

            return ApiResponse::success($address, "Address updated with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'deleteAddress', title: 'Remover endereço', description: '**operationId:** `deleteAddress` — Remove o endereço informado. Requer que o registro pertença ao perfil autenticado (`AddressPolicy::delete`). Em **200**, `data` é `null`.')]
    public function delete(DeleteAddressRequest $request): JsonResponse
    {
        try {
            $this->addressService->delete($request->validated());

            return ApiResponse::success(message: "Address deleted with success");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }
}
