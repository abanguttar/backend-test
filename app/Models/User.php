<?php

namespace App\Models;

use Illuminate\Support\Str;
use App\Mail\ForgetPassword;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, SoftDeletes, Notifiable;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'role',
        'token',
        'phone',
        'address'
    ];

    public static function boot()
    {
        parent::boot();

        static::created(function ($manager) {
            $token = \Illuminate\Support\Str::random(60);

            $url = config('app.url') . '/api/password/reset?token=' . $token;
            $data = (object)[
                'name' => $manager->name,
                'email' => $manager->email,
                'url' => $url,
                'subject' => 'Reset Kata Sandi'
            ];

            Log::info("data url", [
                'data' => $data,
            ]);

            //Send email to reset password
            Mail::to($manager->email)->send(new ForgetPassword($data));
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id'); // Foreign key dan local key
    }


    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
