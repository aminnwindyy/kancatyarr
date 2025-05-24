<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'description',
        'type',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that last updated this setting.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'user_id');
    }

    /**
     * Get setting value based on its type.
     *
     * @return mixed
     */
    public function getTypedValueAttribute()
    {
        switch ($this->type) {
            case 'boolean':
                return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $this->value;
            case 'json':
                return json_decode($this->value, true);
            default:
                return $this->value;
        }
    }

    /**
     * Set value based on its type.
     *
     * @param mixed $value
     * @return void
     */
    public function setTypedValueAttribute($value)
    {
        switch ($this->type) {
            case 'boolean':
                $this->attributes['value'] = $value ? '1' : '0';
                break;
            case 'integer':
                $this->attributes['value'] = (string) (int) $value;
                break;
            case 'json':
                $this->attributes['value'] = json_encode($value);
                break;
            default:
                $this->attributes['value'] = (string) $value;
        }
    }

    /**
     * Get a setting value by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getValueByKey(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }

        return $setting->typed_value;
    }

    /**
     * Set a setting value by key.
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $updatedBy
     * @return ChatSetting
     */
    public static function setValueByKey(string $key, $value, ?int $updatedBy = null)
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return null;
        }

        // Log the change
        if ($setting->value !== (string) $value) {
            \DB::table('chat_settings_logs')->insert([
                'key' => $key,
                'old_value' => $setting->value,
                'new_value' => (string) $value,
                'updated_by' => $updatedBy,
                'created_at' => now(),
            ]);
        }

        $setting->typed_value = $value;
        $setting->updated_by = $updatedBy;
        $setting->save();

        return $setting;
    }

    /**
     * Reset a setting to default value.
     *
     * @param string $key
     * @param int|null $updatedBy
     * @return ChatSetting|null
     */
    public static function resetToDefault(string $key, ?int $updatedBy = null)
    {
        // Default values for each setting
        $defaults = [
            'allow_chat_after_72_hours' => false,
            'allow_chat_download' => true,
            'allow_photo_only' => false,
            'allow_view_names_only' => false,
            'prevent_bad_words' => true,
            'prevent_repeat_comments' => true,
            'prevent_frequent_reviews' => true,
            'limit_reviews_per_user' => 5,
            'prevent_low_char_messages' => true,
        ];

        if (!isset($defaults[$key])) {
            return null;
        }

        return self::setValueByKey($key, $defaults[$key], $updatedBy);
    }
}
