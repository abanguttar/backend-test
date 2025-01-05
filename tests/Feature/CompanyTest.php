<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use Database\Seeders\CompanyListSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\CompanySeeder;
use Database\Seeders\ManagerSeeder;
use Database\Seeders\EmployeeSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompanyTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;
    public $postData = [
        'email' => 'uttarpn@gmail.com',
        'name' => 'Uttar Pradesh',
        'phone' => '0897723423',
    ];

    private function actAsUser($role = 'superadmin')
    {
        if ($role === 'superadmin') {
            $this->seed(UserSeeder::class);
        } else if ($role === 'manager') {
            $this->seed(ManagerSeeder::class);
        } else if ($role === 'employee') {
            $this->seed(EmployeeSeeder::class);
        }
        $user = User::find(1);
        return auth('api')->login($user);
    }


    private function postCompany($data, $role = 'superadmin')
    {
        $token = $this->actAsUser($role);

        // Gunakan token dalam header Authorization untuk request
        return $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->postJson('/api/companies', $data);
    }

    private function putCompany($id, $data, $role = 'superadmin')
    {
        $token = $this->actAsUser($role);

        // Gunakan token dalam header Authorization untuk request
        return $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->putJson("/api/companies/$id", $data);
    }



    private function addCompany()
    {
        $this->seed(CompanySeeder::class);
    }


    public function test_create_without_authenticate(): void
    {
        $response = $this->postJson('/api/companies', [
            'email' => 'string',
            'name' => 'name',
            'phone' => '90809',
        ]);
        $response->assertStatus(401);
    }



    public function test_create_fails(): void
    {
        $data = $this->postData;
        $data['email'] = 'string';
        $data['name'] = '';
        $data['phone'] = 'asdjhk';
        $response = $this->postCompany($data)->assertJson(
            [
                "errors" => [
                    "name" => [
                        0 => "The name field is required.",
                    ],
                    "email" => [
                        0 => "The email field must be a valid email address."
                    ],
                    "phone" => [
                        0 => "The phone field must be a number.",
                        1 => "The phone field must not have more than 20 digits."
                    ]
                ]
            ]
        );

        $response->assertStatus(400);
    }


    public function test_create_success(): void
    {
        $response = $this->postCompany($this->postData);
        $response->assertStatus(201)->assertJson([
            'data' => $this->postData
        ]);
        $this->assertDatabaseHas('companies', $this->postData);
        $this->assertDatabaseHas('users', $this->postData);
    }

    public function test_create_duplicate(): void
    {
        $this->postCompany($this->postData);
        $response = $this->postJson('/api/companies', $this->postData);
        $response->assertStatus(400)->assertJson([
            "errors" =>  [
                "email" => [
                    0 => "The email has already been taken."
                ]
            ]
        ]);
    }

    public function test_create_company_as_manager_role_should_fail(): void
    {
        /**
         * Expectation refuse on middleware because role is not superadmin
         */
        $response = $this->postCompany($this->postData, 'manager');
        $response->assertStatus(403)->assertJson([
            "success" => false,
            "message" => "Tidak memiliki akses"
        ]);
    }

    public function test_create_company_as_employee_role_should_fail(): void
    {
        /**
         * Expectation refuse on middleware because role is not superadmin
         */
        $response = $this->postCompany($this->postData, 'employee');
        $response->assertStatus(403)->assertJson([
            "success" => false,
            "message" => "Tidak memiliki akses"
        ]);
    }

    public function test_create_other_company_success(): void
    {
        $data = $this->postData;
        $data['email'] = 'string2@gmail.com';
        $data['name'] = 'string two';
        $data['phone'] = '0909907324';
        $response = $this->postCompany($data);
        $this->assertDatabaseHas('companies', $data);
        $this->assertDatabaseHas('users', $data);
        $response->assertStatus(201)->assertJson([
            'data' => $data
        ]);
    }


    public function test_fetch_company_data()
    {
        $response = $this->postCompany($this->postData);
        $this->assertDatabaseHas('companies', $this->postData);
        $this->assertDatabaseHas('users', $this->postData);

        $user = User::find(1);
        if (!$user) {
            $this->seed(UserSeeder::class);
        }
        $user = User::find(1);
        $token = auth('api')->login($user);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson('/api/companies/1');

        $response->assertStatus(200);
    }

    public function test_fetch_company_data_fails()
    {
        $response = $this->postCompany($this->postData);
        $this->assertDatabaseHas('companies', $this->postData);
        $this->assertDatabaseHas('users', $this->postData);

        $user = User::find(1);
        if (!$user) {
            $this->seed(UserSeeder::class);
        }
        $user = User::find(1);
        $token = auth('api')->login($user);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson('/api/companies/10');

        // Id 10 belum ada

        $response->assertStatus(404);
    }



    public function test_delete_company_data()
    {
        $response = $this->postCompany($this->postData);
        $this->assertDatabaseHas('companies', $this->postData);
        $this->assertDatabaseHas('users', $this->postData);

        $user = User::find(1);
        if (!$user) {
            $this->seed(UserSeeder::class);
        }
        $user = User::find(1);
        $token = auth('api')->login($user);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->deleteJson('/api/companies/1');

        $response->assertStatus(200);
        $this->assertDatabaseHas('companies', ['id' => 1, 'deleted_at' => \Carbon\Carbon::now()]);
    }


    public function test_update_company_without_authenticate(): void
    {
        $this->addCompany();

        $response = $this->putJson('/api/companies/1', [
            'email' => 'string',
            'name' => 'name',
            'phone' => '90809',
        ]);
        $response->assertStatus(401);
    }

    public function test_update_company_as_manager_role_should_fail(): void
    {
        $this->addCompany();
        /**
         * Expectation refuse on middleware because role is not superadmin
         */
        $response = $this->putCompany(1, $this->postData, 'manager');
        $response->assertStatus(403)->assertJson([
            "success" => false,
            "message" => "Tidak memiliki akses"
        ]);
    }
    public function test_update_company_as_employee_role_should_fail(): void
    {
        $this->addCompany();
        /**
         * Expectation refuse on middleware because role is not superadmin
         */
        $response = $this->putCompany(1, $this->postData, 'employee');
        $response->assertStatus(403)->assertJson([
            "success" => false,
            "message" => "Tidak memiliki akses"
        ]);
    }



    public function test_update_company_fails(): void
    {
        $this->addCompany();
        $old_data = $this->postData;
        $data = $this->postData;
        $data['email'] = '';
        $data['name'] = "";
        $data['phone'] = '';
        $response = $this->putCompany(1, $data);
        $response->assertStatus(400);
        $this->assertDatabaseHas('companies', $old_data);
    }



    public function test_update_company_success(): void
    {
        $this->addCompany();
        $data = $this->postData;
        $data['email'] = 'update@gmail.com';
        $data['name'] = "Update Name";
        $data['phone'] = 98709234;
        $response = $this->putCompany(1, $data);
        $response->assertStatus(200)->assertJson([
            'data' => $data
        ]);
        $this->assertDatabaseHas('companies', $data);
    }




    public function test_fetch_all_companies_list_page_1()
    {
        $token = $this->actAsUser();

        $this->seed(CompanyListSeeder::class);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson('/api/companies');
        $response->assertStatus(200)->assertJson(['data' => [
            'current_page' => 1,
            'per_page' => 20,
            'to' => 20,
            "total" => 100
        ]]);
    }

    public function test_fetch_all_companies_list_page_n()
    {
        $token = $this->actAsUser();
        $page = mt_rand(1, 5);

        $this->seed(CompanyListSeeder::class);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson("/api/companies?page=$page");

        $response->assertStatus(200)->assertJson(['data' => [
            'current_page' => $page,
            'per_page' => 20,
            'to' => 20 * $page,
            "total" => 100
        ]]);
    }

    public function test_fetch_all_companies_list_page_1_descending()
    {
        $token = $this->actAsUser();

        $this->seed(CompanyListSeeder::class);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson('/api/companies?sortDir=desc');

        $response->assertStatus(200)->assertJson(['data' => [
            'current_page' => 1,
            'per_page' => 20,
            'to' => 20,
            "total" => 100
        ]]);

        $companies = $response->json('data.data');

        $sortedCompanies = collect($companies)->sortByDesc('id')->values()->toArray();
        $this->assertEquals($sortedCompanies, $companies);
    }

    public function test_fetch_all_companies_list_page_1_sortBy_name_descending()
    {
        $token = $this->actAsUser();

        $this->seed(CompanyListSeeder::class);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson('/api/companies?sortBy=name&sortDir=desc');

        $response->assertStatus(200)->assertJson(['data' => [
            'current_page' => 1,
            'per_page' => 20,
            'to' => 20,
            "total" => 100
        ]]);

        $companies = $response->json('data.data');

        $sortedCompanies = collect($companies)->sortByDesc('name')->values()->toArray();
        $this->assertEquals($sortedCompanies, $companies);
    }

    public function test_fetch_all_companies_list_page_with_search_params_found()
    {
        $token = $this->actAsUser();
        $name = 'a';

        $this->seed(CompanyListSeeder::class);
        $this->postData['name'] = $name;

        // Insert new data with spesific name
        $this->postCompany($this->postData);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson("/api/companies?name=$name");

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'success',
            'data' => [
                'current_page',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                    ]
                ],
                'total',
                'per_page',
                'last_page',
            ]
        ]);

        $companies = $response->json('data.data');
        $this->assertNotEmpty($companies);
        foreach ($companies as $company) {
            $this->assertStringContainsStringIgnoringCase($name, $company['name']);
        }
    }

    public function test_fetch_all_companies_list_page_with_search_params_not_found()
    {
        $token = $this->actAsUser();
        $name = 'auiasyfiuyasfiyasf89y98asf76';

        $this->seed(CompanyListSeeder::class);
        $this->postCompany($this->postData);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson("/api/companies?name=$name");

        $response->assertStatus(404)->assertJson([
            'success' => false,
            'message' => 'Data tidak ditemukan'
        ]);
    }
}
