<?php

namespace App\Services\Admin\Auth;

use App\Exceptions\Users\EmailAlreadyTakenException;
use App\Models\User;
use App\Models\Role;
use App\Models\Store;
use App\Models\StoreUserRole;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\Profile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SignUpService {

    public function register(array $data): array {
        try {
            DB::beginTransaction();
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
            $profile = Profile::create([
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'second_last_name' => $data['second_last_name'],
                'birth_date' => $data['birth_date'] ?? null,
            ]);
            $branch = Branch::create([
                'name' => $storeName . ' - Main Branch',
                'store_id' => $store->id,
                'is_active' => true,
            ]);
            Warehouse::create([
                'name' => $storeName . ' Warehouse',
                'type' => 'central',
                'is_active' => true,
                'branch_id' => $branch->id,
                'store_id' => $store->id,
            ]);
            $token = $user->createToken("API Token")->plainTextToken;
            DB::commit();
            return [
                "user" => $user,
                "token" => $token,
                "profile" => $profile,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}