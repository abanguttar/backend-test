<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use Database\Seeders\CompanySeeder;
use Illuminate\Support\Str;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\DB;
use Database\Seeders\ManagerSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\ManagerListSeeder;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\ServeManagerSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ManagerTest extends TestCase
{

    use RefreshDatabase;
    public $postData = [
        'company_id' => 1,
        'name' => 'Dummy Manager',
        'email' => 'manager@gmail.com',
        'password' => 12345,
        'phone' => '879234789243',
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


    private function postManager($data, $role = 'superadmin')
    {
        $token = $this->actAsUser($role);

        // Gunakan token dalam header Authorization untuk request
        return $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->postJson('/api/managers', $data);
    }

    public function test_create_without_authenticate(): void
    {
        $response = $this->postJson('/api/managers', $this->postData);
        $response->assertStatus(401);
    }

    public function test_create_fails(): void
    {
        $data = $this->postData;
        $data['company_id'] = '';
        $data['password'] = '';
        $data['email'] = 'string';
        $data['name'] = '';
        $data['phone'] = 'asdjhk';
        $response = $this->postManager($data)->assertJson(
            [
                "errors" => [
                    "company_id" => [
                        0 => "The company id field is required.",
                    ],
                    "name" => [
                        0 => "The name field is required.",
                    ],
                    "email" => [
                        0 => "The email field must be a valid email address."
                    ],
                    "password" => [
                        0 => "The password field is required.",
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
        $response = $this->postManager($this->postData);
        $response->assertStatus(201)->assertJsonStructure([
            "success",
            "data" => [
                "company_id",
                "name",
                "email",
                "phone",
                "updated_at",
                "created_at",
                "id"
            ]
        ]);
        $data = $this->postData;
        unset($data['password']);
        $response_data = $response->json('data');
        $this->assertDatabaseHas('users', $data);

        // Make sure role is manager
        $this->assertEquals('manager', $response_data['role']);
    }


    public function test_create_duplicate(): void
    {
        /**
         * This seeder will create superadmin and manager with manager@gmail.com
         */
        $this->seed(ServeManagerSeeder::class);

        $user = User::find(1);
        $token = auth('api')->login($user);
        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->postJson('/api/managers', $this->postData);
        $response->assertStatus(400)->assertJson([
            "errors" =>  [
                "email" => [
                    0 => "The email has already been taken."
                ]
            ]
        ]);
    }


    public function test_create_manager_as_manager_role_should_fail(): void
    {
        /**
         * Expectation refuse on middleware because role is not superadmin
         */
        $response = $this->postManager($this->postData, 'manager');
        $response->assertStatus(403)->assertJson([
            "success" => false,
            "message" => "Tidak memiliki akses"
        ]);
    }

    public function test_create_manager_as_employee_role_should_fail(): void
    {
        /**
         * Expectation refuse on middleware because role is not superadmin
         */
        $response = $this->postManager($this->postData, 'employee');
        $response->assertStatus(403)->assertJson([
            "success" => false,
            "message" => "Tidak memiliki akses"
        ]);
    }

    public function test_fetch_self_manager_data()
    {
        $this->seed(CompanySeeder::class);
        $token = $this->actAsUser('manager');
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/managers/self');

        $response->assertStatus(200)->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'company_id',
                'name',
                'email',
                'role',
                'phone',
                'token',
                'deleted_at',
                'created_at',
                'updated_at',
                'company' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'deleted_at',
                    'created_at',
                    'updated_at',
                ]
            ]
        ]);
    }


    public function test_fetch_manager_data_for_edit(): void
    {
        /**
         * This seeder will create superadmin with id 1
         */
        $this->seed(UserSeeder::class);
        /**
         * This seeder will company and manager
         */
        $this->seed(ManagerListSeeder::class);

        $user = User::find(1);
        $token = auth('api')->login($user);
        // Gunakan token dalam header Authorization untuk request

        $manager_id = mt_rand(1, 10);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson("/api/managers/$manager_id/edit", $this->postData);

        $response->assertStatus(200)->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'company_id',
                'name',
                'email',
                'role',
                'phone',
                'token',
                'deleted_at',
                'created_at',
                'updated_at',
                'company' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'deleted_at',
                    'created_at',
                    'updated_at',
                ]
            ]
        ]);
    }

    public function test_fetch_manager_data_for_edit_role_manager_should_fails(): void
    {
        /**
         * This seeder will company and manager without superadmin create id 1 is manager role
         */
        $this->seed(ManagerListSeeder::class);

        $user = User::find(1);
        $token = auth('api')->login($user);
        // Gunakan token dalam header Authorization untuk request

        $manager_id = mt_rand(1, 10);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson("/api/managers/$manager_id/edit", $this->postData);

        $response->assertStatus(403)->assertJson([
            "success" => false,
            "message" => "Tidak memiliki akses"
        ]);
    }

    public function test_fetch_manager_data_for_edit_role_employee_should_fails(): void
    {

        /**
         * This seeder will create users role employee with id 1
         */
        $this->seed(EmployeeSeeder::class);
        /**
         * This seeder will company and manager without superadmin create id 1 is manager role
         */
        $this->seed(ManagerListSeeder::class);

        $user = User::find(1);
        $token = auth('api')->login($user);
        // Gunakan token dalam header Authorization untuk request

        $manager_id = mt_rand(1, 10);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson("/api/managers/$manager_id/edit", $this->postData);

        $response->assertStatus(403)->assertJson([
            "success" => false,
            "message" => "Tidak memiliki akses"
        ]);
    }

    public function test_delete_manager_data()
    {
        // create superadmin
        $this->seed(UserSeeder::class);
        /**
         * This seeder will company and manager without superadmin create id 1 is manager role
         */
        $this->seed(ManagerListSeeder::class);
        $user = User::find(1);
        $token = auth('api')->login($user);

        $manager_id = mt_rand(2, 10);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->deleteJson("/api/managers/$manager_id");

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $manager_id, 'deleted_at' => \Carbon\Carbon::now()]);
    }

    public function test_update_manager_fails(): void
    {
        $data = $this->postData;
        $data['company_id'] = '';
        $data['email'] = 'string';
        $data['name'] = '';
        $data['phone'] = 'asdjhk';

        // create superadmin
        $this->seed(UserSeeder::class);
        /**
         * This seeder will company and manager without superadmin create id 1 is manager role
         */
        $this->seed(ManagerListSeeder::class);
        $user = User::find(1);
        $token = auth('api')->login($user);
        $manager_id = mt_rand(2, 10);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->putJson("/api/managers/$manager_id/edit", $data);

        $response->assertJson(
            [
                "errors" => [
                    "company_id" => [
                        0 => "The company id field is required.",
                    ],
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



    public function test_update_manager_but_not_itself_role_manager_should_fails(): void
    {
        $data = $this->postData;
        $data['company_id'] = 2;
        $data['email'] = 'update@gmail.com';
        $data['name'] = 'Update Manager';
        $data['phone'] = 6625512;
        $data['password'] = 86774682; //fill password

        // create manager
        $this->seed(ManagerSeeder::class);
        /**
         * This seeder will company and manager without superadmin create id 1 is manager role
         */
        $this->seed(ManagerListSeeder::class);
        $user = User::find(1); //login as manager role
        $token = auth('api')->login($user);
        $manager_id = mt_rand(2, 10);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->putJson("/api/managers/$manager_id/edit", $data);
        $response->assertStatus(403)->assertJson([
            "success" => false,
            "message" => "Tidak memiliki akses"
        ]);
    }


    public function test_update_manager_but_not_itself_role_employee_should_fails(): void
    {
        $data = $this->postData;
        $data['company_id'] = 2;
        $data['email'] = 'update@gmail.com';
        $data['name'] = 'Update Manager';
        $data['phone'] = 6625512;
        $data['password'] = 86774682; //fill password

        // create employee
        $this->seed(EmployeeSeeder::class);
        /**
         * This seeder will company and manager without superadmin create id 1 is manager role
         */
        $this->seed(ManagerListSeeder::class);
        $user = User::find(1); //login as manager role
        $token = auth('api')->login($user);
        $manager_id = mt_rand(2, 10);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->putJson("/api/managers/$manager_id/edit", $data);
        $response->assertStatus(403)->assertJson([
            "success" => false,
            "message" => "Tidak memiliki akses"
        ]);
    }



    public function test_update_manager_without_change_password_success(): void
    {
        $data = $this->postData;
        $data['company_id'] = 2;
        $data['email'] = 'update@gmail.com';
        $data['name'] = 'Update Manager';
        $data['phone'] = 6625512;
        // remove password
        unset($data['password']);

        // create superadmin
        $this->seed(UserSeeder::class);
        /**
         * This seeder will company and manager without superadmin create id 1 is manager role
         */
        $this->seed(ManagerListSeeder::class);
        $user = User::find(1);
        $token = auth('api')->login($user);
        $manager_id = mt_rand(2, 10);



        /**Get old password of manager*/

        $manager = User::find($manager_id);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->putJson("/api/managers/$manager_id/edit", $data);
        $response->assertStatus(200)->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'company_id',
                'name',
                'email',
                'role',
                'phone',
                'token',
                'deleted_at',
                'created_at',
                'updated_at',
            ]
        ]);

        $response_data = $response->json('data');

        $this->assertDatabaseHas('users', $data);

        // Validate is password is equal
        $this->assertEquals($manager->password, $response_data['password']);
    }

    public function test_update_manager_with_change_password_success(): void
    {
        $data = $this->postData;
        $data['company_id'] = 2;
        $data['email'] = 'update@gmail.com';
        $data['name'] = 'Update Manager';
        $data['phone'] = 6625512;
        $data['password'] = 86774682; //fill password

        // create superadmin
        $this->seed(UserSeeder::class);
        /**
         * This seeder will company and manager without superadmin create id 1 is manager role
         */
        $this->seed(ManagerListSeeder::class);
        $user = User::find(1);
        $token = auth('api')->login($user);
        $manager_id = mt_rand(2, 10);

        /**Get old password of manager*/
        $manager = User::find($manager_id);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->putJson("/api/managers/$manager_id/edit", $data);
        $response->assertStatus(200)->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'company_id',
                'name',
                'email',
                'role',
                'phone',
                'token',
                'deleted_at',
                'created_at',
                'updated_at',
            ]
        ]);
        unset($data['password']);
        $this->assertDatabaseHas('users', $data);


        $response_data = $response->json('data');

        // Validate is password is not equal
        $this->assertNotEquals($manager->password, $response_data['password']);
    }


    public function test_self_update_manager_without_change_password_success(): void
    {
        $data = $this->postData;
        $data['company_id'] = 2; //we try to override request company_id in managerSeeder company_id is 1
        $data['email'] = 'selfupdate@gmail.com';
        $data['name'] = 'selfUpdate Manager';
        $data['phone'] = 6625512;
        // remove password
        unset($data['password']);

        // create manager
        $this->seed(ManagerSeeder::class);
        $user = User::find(1);
        $token = auth('api')->login($user);
        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->putJson("/api/managers/self", $data);
        $response->assertStatus(200)->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'company_id',
                'name',
                'email',
                'role',
                'phone',
                'token',
                'deleted_at',
                'created_at',
                'updated_at',
            ]
        ]);

        unset($data['password']);

        $response_data = $response->json('data');

        // Validate is password is equal
        $this->assertEquals($user->password, $response_data['password']);
        // Validate is company id not change
        $this->assertNotEquals($user->company_id, $data['company_id']);
        // Validate is email not change
        $this->assertNotEquals($user->email, $data['email']);
        unset($data['company_id'], $data['email']); //unset company_id and email because manager self cant change it
        $this->assertDatabaseHas('users', $data);
    }

    public function test_self_update_manager_with_change_password_success(): void
    {
        $data = $this->postData;
        $data['company_id'] = 2; //we try to override request company_id in managerSeeder company_id is 1
        $data['email'] = 'selfupdate@gmail.com';
        $data['name'] = 'selfUpdate Manager';
        $data['phone'] = 6625512;
        $data['password'] = 'selfupdate'; //fill password

        // create manager
        $this->seed(ManagerSeeder::class);
        $user = User::find(1);
        $token = auth('api')->login($user);
        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->putJson("/api/managers/self", $data);
        $response->assertStatus(200)->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'company_id',
                'name',
                'email',
                'role',
                'phone',
                'token',
                'deleted_at',
                'created_at',
                'updated_at',
            ]
        ]);

        unset($data['password']);

        $response_data = $response->json('data');

        // Validate is password is not equal
        $this->assertNotEquals($user->password, $response_data['password']);
        // Validate is company id not change
        $this->assertNotEquals($user->company_id, $data['company_id']);
        // Validate is email not change
        $this->assertNotEquals($user->email, $data['email']);
        unset($data['company_id'], $data['email']); //unset company_id and email because manager self cant change it
        $this->assertDatabaseHas('users', $data);
    }


    public function test_fetch_all_manager_list_page_1_role_superadmin()
    {
        $token = $this->actAsUser();

        $this->seed(ManagerListSeeder::class);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson('/api/managers');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'company_id',
                            'name',
                            'email',
                            'role',
                            'phone',
                            'token',
                            'deleted_at',
                            'created_at',
                            'updated_at',
                            'company' => [
                                'id',
                                'name',
                                'email',
                                'phone',
                                'deleted_at',
                                'created_at',
                                'updated_at',
                            ]
                        ]
                    ],
                    'total',
                    'per_page',
                    'last_page',
                ]
            ])
            // ManagerListSeeder membuat 99 data, jika role superadmin, akan menampilkan data manager dari semua perusahaan
            ->assertJson(['data' => [
                'current_page' => 1,
                'per_page' => 20,
                'to' => 20,
                "total" => 99
            ]]);
    }

    public function test_fetch_all_manager_list_page_1_role_manager()
    {
        $token = $this->actAsUser('manager'); //company_id is 1

        $this->seed(ManagerListSeeder::class);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson('/api/managers');
        $response_data = $response->json('data');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'company_id',
                            'name',
                            'email',
                            'role',
                            'phone',
                            'token',
                            'deleted_at',
                            'created_at',
                            'updated_at',
                            'company' => [
                                'id',
                                'name',
                                'email',
                                'phone',
                                'deleted_at',
                                'created_at',
                                'updated_at',
                            ]
                        ]
                    ],
                    'total',
                    'per_page',
                    'last_page',
                ]
            ])
            // ManagerListSeeder membuat 99 data, jika role manager, akan menampilkan data manager hanya dari perusahaan yang sama
            ->assertJson(['data' => [
                'current_page' => 1,
                'per_page' => 20,
            ]]);

        // Memastika jumlah total data tidak akan 99, karena berbeda company_id
        $this->assertNotEquals(99, $response_data['total']);
    }

    public function test_fetch_all_manager_list_page_n()
    {
        $token = $this->actAsUser();
        $page = mt_rand(1, 5);

        $this->seed(ManagerListSeeder::class);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson("/api/managers?page=$page");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'company_id',
                            'name',
                            'email',
                            'role',
                            'phone',
                            'token',
                            'deleted_at',
                            'created_at',
                            'updated_at',
                            'company' => [
                                'id',
                                'name',
                                'email',
                                'phone',
                                'deleted_at',
                                'created_at',
                                'updated_at',
                            ]
                        ]
                    ],
                    'total',
                    'per_page',
                    'last_page',
                ]
            ])
            ->assertJson(['data' => [
                'current_page' => $page,
                'per_page' => 20,
                'to' => 20 * $page,
            ]]);
    }

    public function test_fetch_all_manager_list_page_1_descending()
    {
        $token = $this->actAsUser();

        $this->seed(ManagerListSeeder::class);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson('/api/managers?sortDir=desc');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'company_id',
                            'name',
                            'email',
                            'role',
                            'phone',
                            'token',
                            'deleted_at',
                            'created_at',
                            'updated_at',
                            'company' => [
                                'id',
                                'name',
                                'email',
                                'phone',
                                'deleted_at',
                                'created_at',
                                'updated_at',
                            ]
                        ]
                    ],
                    'total',
                    'per_page',
                    'last_page',
                ]
            ])
            ->assertJson(['data' => [
                'current_page' => 1,
                'per_page' => 20,
                'to' => 20,
                "total" => 99
            ]]);

        $companies = $response->json('data.data');

        $sortedCompanies = collect($companies)->sortByDesc('id')->values()->toArray();
        $this->assertEquals($sortedCompanies, $companies);
    }

    public function test_fetch_all_manager_list_page_1_sortBy_name_descending()
    {
        $token = $this->actAsUser();

        $this->seed(ManagerListSeeder::class);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson('/api/managers?sortBy=name&sortDir=desc');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'company_id',
                            'name',
                            'email',
                            'role',
                            'phone',
                            'token',
                            'deleted_at',
                            'created_at',
                            'updated_at',
                            'company' => [
                                'id',
                                'name',
                                'email',
                                'phone',
                                'deleted_at',
                                'created_at',
                                'updated_at',
                            ]
                        ]
                    ],
                    'total',
                    'per_page',
                    'last_page',
                ]
            ])
            ->assertJson(['data' => [
                'current_page' => 1,
                'per_page' => 20,
                'to' => 20,
                "total" => 99
            ]]);

        $companies = $response->json('data.data');

        $sortedCompanies = collect($companies)->sortByDesc('name')->values()->toArray();
        $this->assertEquals($sortedCompanies, $companies);
    }


    public function test_fetch_all_manager_list_page_with_search_params_found()
    {
        $token = $this->actAsUser();
        $name = 'search';
        $this->postData['name'] = $name;
        $this->postData['role'] = 'manager';
        $this->seed(ManagerListSeeder::class);

        // Buat unique user untuk dicari
        User::create($this->postData);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson("/api/managers?name=$name");


        $response->assertStatus(200);

        $response->assertJsonStructure([
            'success',
            'data' => [
                'current_page',
                'data' => [
                    '*' => [
                        'id',
                        'company_id',
                        'name',
                        'email',
                        'password',
                        'role',
                        'phone',
                    ]
                ],
                'total',
                'per_page',
                'last_page',
            ]
        ]);

        $managers = $response->json('data.data');
        $this->assertNotEmpty($managers);
        foreach ($managers as $manager) {
            $this->assertStringContainsStringIgnoringCase($name, $manager['name']);
        }
    }

    public function test_fetch_all_manager_list_page_with_search_params_not_found()
    {
        $token = $this->actAsUser();
        $name = '78as98asuhdi987';

        // Buat unique user untuk dicari
        User::create($this->postData);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson("/api/managers?name=$name");

        $response->assertStatus(404)->assertJson([
            'success' => false,
            'message' => 'Data tidak ditemukan'
        ]);
    }
}
