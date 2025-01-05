<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CompanyListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::truncate();

        $arrayInsert = [];
        $time = Carbon::now();

        for ($i = 0; $i < 100; $i++) {
            $mockData =  [
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'phone' => mt_rand(11111111, 99999999),
                'created_at' => $time,
                'updated_at' => $time,
            ];
            array_push($arrayInsert, $mockData);
        }
        // Use DB insert for prevent email send
        DB::table('companies')->insert($arrayInsert);
    }
}
