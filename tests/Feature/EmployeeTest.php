<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\DB;
use Database\Seeders\CompanySeeder;
use Database\Seeders\ManagerSeeder;
use Database\Seeders\EmployeeSeeder;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\EmployeeListSeeder;
use Database\Seeders\ServeEmployeeSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\EmployeeDifferentCompanySeeder;

class EmployeeTest extends TestCase
{

    use RefreshDatabase;
    public $postData = [
        'company_id' => 1,
        'name' => 'Dummy Employee',
        'email' => 'employee@gmail.com',
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


    private function postEmployee($data, $role = 'superadmin')
    {
        $token = $this->actAsUser($role);

        // Gunakan token dalam header Authorization untuk request
        return $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->postJson('/api/employees', $data);
    }

    public function test_create_without_authenticate(): void
    {
        $response = $this->postJson('/api/employees', $this->postData);
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
        $response = $this->postEmployee($data)->assertJson(
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

    public function test_create_success_role_superadmin(): void
    {
        $response = $this->postEmployee($this->postData);
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

        // Make sure role is employee
        $this->assertEquals('employee', $response_data['role']);
        // Make sure company_id sama dengan yang diatur
        $this->assertEquals(1, $response_data['company_id']);
    }

    public function test_create_success_role_manager(): void
    {
        // ubah company_id menjadi 2
        $data = $this->postData;
        $data['company_id'] = 3;
        $response = $this->postEmployee($data, 'manager'); //company_id manager adalah 1 berdasarkan ManagerSeeder
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

        unset($data['password']);
        $response_data = $response->json('data');

        // Make sure role is employee
        $this->assertEquals('employee', $response_data['role']);
        // Make sure company_id sama dengan yang diatur
        $this->assertNotEquals($data['company_id'], $response_data['company_id']);
        // remove company_id
        unset($data['company_id']);
        $this->assertDatabaseHas('users', $data);
    }


    public function test_create_duplicate(): void
    {
        /**
         * This seeder will create superadmin and employee with manager@gmail.com
         */
        $this->seed(ServeEmployeeSeeder::class);

        $user = User::find(1);
        $token = auth('api')->login($user);
        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->postJson('/api/employees', $this->postData);
        $response->assertStatus(400)->assertJson([
            "errors" =>  [
                "email" => [
                    0 => "The email has already been taken."
                ]
            ]
        ]);
    }


    public function test_create_employee_as_employee_role_should_fail(): void
    {
        /**
         * Expectation refuse on middleware because role is not superadmin or manager
         */
        $response = $this->postEmployee($this->postData, 'employee');
        $response->assertStatus(403)->assertJson([
            "success" => false,
            "message" => "Tidak memiliki akses"
        ]);
    }

    public function test_fetch_self_employee_data()
    {
        $this->seed(CompanySeeder::class);
        $token = $this->actAsUser('employee');
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/employees/self');

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


    public function test_fetch_employee_data_for_edit(): void
    {
        /**
         * This seeder will create superadmin with id 1
         */
        $this->seed(UserSeeder::class);
        /**
         * This seeder will company and employee
         */
        $this->seed(EmployeeListSeeder::class);

        $user = User::find(1);
        $token = auth('api')->login($user);
        // Gunakan token dalam header Authorization untuk request

        $employee_id = mt_rand(1, 10);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson("/api/employees/$employee_id/edit", $this->postData);

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


    public function test_fetch_employee_data_for_edit_not_same_company_should_fails(): void
    {
        $this->seed(ManagerSeeder::class);  //company_id is 1
        /**
         * This seeder will company and employee without company_id 1
         */
        $this->seed(EmployeeDifferentCompanySeeder::class);

        $user = User::find(1);
        $token = auth('api')->login($user);

        // Gunakan token dalam header Authorization untuk request
        $employee_id = mt_rand(1, 10);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson("/api/employees/$employee_id/edit", $this->postData);

        $response->assertStatus(403)->assertJson([
            "success" => false,
            "message" => "Tidak memiliki akses"
        ]);
    }


    public function test_fetch_employee_data_for_edit_role_employee_should_fails(): void
    {
        /**
         * This seeder will company and employee without superadmin create id 1 is employee role
         */
        $this->seed(EmployeeListSeeder::class);

        $user = User::find(1);
        $token = auth('api')->login($user);
        // Gunakan token dalam header Authorization untuk request

        $employee_id = mt_rand(1, 10);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson("/api/employees/$employee_id/edit", $this->postData);

        $response->assertStatus(403)->assertJson([
            "success" => false,
            "message" => "Tidak memiliki akses"
        ]);
    }



    public function test_delete_employee_data_not_same_company_should_fails()
    {
        $this->seed(ManagerSeeder::class);  //company_id is 1
        /**
         * This seeder will company and employee without company_id 1
         */
        $this->seed(EmployeeDifferentCompanySeeder::class);
        $user = User::find(1);
        $token = auth('api')->login($user);

        $employee_id = mt_rand(2, 10);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->deleteJson("/api/employees/$employee_id");

        $response->assertStatus(403)->assertJson([
            "success" => false,
            "message" => "Tidak memiliki akses"
        ]);
    }


    public function test_delete_employee_data()
    {
        // create superadmin
        $this->seed(UserSeeder::class);
        /**
         * This seeder will company and employee without superadmin create id 1 is employee role
         */
        $this->seed(EmployeeListSeeder::class);
        $user = User::find(1);
        $token = auth('api')->login($user);

        $employee_id = mt_rand(2, 10);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->deleteJson("/api/employees/$employee_id");

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $employee_id, 'deleted_at' => \Carbon\Carbon::now()]);
    }



    public function test_update_employee_fails(): void
    {
        $data = $this->postData;
        $data['company_id'] = '';
        $data['email'] = 'string';
        $data['name'] = '';
        $data['phone'] = 'asdjhk';

        // create user as manager role
        $this->seed(ManagerSeeder::class);
        /**
         * This seeder will company and employee without superadmin create id 1 is employee role
         */
        $this->seed(EmployeeListSeeder::class);
        $user = User::find(1);
        $token = auth('api')->login($user);
        $employee_id = mt_rand(2, 10);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->putJson("/api/employees/$employee_id/edit", $data);


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



    public function test_update_employee_but_not_itself_role_employee_should_fails(): void
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
         * This seeder will company and employee without superadmin, id 1 is employee role
         */
        $this->seed(EmployeeListSeeder::class);
        $user = User::find(1); //login as employee role
        $token = auth('api')->login($user);
        $employee_id = mt_rand(2, 10);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->putJson("/api/employees/$employee_id/edit", $data);
        $response->assertStatus(403)->assertJson([
            "success" => false,
            "message" => "Tidak memiliki akses"
        ]);
    }


    public function test_update_employee_but_not_itself_role_not_same_company_should_fails(): void
    {
        $data = $this->postData;
        $data['company_id'] = 2;
        $data['email'] = 'update@gmail.com';
        $data['name'] = 'Update Manager';
        $data['phone'] = 6625512;
        $data['password'] = 86774682; //fill password

        $this->seed(ManagerSeeder::class);  //company_id is 1
        /**
         * This seeder will company and employee without company_id 1
         */
        $this->seed(EmployeeDifferentCompanySeeder::class);
        $user = User::find(1);
        $token = auth('api')->login($user);

        $employee_id = mt_rand(2, 10);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->putJson("/api/employees/$employee_id/edit", $data);
        $response->assertStatus(403)->assertJson([
            "success" => false,
            "message" => "Tidak memiliki akses"
        ]);
    }


    public function test_update_employee_without_change_password_success(): void
    {
        $data = $this->postData;
        $data['email'] = 'update@gmail.com';
        $data['name'] = 'Update Employee';
        $data['phone'] = 6625512;
        // remove password
        unset($data['password']);

        // create user as manager role
        $this->seed(ManagerSeeder::class);
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
        $user = User::find(1);
        $token = auth('api')->login($user);
        $employee_id = 2;

        /**Get old password of employee*/
        $manager = User::find($employee_id);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->putJson("/api/employees/$employee_id/edit", $data);
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

    public function test_update_employee_with_change_password_success(): void
    {
        $data = $this->postData;
        $data['email'] = 'update@gmail.com';
        $data['name'] = 'Update Employee';
        $data['phone'] = 6625512;
        $data['password'] = 86774682; //fill password

        $this->seed(ManagerSeeder::class);
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
        $user = User::find(1);
        $token = auth('api')->login($user);
        $employee_id = 2;

        /**Get old password of employee*/
        $employee = User::find($employee_id);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->putJson("/api/employees/$employee_id/edit", $data);
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
        $this->assertNotEquals($employee->password, $response_data['password']);
    }


    public function test_self_update_employee_without_change_password_success(): void
    {
        $data = $this->postData;
        $data['company_id'] = 2; //we try to override request company_id in managerSeeder company_id is 1
        $data['email'] = 'selfupdate@gmail.com';
        $data['name'] = 'selfUpdate Employee';
        $data['phone'] = 6625512;
        // remove password
        unset($data['password']);

        // create manager
        $this->seed(EmployeeSeeder::class);
        $user = User::find(1);
        $token = auth('api')->login($user);
        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->putJson("/api/employees/self", $data);
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

    public function test_self_update_employee_with_change_password_success(): void
    {
        $data = $this->postData;
        $data['company_id'] = 2; //we try to override request company_id in managerSeeder company_id is 1
        $data['email'] = 'selfupdate@gmail.com';
        $data['name'] = 'selfUpdate Employee';
        $data['phone'] = 6625512;
        $data['password'] = 'selfupdate'; //fill password

        // create manager
        $this->seed(EmployeeSeeder::class);
        $user = User::find(1);
        $token = auth('api')->login($user);
        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->putJson("/api/employees/self", $data);
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


    public function test_fetch_all_employee_list_page_1_role_superadmin()
    {
        $token = $this->actAsUser(); //act as superadmin

        $this->seed(EmployeeListSeeder::class);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson('/api/employees');

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
            // EmployeListSeeder membuat 99 data, jika role superadmin, akan menampilkan data employee dari semua perusahaan
            ->assertJson(['data' => [
                'current_page' => 1,
                'per_page' => 20,
                'to' => 20,
                "total" => 99
            ]]);
    }

    public function test_fetch_all_employee_list_page_1_role_manager()
    {
        $token = $this->actAsUser('manager'); //act as superadmin

        $this->seed(EmployeeListSeeder::class);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson('/api/employees');

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
            // EmployeListSeeder membuat 99 data, jika role manager, akan menampilkan data employee hanya dari perusahaan yang sama
            ->assertJson(['data' => [
                'current_page' => 1,
                'per_page' => 20,
            ]]);

        $response_data = $response->json('data');
        // Memastika jumlah total data tidak akan 99, karena berbeda company_id
        $this->assertNotEquals(99, $response_data['total']);
    }

    public function test_fetch_all_employee_list_page_1_role_employee()
    {
        $token = $this->actAsUser('employee'); //act as superadmin

        $this->seed(EmployeeListSeeder::class);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson('/api/employees');

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
            // EmployeListSeeder membuat 99 data, jika role employee, akan menampilkan data employee hanya dari perusahaan yang sama
            ->assertJson(['data' => [
                'current_page' => 1,
                'per_page' => 20,
            ]]);

        $response_data = $response->json('data');
        // Memastika jumlah total data tidak akan 99, karena berbeda company_id
        $this->assertNotEquals(99, $response_data['total']);
    }


    public function test_fetch_all_employee_list_page_n_as_manager()
    {
        $token = $this->actAsUser('manager'); //act as manager
        $this->seed(EmployeeListSeeder::class);
        $employees = User::where('role', 'employee')->where('company_id', 1)->get();
        $page = (int) round(count($employees) / 20);


        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson("/api/employees?page=$page");

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
            ]]);

        $response_data = $response->json('data');
        // Memastika jumlah total data tidak akan 99, karena berbeda company_id
        $this->assertNotEquals(99, $response_data['total']);
    }

    public function test_fetch_all_employee_list_page_1_descending()
    {
        $token = $this->actAsUser(); // act as superadmin

        $this->seed(EmployeeListSeeder::class);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson('/api/employees?sortDir=desc');

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

    public function test_fetch_all_employee_list_page_1_sortBy_name_descending()
    {
        $token = $this->actAsUser();

        $this->seed(EmployeeListSeeder::class);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson('/api/employees?sortBy=name&sortDir=desc');

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


    public function test_fetch_all_employee_list_page_with_search_params_found()
    {
        $token = $this->actAsUser('employee');
        $name = 'search';
        $this->postData['name'] = $name;
        $this->postData['role'] = 'employee';
        $this->seed(EmployeeListSeeder::class);

        // Buat unique user untuk dicari
        User::create($this->postData);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson("/api/employees?name=$name");


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

    public function test_fetch_all_employee_list_page_with_search_params_not_found()
    {
        $token = $this->actAsUser();
        $name = '78as98asuhdi987';

        // Buat unique user untuk dicari
        User::create($this->postData);

        // Gunakan token dalam header Authorization untuk request
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token", // Tambahkan token JWT di header
        ])->getJson("/api/employees?name=$name");

        $response->assertStatus(404)->assertJson([
            'success' => false,
            'message' => 'Data tidak ditemukan'
        ]);
    }
}
