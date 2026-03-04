<?php

namespace App\Http\Controllers\Api\Management\Access\Auth;

use App\Actions\Access\Auth\GenerateOtpAction;
use App\Actions\Access\Auth\LoginAction;
use App\Actions\Access\Auth\LoginWithOtpAction;
use App\Actions\Access\Auth\LogoutAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\Access\Auth\SignInWithOtpRequest;
use App\Http\Requests\Management\Access\Auth\SignInRequest;
use App\Models\ActivityLog;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SignInController extends Controller
{
    use LogsActivity;
    public function __construct(
        private readonly LoginAction $loginAction,
        private readonly LoginWithOtpAction $loginWithOtpAction,
        private readonly GenerateOtpAction $generateOtpAction,
        private readonly LogoutAction $logoutAction,
    ) {}

    public function signInWithEmailAndPassword(SignInRequest $request): JsonResponse
    {
        $data = ($this->loginAction)($request->validated());
        $this->logActivity(
            type: ActivityLog::TYPE_AUTH,
            event: 'auth.login',
            description: 'Inicio de sesión con email y contraseña',
            metadata: ['method' => 'email_password'],
            userId: $data['user']['id'] ?? null,
        );
        return response()->json([
            'message' => __('messages.login_successful'),
            'data' => $data
        ]);
    }

    public function signInWithOtp(SignInWithOtpRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data = ($this->loginWithOtpAction)($data);
        $this->logActivity(
            type: ActivityLog::TYPE_AUTH,
            event: 'auth.login',
            description: 'Inicio de sesión con OTP',
            metadata: ['method' => 'otp'],
            userId: $data['user']['id'] ?? null,
        );
        return response()->json([
            'message' => __('messages.login_successful'),
            'data' => $data
        ]);
    }

    public function generateOtp(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);
        ($this->generateOtpAction)($request->get('email'));
        return response()->json([
            'message' => __('messages.otp_sent_successfully')
        ]);
    }

    public function signOut(): JsonResponse
    {
        $user = Auth::user();
        ($this->logoutAction)($user->id);

        $this->logActivity(
            type: ActivityLog::TYPE_AUTH,
            event: 'auth.logout',
            description: 'Cierre de sesión',
            userId: $user->id,
        );

        return response()->json([
            'message' => __('messages.logout_successful')
        ]);
    }
}
