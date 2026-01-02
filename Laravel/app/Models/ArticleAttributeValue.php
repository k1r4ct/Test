<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleAttributeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'attribute_id',
        'value_text',
        'value_textarea',
        'value_integer',
        'value_decimal',
        'value_boolean',
        'value_date',
        'value_datetime',
        'value_json',
    ];

    protected $casts = [
        'value_integer' => 'integer',
        'value_decimal' => 'decimal:4',
        'value_boolean' => 'boolean',
        'value_date' => 'date',
        'value_datetime' => 'datetime',
        'value_json' => 'array',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * The article this value belongs to.
     */
    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * The attribute definition this value is for.
     */
    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    // ==================== SCOPES ====================

    public function scopeByArticle($query, int $articleId)
    {
        return $query->where('article_id', $articleId);
    }

    public function scopeByAttribute($query, int $attributeId)
    {
        return $query->where('attribute_id', $attributeId);
    }

    public function scopeByAttributeCode($query, string $code)
    {
        return $query->whereHas('attribute', function ($q) use ($code) {
            $q->where('attribute_code', $code);
        });
    }

    /**
     * Scope to filter by text value.
     */
    public function scopeWhereTextValue($query, string $value)
    {
        return $query->where('value_text', $value);
    }

    /**
     * Scope to filter by integer value range.
     */
    public function scopeWhereIntegerBetween($query, int $min, int $max)
    {
        return $query->whereBetween('value_integer', [$min, $max]);
    }

    /**
     * Scope to filter by decimal value range.
     */
    public function scopeWhereDecimalBetween($query, float $min, float $max)
    {
        return $query->whereBetween('value_decimal', [$min, $max]);
    }

    /**
     * Scope to filter by boolean value.
     */
    public function scopeWhereBooleanTrue($query)
    {
        return $query->where('value_boolean', true);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeWhereDateBetween($query, $start, $end)
    {
        return $query->whereBetween('value_date', [$start, $end]);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Get the actual value based on attribute type.
     */
    public function getValue()
    {
        if (!$this->attribute) {
            return null;
        }

        $column = $this->attribute->getValueColumn();
        return $this->{$column};
    }

    /**
     * Set the value in the appropriate column based on attribute type.
     */
    public function setValueByType($value): self
    {
        if (!$this->attribute) {
            throw new \Exception('Attribute must be set before setting value');
        }

        // Clear all value columns first
        $this->clearAllValueColumns();

        // Set value in the appropriate column
        $column = $this->attribute->getValueColumn();
        $this->{$column} = $this->castValueForColumn($value, $this->attribute->attribute_type);

        return $this;
    }

    /**
     * Clear all value columns.
     */
    protected function clearAllValueColumns(): void
    {
        $this->value_text = null;
        $this->value_textarea = null;
        $this->value_integer = null;
        $this->value_decimal = null;
        $this->value_boolean = null;
        $this->value_date = null;
        $this->value_datetime = null;
        $this->value_json = null;
    }

    /**
     * Cast value to the appropriate type for storage.
     */
    protected function castValueForColumn($value, string $attributeType)
    {
        if ($value === null || $value === '') {
            return null;
        }

        switch ($attributeType) {
            case Attribute::TYPE_NUMBER:
                return (int) $value;

            case Attribute::TYPE_DECIMAL:
            case Attribute::TYPE_PRICE:
                return (float) $value;

            case Attribute::TYPE_BOOLEAN:
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);

            case Attribute::TYPE_DATE:
                return $value instanceof \DateTime ? $value : new \DateTime($value);

            case Attribute::TYPE_DATETIME:
                return $value instanceof \DateTime ? $value : new \DateTime($value);

            case Attribute::TYPE_MULTISELECT:
                return is_array($value) ? $value : [$value];

            default:
                return (string) $value;
        }
    }

    /**
     * Get the formatted value for display.
     */
    public function getFormattedValue(): string
    {
        if (!$this->attribute) {
            return '';
        }

        return $this->attribute->formatValue($this->getValue());
    }

    /**
     * Check if the value is empty.
     */
    public function isEmpty(): bool
    {
        $value = $this->getValue();

        if ($value === null) {
            return true;
        }

        if (is_string($value) && trim($value) === '') {
            return true;
        }

        if (is_array($value) && empty($value)) {
            return true;
        }

        return false;
    }

    /**
     * Validate the value against attribute rules.
     */
    public function isValid(): bool
    {
        if (!$this->attribute) {
            return false;
        }

        $value = $this->getValue();

        // Check required
        if ($this->attribute->is_required && $this->isEmpty()) {
            return false;
        }

        // Check options for select/multiselect
        if ($this->attribute->hasOptions() && !$this->isEmpty()) {
            return $this->attribute->isValidOption($value);
        }

        return true;
    }

    // ==================== STATIC HELPERS ====================

    /**
     * Create or update an attribute value for an article.
     */
    public static function setValue(int $articleId, int $attributeId, $value): self
    {
        $attribute = Attribute::find($attributeId);

        if (!$attribute) {
            throw new \Exception("Attribute {$attributeId} not found");
        }

        $attrValue = static::firstOrNew([
            'article_id' => $articleId,
            'attribute_id' => $attributeId,
        ]);

        $attrValue->attribute()->associate($attribute);
        $attrValue->setValueByType($value);
        $attrValue->save();

        return $attrValue;
    }

    /**
     * Create or update an attribute value by attribute code.
     */
    public static function setValueByCode(int $articleId, string $attributeCode, $value): self
    {
        $attribute = Attribute::findByCode($attributeCode);

        if (!$attribute) {
            throw new \Exception("Attribute with code '{$attributeCode}' not found");
        }

        return static::setValue($articleId, $attribute->id, $value);
    }

    /**
     * Get value for an article by attribute code.
     */
    public static function getValueByCode(int $articleId, string $attributeCode)
    {
        $value = static::where('article_id', $articleId)
                       ->whereHas('attribute', function ($q) use ($attributeCode) {
                           $q->where('attribute_code', $attributeCode);
                       })
                       ->with('attribute')
                       ->first();

        return $value ? $value->getValue() : null;
    }

    /**
     * Delete value for an article by attribute.
     */
    public static function deleteValue(int $articleId, int $attributeId): bool
    {
        return static::where('article_id', $articleId)
                     ->where('attribute_id', $attributeId)
                     ->delete() > 0;
    }

    /**
     * Bulk set values for an article.
     * 
     * @param int $articleId
     * @param array $values Array of [attribute_id => value] or [attribute_code => value]
     * @param bool $useCode Whether the keys are attribute codes (true) or IDs (false)
     */
    public static function bulkSetValues(int $articleId, array $values, bool $useCode = false): array
    {
        $results = [];

        foreach ($values as $key => $value) {
            try {
                if ($useCode) {
                    $results[$key] = static::setValueByCode($articleId, $key, $value);
                } else {
                    $results[$key] = static::setValue($articleId, (int) $key, $value);
                }
            } catch (\Exception $e) {
                $results[$key] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Get all values for an article as associative array.
     * 
     * @param int $articleId
     * @param bool $useCode Whether to use attribute codes as keys (true) or IDs (false)
     * @return array
     */
    public static function getAllValues(int $articleId, bool $useCode = true): array
    {
        $values = static::where('article_id', $articleId)
                        ->with('attribute')
                        ->get();

        $result = [];

        foreach ($values as $attrValue) {
            $key = $useCode ? $attrValue->attribute->attribute_code : $attrValue->attribute_id;
            $result[$key] = $attrValue->getValue();
        }

        return $result;
    }
}
