<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'last_name',
        'username',
        'email',
        'password',
        'description',
        'img',
        'instagram',
        'facebook',
        'tiktok'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function plans()
    {
        return $this->hasMany(Plan::class);
    }
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public  function  getJWTIdentifier()
    {
        return  $this->getKey();
    }

    public  function  getJWTCustomClaims()
    {
        return [];
    }

    public function checkProd()
    {
        return app()->environment('production');
    }

    public static function createUser($request)
    {
        $user = User::create([
            'name' => $request->nombre,
            'last_name' => $request->apellido,
            'username' => $request->username,
            'img' => app()->environment('production')
                ? 'https://xvzgprxywegcfprvqhtr.supabase.co/storage/v1/object/public/storage/images/default/default_user.png'
                : env('APP_URL') . '/storage/default/default_user.png',

            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        return $user;
    }

    public static function updateUser($data, $user)
    {
        $user->update([
            'description' => $data['description'],
            'instagram' => $data['instagram'],
            'facebook' => $data['facebook'],
            'tiktok' => $data['tiktok'],
        ]);
        if (isset($data['img'])) {
            $user->update([
                'img' => $data['img']
            ]);
        } else if (isset($data['default_img'])) {
            $user->update([
            'img' => app()->environment('production')
                ? 'https://xvzgprxywegcfprvqhtr.supabase.co/storage/v1/object/public/storage/images/default/default_user.png'
                : env('APP_URL') . '/storage/default/default_user.png'
        ]);
        }

        return $user;
    }

    public static function updateGeneralSettings($data, $user)
    {
        $user->update([
            'username' => $data['username'],
            'email' => $data['email']
        ]);
        return $user;
    }

    public static function updatePassword($newPass, $user)
    {
        $user->update([
            'password' => Hash::make($newPass)
        ]);
        return $user;
    }
}
