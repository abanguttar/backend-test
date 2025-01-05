<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Database\Seeders\ManagerSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    /**
     * A basic feature test example.
     */

    use RefreshDatabase;

    private function userLogin($email, $password)
    {
        return  $this->postJson('/api/login', [
            'email' => $email,
            'password' => $password,
        ]);
    }


    private function actAsUser()
    {
        $this->seed(UserSeeder::class);
        $user = User::find(1);
        return auth('api')->login($user);
    }

    private function postReset($data, $token = null)
    {
        $value = null;
        $this->seed(ManagerSeeder::class);
        $user = User::find(1);
        if ($token === 'valid') {
            $value = $user->token;
        } else {
            $value = "not-valid";
        }
        return  $this->postJson('/api/password/reset?token=' . $value, $data);
    }

    public function test_login_failed_cause_empty_email_and_password(): void
    {
        $response = $this->userLogin('', '');

        $response->assertStatus(400);
    }

    public function test_login_failed(): void
    {
        $response = $this->userLogin('superadmin@superadmin.com', 'ajksdhkjas');

        $response->assertStatus(400);
    }

    public function test_login_success(): void
    {
        $this->seed(UserSeeder::class);
        $response = $this->userLogin('superadmin@superadmin.com', 12345);

        $response->assertStatus(200)->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in',
            'data' => [
                'email',
                'name',
            ]
        ]);

        $this->assertNotNull($response->json('access_token'));
    }


    public function test_logout_success(): void
    {
        $token = $this->actAsUser();
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->deleteJson('/api/logout');

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'message' => 'Berhasil logout',
        ]);

        $this->assertNull($response->json('access_token'));
    }


    public function test_reset_password_all_empty_fails(): void
    {

        $response = $this->postReset([
            'password' => '',
            'password_confirm' => '',
        ]);

        $response->assertStatus(400);
    }

    public function test_reset_password_token_not_valid(): void
    {

        $response = $this->postReset([
            'password' => '12345',
            'password_confirm' => '12345',
        ], 'not-valid');

        $response->assertStatus(400)->assertJson(
            ['errors' => "Token tidak valid!"]
        );
    }

    public function test_reset_password_not_match_fails(): void
    {

        $response = $this->postReset([
            'password' => '423234423',
            'password_confirm' => '312123132',
        ], 'valid');

        $response->assertStatus(400);
    }

    public function test_reset_password_success(): void
    {

        $response = $this->postReset([
            'password' => '12345',
            'password_confirm' => '12345',
        ], 'valid');

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'message' => 'Berhasil mengubah password'
        ]);
    }

    public function test_login_success_with_new_password(): void
    {
        // Change Password from 11111 to 12345
        $this->postReset([
            'password' => '12345',
            'password_confirm' => '12345',
        ], 'valid');

        $response = $this->userLogin('manager@mail.com', '12345');

        $response->assertStatus(200)->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in',
            'data' => [
                'email',
                'name',
            ]
        ]);

        $this->assertNotNull($response->json('access_token'));
    }

    public function test_current_user(): void
    {
        $token = $this->actAsUser();
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/me');
        $response->assertStatus(200)->assertJsonStructure([
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
        ]);
    }
}
