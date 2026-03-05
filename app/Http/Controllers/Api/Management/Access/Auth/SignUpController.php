<?php

namespace App\Http\Controllers\Api\Management\Access\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\Access\Auth\SignUpRequest;
use App\Actions\Auth\RegisterUserAction;
use App\Models\ActivityLog;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;

class SignUpController extends Controller
{
    use LogsActivity;
    public function __construct(private readonly RegisterUserAction $registerUserAction) {}

    public function register(SignUpRequest $request) {
        $data = $request->validated();
        $result = ($this->registerUserAction)($data);
        $this->logActivity(
            type: ActivityLog::TYPE_AUTH,
            event: 'auth.register',
            description: "Nuevo usuario registrado: {$result['user']['email']}",
            metadata: ['user_id' => $result['user']['id'], 'email' => $result['user']['email']],
            userId: $result['user']['id'],
            storeId: $result['store']['id'] ?? null,
        );
        return response()->json([
            "status" => true,
            "message" => __('messages.user_registered_successfully'),
            "data" => $result
        ]);
    }
}
