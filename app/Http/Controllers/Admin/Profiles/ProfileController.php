<?php

namespace App\Http\Controllers\Admin\Profiles;

use App\Http\Controllers\Controller;
use App\Services\Admin\Profiles\ProfileService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profileService) {}

    public function store(Request $request) {
        $data = $request->all();
        $result = $this->profileService->createProfile($data);
        return response()->json([
            "status" => true,
            "message" => "Profile created successfully",
            "data" => $result
        ]);
    }
}
