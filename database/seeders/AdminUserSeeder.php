<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->first();

        if ($admin) {
            $admin->is_admin = true;
            $admin->password = Hash::make('12345678');
            if (empty($admin->first_name)) {
                $admin->first_name = 'Admin';
            }
            if (empty($admin->last_name)) {
                $admin->last_name = 'User';
            }
            $admin->save();
        } else {
            User::create([
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('12345678'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]);
        }
    }
}
