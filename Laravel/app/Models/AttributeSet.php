<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttributeSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'set_name',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Attributes belonging to this set.
     */
    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'attribute_set_attributes')
                    ->withPivot(['sort_order', 'is_required'])
                    ->withTimestamps()
                    ->orderBy('attribute_set_attributes.sort_order', 'asc');
    }

    /**
     * Active attributes belonging to this set.
     */
    public function activeAttributes()
    {
        return $this->attributes()->where('attributes.is_active', true);
    }

    /**
     * Articles using this attribute set.
     */
    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Pivot records for this set.
     */
    public function attributeSetAttributes()
    {
        return $this->hasMany(AttributeSetAttribute::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    public function scopeByName($query, string $name)
    {
        return $query->where('set_name', $name);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Get attributes grouped by their requirement status.
     */
    public function getGroupedAttributes(): array
    {
        $attributes = $this->activeAttributes()->get();

        return [
            'required' => $attributes->filter(function ($attr) {
                // Check pivot override first, then attribute default
                $pivotRequired = $attr->pivot->is_required;
                return $pivotRequired !== null ? $pivotRequired : $attr->is_required;
            }),
            'optional' => $attributes->filter(function ($attr) {
                $pivotRequired = $attr->pivot->is_required;
                $isRequired = $pivotRequired !== null ? $pivotRequired : $attr->is_required;
                return !$isRequired;
            }),
        ];
    }

    /**
     * Get all required attribute IDs for this set.
     */
    public function getRequiredAttributeIds(): array
    {
        return $this->activeAttributes()
                    ->get()
                    ->filter(function ($attr) {
                        $pivotRequired = $attr->pivot->is_required;
                        return $pivotRequired !== null ? $pivotRequired : $attr->is_required;
                    })
                    ->pluck('id')
                    ->toArray();
    }

    /**
     * Check if this set contains a specific attribute.
     */
    public function hasAttribute(int $attributeId): bool
    {
        return $this->attributes()->where('attributes.id', $attributeId)->exists();
    }

    /**
     * Check if this set contains an attribute by code.
     */
    public function hasAttributeByCode(string $code): bool
    {
        return $this->attributes()->where('attributes.attribute_code', $code)->exists();
    }

    /**
     * Get attribute by code within this set.
     */
    public function getAttributeByCode(string $code): ?Attribute
    {
        return $this->attributes()->where('attributes.attribute_code', $code)->first();
    }

    /**
     * Add an attribute to this set.
     */
    public function addAttribute(int $attributeId, array $pivotData = []): void
    {
        $defaultPivot = [
            'sort_order' => $this->attributes()->count(),
            'is_required' => null,
        ];

        $this->attributes()->syncWithoutDetaching([
            $attributeId => array_merge($defaultPivot, $pivotData),
        ]);
    }

    /**
     * Remove an attribute from this set.
     */
    public function removeAttribute(int $attributeId): void
    {
        $this->attributes()->detach($attributeId);
    }

    /**
     * Update attribute pivot data within this set.
     */
    public function updateAttributePivot(int $attributeId, array $pivotData): void
    {
        $this->attributes()->updateExistingPivot($attributeId, $pivotData);
    }

    /**
     * Reorder attributes in this set.
     */
    public function reorderAttributes(array $attributeIdsInOrder): void
    {
        foreach ($attributeIdsInOrder as $index => $attributeId) {
            $this->attributes()->updateExistingPivot($attributeId, ['sort_order' => $index]);
        }
    }

    /**
     * Get filterable attributes in this set.
     */
    public function getFilterableAttributes()
    {
        return $this->activeAttributes()
                    ->where('attributes.is_filterable', true)
                    ->get();
    }

    /**
     * Get searchable attributes in this set.
     */
    public function getSearchableAttributes()
    {
        return $this->activeAttributes()
                    ->where('attributes.is_searchable', true)
                    ->get();
    }

    /**
     * Get attributes visible on frontend in this set.
     */
    public function getVisibleAttributes()
    {
        return $this->activeAttributes()
                    ->where('attributes.is_visible_on_front', true)
                    ->get();
    }

    /**
     * Clone this attribute set with a new name.
     */
    public function duplicate(string $newName): self
    {
        $newSet = $this->replicate();
        $newSet->set_name = $newName;
        $newSet->save();

        // Copy attribute associations
        foreach ($this->attributes as $attribute) {
            $newSet->attributes()->attach($attribute->id, [
                'sort_order' => $attribute->pivot->sort_order,
                'is_required' => $attribute->pivot->is_required,
            ]);
        }

        return $newSet;
    }

    /**
     * Get validation rules for all attributes in this set.
     */
    public function getValidationRules(): array
    {
        $rules = [];

        foreach ($this->activeAttributes as $attribute) {
            $attrRules = $attribute->getValidationRules();

            // Override required status from pivot if set
            $pivotRequired = $attribute->pivot->is_required;
            if ($pivotRequired !== null) {
                $attrRules = array_filter($attrRules, fn($rule) => !in_array($rule, ['required', 'nullable']));
                array_unshift($attrRules, $pivotRequired ? 'required' : 'nullable');
            }

            $rules['attributes.' . $attribute->attribute_code] = $attrRules;
        }

        return $rules;
    }

    /**
     * Find attribute set by name.
     */
    public static function findByName(string $name): ?self
    {
        return static::where('set_name', $name)->first();
    }
}
