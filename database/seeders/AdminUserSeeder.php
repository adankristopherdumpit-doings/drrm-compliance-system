<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Adan Kristopher B. Dumpit',
            'email' => 'adankristopher.dumpit@gmail.com',
            'password' => Hash::make('password123'), // Change this password
            'email_verified_at' => now(),
        ]);

        echo "Admin user created successfully!\n";
        echo "Email: adankristopher.dumpit@gmail.com\n";
        echo "Password: password123\n";
    }
}
