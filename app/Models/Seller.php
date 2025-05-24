<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Seller extends Model
{
    use HasFactory;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'seller_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'national_code',
        'business_license_number',
        'shop_name',
        'shop_description',
        'shop_logo',
        'shop_address',
        'shop_phone_number',
        'social_media_links',
        'location',
        'accept_from_own_city',
        'accept_nationwide',
        'document_verification_status',
        'verification_comment',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'social_media_links' => 'json',
        'accept_from_own_city' => 'boolean',
        'accept_nationwide' => 'boolean',
    ];

    /**
     * Get the user that owns the seller.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the products for the seller.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'seller_id', 'seller_id');
    }

    /**
     * Get the orders for the seller.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'seller_id', 'seller_id');
    }

    /**
     * Get the advertisements for the seller.
     */
    public function advertisements(): HasMany
    {
        return $this->hasMany(Advertisement::class, 'seller_id', 'seller_id');
    }

    /**
     * Get the business categories for the seller.
     */
    public function businessCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            BusinessCategory::class,
            'seller_business_categories',
            'seller_id',
            'business_category_id'
        );
    }

    /**
     * Get the skills for the seller.
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(
            Skill::class,
            'seller_skills',
            'seller_id',
            'skill_id'
        );
    }

    /**
     * Get the discounts for the seller.
     */
    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class, 'seller_id', 'seller_id');
    }

    /**
     * Get the reviews for the seller.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'seller_id', 'seller_id');
    }
}
