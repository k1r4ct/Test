<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Services\SystemLogService;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_path',
        'disk',
        'file_type',
        'mime_type',
        'file_name',
        'original_name',
        'alt_text',
        'file_size',
        'width',
        'height',
        'display_order',
        'is_active',
        'uploaded_by_user_id',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'display_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Supported file types.
     */
    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';
    public const TYPE_DOCUMENT = 'document';

    /**
     * Common image mime types.
     */
    public const IMAGE_MIMES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
    ];

    /**
     * Common video mime types.
     */
    public const VIDEO_MIMES = [
        'video/mp4',
        'video/webm',
        'video/ogg',
    ];

    // ==================== RELATIONSHIPS ====================

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_assets');
    }

    public function articlesWithThumbnail()
    {
        return $this->hasMany(Article::class, 'thumbnail_asset_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function storesWithLogo()
    {
        return $this->hasMany(Store::class, 'logo_asset_id');
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        static::creating(function ($asset) {
            if (empty($asset->disk)) {
                $asset->disk = 'public';
            }

            if ($asset->is_active === null) {
                $asset->is_active = true;
            }

            if (empty($asset->file_type) && !empty($asset->mime_type)) {
                $asset->file_type = static::determineFileType($asset->mime_type);
            }
        });

        // Log asset creation (file upload)
        static::created(function ($asset) {
            $asset->load('uploadedBy');
            
            $userName = $asset->uploadedBy 
                ? $asset->uploadedBy->name . ' ' . $asset->uploadedBy->cognome 
                : 'Sistema';

            SystemLogService::ecommerce()->info("Asset uploaded", [
                'asset_id' => $asset->id,
                'file_name' => $asset->file_name,
                'original_name' => $asset->original_name,
                'file_type' => $asset->file_type,
                'mime_type' => $asset->mime_type,
                'file_size' => $asset->file_size,
                'file_size_formatted' => $asset->getFormattedFileSize(),
                'dimensions' => $asset->getDimensions(),
                'disk' => $asset->disk,
                'file_path' => $asset->file_path,
                'uploaded_by' => $userName,
            ]);
        });

        // Log asset updates
        static::updated(function ($asset) {
            $changes = $asset->getChanges();
            $original = $asset->getOriginal();

            $changesForLog = [];
            foreach ($changes as $field => $newValue) {
                if ($field !== 'updated_at') {
                    $changesForLog[$field] = [
                        'old' => $original[$field] ?? null,
                        'new' => $newValue,
                    ];
                }
            }

            if (!empty($changesForLog)) {
                SystemLogService::ecommerce()->info("Asset updated", [
                    'asset_id' => $asset->id,
                    'file_name' => $asset->file_name,
                    'changes' => $changesForLog,
                ]);
            }
        });

        // Log asset deletion and delete file from storage
        static::deleting(function ($asset) {
            SystemLogService::ecommerce()->warning("Asset being deleted", [
                'asset_id' => $asset->id,
                'file_name' => $asset->file_name,
                'original_name' => $asset->original_name,
                'file_path' => $asset->file_path,
                'file_size' => $asset->file_size,
            ]);
            
            $asset->deleteFile();
        });
    }

    // ==================== SCOPES ====================

    public function scopeImages($query)
    {
        return $query->where('file_type', self::TYPE_IMAGE);
    }

    public function scopeVideos($query)
    {
        return $query->where('file_type', self::TYPE_VIDEO);
    }

    public function scopeDocuments($query)
    {
        return $query->where('file_type', self::TYPE_DOCUMENT);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc');
    }

    public function scopeByMimeType($query, string $mimeType)
    {
        return $query->where('mime_type', $mimeType);
    }

    public function scopeUploadedBy($query, int $userId)
    {
        return $query->where('uploaded_by_user_id', $userId);
    }

    public function scopeRecentlyUploaded($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeLargerThan($query, int $bytes)
    {
        return $query->where('file_size', '>', $bytes);
    }

    public function scopeSmallerThan($query, int $bytes)
    {
        return $query->where('file_size', '<', $bytes);
    }

    // ==================== HELPER METHODS ====================

    public function isImage(): bool
    {
        return $this->file_type === self::TYPE_IMAGE;
    }

    public function isVideo(): bool
    {
        return $this->file_type === self::TYPE_VIDEO;
    }

    public function isDocument(): bool
    {
        return $this->file_type === self::TYPE_DOCUMENT;
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function getUrl(): string
    {
        $disk = $this->disk ?? 'public';
        return Storage::disk($disk)->url($this->file_path);
    }

    public function getUrlAttribute(): string
    {
        return $this->getUrl();
    }

    public function getFullPath(): string
    {
        $disk = $this->disk ?? 'public';
        return Storage::disk($disk)->path($this->file_path);
    }

    public function fileExists(): bool
    {
        $disk = $this->disk ?? 'public';
        return Storage::disk($disk)->exists($this->file_path);
    }

    public function getFormattedFileSize(): string
    {
        if (!$this->file_size) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    public function getDimensions(): ?string
    {
        if (!$this->width || !$this->height) {
            return null;
        }

        return $this->width . 'x' . $this->height;
    }

    public function getAspectRatio(): ?float
    {
        if (!$this->width || !$this->height) {
            return null;
        }

        return round($this->width / $this->height, 2);
    }

    public function isLandscape(): bool
    {
        return $this->width > $this->height;
    }

    public function isPortrait(): bool
    {
        return $this->height > $this->width;
    }

    public function isSquare(): bool
    {
        return $this->width === $this->height;
    }

    public function getExtension(): string
    {
        return pathinfo($this->file_path, PATHINFO_EXTENSION);
    }

    public function getAltText(): string
    {
        return $this->alt_text ?: $this->file_name;
    }

    public function getDisplayName(): string
    {
        return $this->original_name ?: $this->file_name;
    }

    // ==================== FILE OPERATIONS ====================

    public function deleteFile(): bool
    {
        $disk = $this->disk ?? 'public';

        if ($this->fileExists()) {
            return Storage::disk($disk)->delete($this->file_path);
        }

        return true;
    }

    public function getContents(): ?string
    {
        $disk = $this->disk ?? 'public';

        if ($this->fileExists()) {
            return Storage::disk($disk)->get($this->file_path);
        }

        return null;
    }

    public function copyTo(string $newPath): ?self
    {
        $disk = $this->disk ?? 'public';

        if (!$this->fileExists()) {
            return null;
        }

        if (Storage::disk($disk)->copy($this->file_path, $newPath)) {
            $newAsset = $this->replicate();
            $newAsset->file_path = $newPath;
            $newAsset->save();

            return $newAsset;
        }

        return null;
    }

    // ==================== STATIC METHODS ====================

    public static function determineFileType(string $mimeType): string
    {
        if (in_array($mimeType, self::IMAGE_MIMES) || str_starts_with($mimeType, 'image/')) {
            return self::TYPE_IMAGE;
        }

        if (in_array($mimeType, self::VIDEO_MIMES) || str_starts_with($mimeType, 'video/')) {
            return self::TYPE_VIDEO;
        }

        return self::TYPE_DOCUMENT;
    }

    public static function createFromUpload(
        \Illuminate\Http\UploadedFile $file,
        string $directory = 'assets',
        ?int $uploadedByUserId = null
    ): self {
        $disk = 'public';
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        $fileName = time() . '_' . \Str::slug(pathinfo($originalName, PATHINFO_FILENAME))
                   . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs($directory, $fileName, $disk);

        $width = null;
        $height = null;
        if (str_starts_with($mimeType, 'image/')) {
            $dimensions = @getimagesize($file->getRealPath());
            if ($dimensions) {
                $width = $dimensions[0];
                $height = $dimensions[1];
            }
        }

        return static::create([
            'file_path' => $path,
            'disk' => $disk,
            'file_type' => static::determineFileType($mimeType),
            'mime_type' => $mimeType,
            'file_name' => $fileName,
            'original_name' => $originalName,
            'file_size' => $fileSize,
            'width' => $width,
            'height' => $height,
            'uploaded_by_user_id' => $uploadedByUserId,
            'is_active' => true,
        ]);
    }

    public static function getStorageStats(): array
    {
        return [
            'total_files' => static::count(),
            'total_size' => static::sum('file_size'),
            'total_size_formatted' => (new static(['file_size' => static::sum('file_size')]))->getFormattedFileSize(),
            'by_type' => static::selectRaw('file_type, COUNT(*) as count, SUM(file_size) as total_size')
                               ->groupBy('file_type')
                               ->get(),
            'images_count' => static::images()->count(),
            'videos_count' => static::videos()->count(),
        ];
    }

    public static function cleanupOrphaned(bool $dryRun = true): array
    {
        $orphaned = static::whereDoesntHave('articles')
                          ->whereDoesntHave('articlesWithThumbnail')
                          ->whereDoesntHave('storesWithLogo')
                          ->get();

        $result = [
            'count' => $orphaned->count(),
            'size' => $orphaned->sum('file_size'),
            'items' => $orphaned->pluck('file_path')->toArray(),
        ];

        if (!$dryRun) {
            foreach ($orphaned as $asset) {
                $asset->delete();
            }
            $result['deleted'] = true;
            
            SystemLogService::ecommerce()->info("Orphaned assets cleanup completed", [
                'deleted_count' => $result['count'],
                'freed_bytes' => $result['size'],
            ]);
        } else {
            $result['deleted'] = false;
        }

        return $result;
    }
}