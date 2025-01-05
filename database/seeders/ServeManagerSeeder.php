<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ServeManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::truncate();
        $password = 12345;
        $mockData = [
            'company_id' => 0,
            'name' => 'Super Admin',
            'email' => 'superadmin@superadmin.com',
            'password' => Hash::make($password),
            'role' => 'superadmin',
            'phone' => '085884885197',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        DB::table('users')->insert($mockData);

        $password = 11111;
        $mockData = [
            'company_id' => 1,
            'name' => 'Manager',
            'email' => 'manager@gmail.com',
            'password' => Hash::make($password),
            'role' => 'manager',
            'token' => Str::random(60), //this token will use for reset password
            'phone' => '085884885197',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        DB::table('users')->insert($mockData);
    }
}
