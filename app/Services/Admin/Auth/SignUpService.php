<?php

namespace App\Services\Admin\Auth;

use App\Exceptions\Users\EmailAlreadyTakenException;
use App\Models\User;
use App\Models\Role;
use App\Models\Store;
use App\Models\StoreUserRole;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SignUpService {

    public function register(array $data): array {
        if (User::where('email', $data['email'])->exists()) {
            throw new EmailAlreadyTakenException();
        }
        $user = User::create([
            "email" => $data["email"],
            "password" => Hash::make($data["password"]),
        ]);
        $role = Role::firstOrCreate(['key' => 'admin']);
        $storeName = $data['store_name'] ?? ($user->email . "'s store");
        $slugBase = Str::slug($storeName);
        $slug = $slugBase;
        $i = 1;
        while (Store::where('slug', $slug)->exists()) {
            $slug = $slugBase . '-' . $i++;
        }
        $store = Store::create([
            'name' => $storeName,
            'slug' => $slug,
            'is_active' => true,
        ]);
        StoreUserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'store_id' => $store->id,
        ]);
        $token = $user->createToken("API Token")->plainTextToken;
        return ["user" => $user, "token" => $token];
    }
}