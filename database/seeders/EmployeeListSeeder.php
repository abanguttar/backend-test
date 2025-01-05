<?php

namespace Database\Seeders;



use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EmployeeListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::truncate();

        $arrayInsert = [];
        $time = Carbon::now();

        for ($i = 0; $i < 5; $i++) {
            $mockData =  [
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'phone' => mt_rand(11111111, 99999999),
                'created_at' => $time,
                'updated_at' => $time,
            ];

            DB::table('companies')->insert($mockData);
        }

        for ($i = 0; $i < 99; $i++) {
            $mockData =  [
                'company_id' => mt_rand(1, 5),
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'phone' => mt_rand(11111111, 99999999),
                'password' => Hash::make(Str::random(8)),
                'role' => 'employee',
                'token' => Str::random(60), //this token will use for reset password
                'created_at' => $time,
                'updated_at' => $time,

            ];
            array_push($arrayInsert, $mockData);
        }
        // Use DB insert for prevent email send
        DB::table('users')->insert($arrayInsert);
    }
}
