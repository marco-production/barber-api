<?php

namespace App\Models\UserEntities;

use App\Models\Geolocation\Country;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLogs extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'country_id',
        'reason_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'country_id' => 'integer',
        'reason_id' => 'integer'
    ];

    /**
     * Get the country that owns the User Logs
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'birth_place_id', 'id');
    }

    /**
     * Get the user deletion reason that owns the User Logs
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function userDeletionReason()
    {
        return $this->belongsTo(UserDeletionReason::class);
    }
}
