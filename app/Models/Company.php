<?php

namespace App\Models;

use Illuminate\Support\Str;
use App\Mail\ForgetPassword;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'user_create',
        'user_update',
    ];

    public static function boot()
    {
        parent::boot();

        static::created(function ($company) {
            $token = Str::random(60);
            $company->user()->create([
                'company_id' => $company->id,
                'role' => 'manager',
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'password' => Hash::make('manager_' . $company->phone),
                'token' => $token
            ]);

            $url = config('app.url') . '/api/password/reset?token=' . $token;
            $data = (object)[
                'name' => $company->name,
                'email' => $company->email,
                'url' => $url,
                'subject' => 'Reset Kata Sandi'
            ];

            Log::info("data url", [
                'data' => $data,
            ]);

            //Send email to reset password
            Mail::to($company->email)->send(new ForgetPassword($data));
        });
    }


    public function user()
    {
        return $this->hasMany(User::class, 'company_id', 'id');
    }
}
