<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;

class Attribute extends Model
{
    use HasFactory;

    /**
     * Supported attribute types for the EAV system.
     */
    public const TYPE_TEXT = 'text';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_NUMBER = 'number';
    public const TYPE_DECIMAL = 'decimal';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_SELECT = 'select';
    public const TYPE_MULTISELECT = 'multiselect';
    public const TYPE_DATE = 'date';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_PRICE = 'price';

    /**
     * Map attribute types to their corresponding value column.
     */
    public const TYPE_TO_COLUMN_MAP = [
        self::TYPE_TEXT => 'value_text',
        self::TYPE_TEXTAREA => 'value_textarea',
        self::TYPE_NUMBER => 'value_integer',
        self::TYPE_DECIMAL => 'value_decimal',
        self::TYPE_BOOLEAN => 'value_boolean',
        self::TYPE_SELECT => 'value_text',
        self::TYPE_MULTISELECT => 'value_json',
        self::TYPE_DATE => 'value_date',
        self::TYPE_DATETIME => 'value_datetime',
        self::TYPE_PRICE => 'value_decimal',
    ];

    protected $fillable = [
        'attribute_code',
        'attribute_name',
        'description',
        'attribute_type',
        'options',
        'is_required',
        'validation_rules',
        'default_value',
        'sort_order',
        'is_visible_on_front',
        'is_filterable',
        'is_searchable',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
        'is_visible_on_front' => 'boolean',
        'is_filterable' => 'boolean',
        'is_searchable' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function attributeSets()
    {
        return $this->belongsToMany(AttributeSet::class, 'attribute_set_attributes')
                    ->withPivot(['sort_order', 'is_required'])
                    ->withTimestamps();
    }

    public function values()
    {
        return $this->hasMany(ArticleAttributeValue::class);
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_attribute_values')
                    ->withTimestamps();
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log attribute creation
        static::created(function ($attribute) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::ecommerce()->info("Attribute created", [
                'attribute_id' => $attribute->id,
                'attribute_code' => $attribute->attribute_code,
                'attribute_name' => $attribute->attribute_name,
                'attribute_type' => $attribute->attribute_type,
                'is_required' => $attribute->is_required,
                'is_filterable' => $attribute->is_filterable,
                'is_searchable' => $attribute->is_searchable,
                'options_count' => is_array($attribute->options) ? count($attribute->options) : 0,
                'created_by' => $userName,
            ]);
        });

        // Log attribute updates
        static::updated(function ($attribute) {
            $changes = $attribute->getChanges();
            $original = $attribute->getOriginal();

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
                $userName = Auth::check() 
                    ? Auth::user()->name . ' ' . Auth::user()->cognome 
                    : 'Sistema';

                SystemLogService::ecommerce()->info("Attribute updated", [
                    'attribute_id' => $attribute->id,
                    'attribute_code' => $attribute->attribute_code,
                    'attribute_name' => $attribute->attribute_name,
                    'changes' => $changesForLog,
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log attribute deletion
        static::deleted(function ($attribute) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::ecommerce()->warning("Attribute deleted", [
                'attribute_id' => $attribute->id,
                'attribute_code' => $attribute->attribute_code,
                'attribute_name' => $attribute->attribute_name,
                'attribute_type' => $attribute->attribute_type,
                'deleted_by' => $userName,
            ]);
        });
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFilterable($query)
    {
        return $query->where('is_filterable', true);
    }

    public function scopeSearchable($query)
    {
        return $query->where('is_searchable', true);
    }

    public function scopeVisibleOnFront($query)
    {
        return $query->where('is_visible_on_front', true);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('attribute_type', $type);
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('attribute_code', $code);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    // ==================== HELPER METHODS ====================

    public function getValueColumn(): string
    {
        return self::TYPE_TO_COLUMN_MAP[$this->attribute_type] ?? 'value_text';
    }

    public function hasOptions(): bool
    {
        return in_array($this->attribute_type, [self::TYPE_SELECT, self::TYPE_MULTISELECT]);
    }

    public function isNumeric(): bool
    {
        return in_array($this->attribute_type, [
            self::TYPE_NUMBER,
            self::TYPE_DECIMAL,
            self::TYPE_PRICE,
        ]);
    }

    public function isDateType(): bool
    {
        return in_array($this->attribute_type, [self::TYPE_DATE, self::TYPE_DATETIME]);
    }

    public function getOptionsArray(): array
    {
        if (!$this->hasOptions() || empty($this->options)) {
            return [];
        }

        return is_array($this->options) ? $this->options : [];
    }

    public function isValidOption($value): bool
    {
        if (!$this->hasOptions()) {
            return true;
        }

        $options = $this->getOptionsArray();

        if ($this->attribute_type === self::TYPE_MULTISELECT && is_array($value)) {
            return empty(array_diff($value, $options));
        }

        return in_array($value, $options);
    }

    public function getValidationRules(): array
    {
        $rules = [];

        if ($this->is_required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        switch ($this->attribute_type) {
            case self::TYPE_NUMBER:
                $rules[] = 'integer';
                break;
            case self::TYPE_DECIMAL:
            case self::TYPE_PRICE:
                $rules[] = 'numeric';
                break;
            case self::TYPE_BOOLEAN:
                $rules[] = 'boolean';
                break;
            case self::TYPE_DATE:
                $rules[] = 'date';
                break;
            case self::TYPE_DATETIME:
                $rules[] = 'date';
                break;
            case self::TYPE_SELECT:
                if ($this->hasOptions()) {
                    $rules[] = 'in:' . implode(',', $this->getOptionsArray());
                }
                break;
            case self::TYPE_MULTISELECT:
                $rules[] = 'array';
                break;
            default:
                $rules[] = 'string';
        }

        if (!empty($this->validation_rules)) {
            $customRules = explode('|', $this->validation_rules);
            $rules = array_merge($rules, $customRules);
        }

        return $rules;
    }

    public function formatValue($value): string
    {
        if ($value === null) {
            return '';
        }

        switch ($this->attribute_type) {
            case self::TYPE_BOOLEAN:
                return $value ? 'Sì' : 'No';
            case self::TYPE_PRICE:
                return number_format((float) $value, 2, ',', '.') . ' €';
            case self::TYPE_DECIMAL:
                return number_format((float) $value, 2, ',', '.');
            case self::TYPE_DATE:
                return $value instanceof \DateTime
                    ? $value->format('d/m/Y')
                    : date('d/m/Y', strtotime($value));
            case self::TYPE_DATETIME:
                return $value instanceof \DateTime
                    ? $value->format('d/m/Y H:i')
                    : date('d/m/Y H:i', strtotime($value));
            case self::TYPE_MULTISELECT:
                return is_array($value) ? implode(', ', $value) : (string) $value;
            default:
                return (string) $value;
        }
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('attribute_code', $code)->first();
    }
}