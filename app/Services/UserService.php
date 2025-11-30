<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function createUser(array $data, string $role = 'customer'): User
    {
        return User::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => $role,
        ]);
    }

    public function updateAdminUser(User $user, array $data): User
    {
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return $user;
    }

    public function deleteUser(User $user): void
    {
        if ($user->orders()->exists()) {
            throw new Exception('Pengguna tidak dapat dihapus karena memiliki riwayat pesanan.');
        }

        $user->delete();
    }
}
