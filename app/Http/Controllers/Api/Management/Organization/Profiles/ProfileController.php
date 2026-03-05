<?php

namespace App\Http\Controllers\Api\Management\Organization\Profiles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profiles\CreateProfileRequest;
use App\Services\Admin\Profiles\ProfileService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profileService) {}

    public function store(CreateProfileRequest $request) {
        $data = $request->validated();
        $result = $this->profileService->createProfile($data);
        return response()->json([
            "status" => true,
            "message" => __('messages.profile_created_successfully'),
            "data" => $result
        ]);
    }
}
