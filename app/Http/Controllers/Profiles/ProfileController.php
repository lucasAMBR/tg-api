<?php

namespace App\Http\Controllers\Profiles;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profiles\ClientProfiles\StoreClientProfileRequest;
use App\Http\Requests\Profiles\CompanyProfiles\StoreCompanyProfileRequest;
use App\Http\Requests\Profiles\DevProfiles\StoreDevProfileRequest;
use App\Services\Profiles\ProfileService;

class ProfileController extends Controller
{
    public function __construct(protected ProfileService $profileService){}

    public function storeDevProfile(StoreDevProfileRequest $request)
    {
        $profile = $this->profileService->storeDevProfile($request->validated());

        return ApiResponse::success($profile, "Profile created with Success", 201);
    }

    public function storeCompanyProfile(StoreCompanyProfileRequest $request)
    {
        $profile = $this->profileService->storeCompanyProfile($request->validated());

        return ApiResponse::success($profile, "Profile created with Success", 201);
    }

    public function storeClientProfile(StoreClientProfileRequest $request)
    {
        $profile = $this->profileService->storeClientProfile($request->validated());

        return ApiResponse::success($profile, "Profile created with Success", 201);
    }
}
