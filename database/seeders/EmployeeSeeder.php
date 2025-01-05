<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::truncate();
        $password = 12345;
        $mockData = [
            'company_id' => 1,
            'name' => 'Employee',
            'email' => 'employee@mail.com',
            'password' => Hash::make($password),
            'role' => 'employee',
            'phone' => '085884885197',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        DB::table('users')->insert($mockData);
    }
}
