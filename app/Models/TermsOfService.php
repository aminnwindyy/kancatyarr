<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TermsOfService extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'terms_of_service';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'version',
        'content',
        'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'version' => 'integer',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the active terms of service
     *
     * @return self|null
     */
    public static function getActive()
    {
        return self::where('active', true)->latest('version')->first();
    }

    /**
     * Get terms of service by version
     *
     * @param int $version
     * @return self|null
     */
    public static function getByVersion(int $version)
    {
        return self::where('version', $version)->first();
    }
}
