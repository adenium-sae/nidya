<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\SignInRequest;
use App\Services\Admin\Auth\SignInService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SignInController extends Controller
{
    public function __construct(private readonly SignInService $signInService) {}

    public function signInWithEmailAndPassword(SignInRequest $request) {
        $data = $request->validated();
        $result = $this->signInService->signInWithEmailAndPassword($data);
        return response()->json([
            "status" => true,
            "message" => __('messages.user_signed_in_successfully'),
            "data" => $result
        ]);
    }

    public function signInWithOtp(Request $request) {
        $email = $request->input("email");
        $otpCode = $request->input("otp");
        $data = ["email" => $email, "otp" => $otpCode];
        $result = $this->signInService->signInWithOtp($data);
        return response()->json([
            "status" => true,
            "message" => __('messages.user_signed_in_successfully'),
            "data" => $result
        ]);
    }

    public function generateOtp(Request $request) {
        $email = $request->input("email");
        $otp = $this->signInService->generateOtp($email);
        return response()->json([
            "status" => true,
            "message" => __('messages.otp_generated_successfully'),
            "data" => ["otp_code" => $otp]
        ]);
    }

    public function signOut(Request $request) {
        $user = Auth::user();
        $this->signInService->signOut($user);
        return response()->json([
            "status" => true,
            "message" => __('messages.user_signed_out_successfully'),
        ]);
    }
}
