<?php

namespace App\Http\Controllers\Api\Management\Access\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\Access\Auth\SignUpRequest;
use App\Services\Admin\Auth\SignUpService;
use Illuminate\Http\Request;

class SignUpController extends Controller
{
    public function __construct(private readonly SignUpService $signUpService) {}

    public function register(SignUpRequest $request) {
        $data = $request->validated();
        $result = $this->signUpService->register($data);
        return response()->json([
            "status" => true,
            "message" => __('messages.user_registered_successfully'),
            "data" => $result
        ]);
    }
}
