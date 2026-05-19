<?php

namespace App\Http\Controllers\Profiles;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyProfile\SyncCompanyStackRequest;
use App\Http\Requests\Profiles\ClientProfiles\StoreClientProfileRequest;
use App\Http\Requests\Profiles\ClientProfiles\UpdateClientProfileRequest;
use App\Http\Requests\Profiles\CompanyProfiles\StoreCompanyProfileRequest;
use App\Http\Requests\Profiles\CompanyProfiles\UpdateCompanyProfileRequest;
use App\Http\Requests\Profiles\DevProfiles\StoreDevProfileRequest;
use App\Http\Requests\Profiles\DevProfiles\UpdateDevProfileRequest;
use App\Models\ClientProfile;
use App\Models\CompanyProfile;
use App\Models\DevProfile;
use App\Services\Profiles\ProfileService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ProfileController extends Controller
{

    use AuthorizesRequests;

    public function __construct(protected ProfileService $profileService){}

    public function storeDevProfile(StoreDevProfileRequest $request)
    {
        $profile = $this->profileService->storeDevProfile($request->validated());

        return ApiResponse::success($profile, "Profile created with Success", 201);
    }

    public function storeCompanyProfile(StoreCompanyProfileRequest $request)
    {
        $profile = $this->profileService->storeCompanyProfile($request->validated());

        return ApiResponse::success($profile, "Profile created with success", 201);
    }

    public function storeClientProfile(StoreClientProfileRequest $request)
    {
        $profile = $this->profileService->storeClientProfile($request->validated());

        return ApiResponse::success($profile, "Profile created with success", 201);
    }

    public function updateDevProfile(UpdateDevProfileRequest $request, DevProfile $dev) {

        $this->authorize('update', $dev);

        $profile = $this->profileService->updateDevProfile($request->validated(), $dev);

        return ApiResponse::success($profile, "Profile updated with success", 200);

    }

    public function updateCompanyProfile(UpdateCompanyProfileRequest $request, CompanyProfile $company) {

        $this->authorize('update', $company);

        $profile = $this->profileService->updateCompanyProfile($request->validated(), $company);

        return ApiResponse::success($profile, "Profile updated with success", 200);

    }

    public function syncCompanyStack(CompanyProfile $company, SyncCompanyStackRequest $request)
    {
        $profile = $this->profileService->syncCompanyProfileStacks($company, $request->validated());

        return ApiResponse::success($profile, "Stack sync with success!");
    }

    public function getCompanyStack(CompanyProfile $company)
    {
        $stack = $this->profileService->getCompanyStack($company);

        return ApiResponse::success($stack, "Stack found!");
    }

    public function updateClientProfile(UpdateClientProfileRequest $request, ClientProfile $client) {

        $this->authorize('update', $client);

        $profile = $this->profileService->updateClientProfile($request->validated(), $client);

        return ApiResponse::success($profile, "Profile updated with success", 200);

    }

    public function destroyDevProfile(DevProfile $dev) {

        $this->authorize('delete', $dev);

        $profile = $this->profileService->destroyDevProfile($dev);

        return ApiResponse::success($profile, "Profile excluded with success!", 200);

    }

    public function destroyCompanyProfile(CompanyProfile $company) {

        $this->authorize('delete', $company);

        $profile = $this->profileService->destroyCompanyProfile($company);

        return ApiResponse::success($profile, "Profile excluded with success!", 200);

    }

    public function destroyClientProfile(ClientProfile $client) {

        $this->authorize('delete', $client);

        $profile = $this->profileService->destroyClientProfile($client);

        return ApiResponse::success($profile, "Profile excluded with success!", 200);

    }

}
