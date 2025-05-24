<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ServiceProviderDocument extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'service_provider_id',
        'document_type',
        'file_path',
        'status',
        'description',
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
     * Get the service provider that owns the document.
     */
    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    /**
     * Get the user who last updated this document.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get full URL for the document.
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get file size in a human readable format.
     *
     * @return string
     */
    public function getFileSizeAttribute(): string
    {
        return Storage::exists($this->file_path) ? $this->formatBytes(Storage::size($this->file_path)) : '0 B';
    }

    /**
     * Format bytes to human readable format.
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Scope a query to filter documents by status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter documents by document type.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $documentType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $documentType)
    {
        return $query->where('document_type', $documentType);
    }
}
