<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\SignInRequest;
use App\Services\Admin\Auth\SignInService;
use Illuminate\Http\Request;

class SignInController extends Controller
{
    public function __construct(private readonly SignInService $signInService) {}

    public function signInWithEmailAndPassword(SignInRequest $request) {
        $data = $request->validated();
        $result = $this->signInService->signInWithEmailAndPassword($data);
        return response()->json([
            "status" => true,
            "message" => "User signed in successfully",
            "data" => $result
        ]);
    }
}
