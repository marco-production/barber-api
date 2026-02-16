<?php

namespace App\Models\UserEntities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDeletionReason extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'reason',
    ];

    /**
     * Get all of the UserLogs for the User Deletion Reason
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userlogs()
    {
        return $this->hasMany(UserLogs::class);
    }
}
