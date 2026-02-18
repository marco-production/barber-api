<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected string $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'lastname',
        'email',
        'phone_number',
        'avatar',
        'slug',
        'password',
        'country_id',
        'is_verified',
        'active',
        'google_id',
        'email_verified_at'
    ];

    protected $attributes = [
        'active' => true,
        'is_verified' => false,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_id'
    ];

    /**
     * Append attributes
     */
    //protected $appends = ['roles'];

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
            'is_verified' => 'boolean',
            'active' => 'boolean',
        ];
    }


    /* protected static function booted()
    {
        static::created(function ($user) {
            $user->assignRole('User');
        });
    } */


    /**
     * Role Attribute
     * 
     * @return Attribute
     */
    /* protected function roles(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getRoleNames()
        );
    } */
}
