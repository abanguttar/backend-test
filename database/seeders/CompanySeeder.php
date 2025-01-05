<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Company::truncate();
        $time = Carbon::now();
        $mockData = [
            'email' => 'uttarpn@gmail.com',
            'name' => 'Uttar Pradesh',
            'phone' => '0897723423',
            'created_at' => $time,
            'updated_at' => $time,
        ];

        DB::table('companies')->insert($mockData);
    }
}
