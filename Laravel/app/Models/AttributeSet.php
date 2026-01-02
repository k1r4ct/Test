<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;

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

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'attribute_set_attributes')
                    ->withPivot(['sort_order', 'is_required'])
                    ->withTimestamps()
                    ->orderBy('attribute_set_attributes.sort_order', 'asc');
    }

    public function activeAttributes()
    {
        return $this->attributes()->where('attributes.is_active', true);
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function attributeSetAttributes()
    {
        return $this->hasMany(AttributeSetAttribute::class);
    }

    // ==================== EVENTS ====================

    protected static function booted()
    {
        // Log attribute set creation
        static::created(function ($attributeSet) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::ecommerce()->info("Attribute set created", [
                'attribute_set_id' => $attributeSet->id,
                'set_name' => $attributeSet->set_name,
                'description' => $attributeSet->description,
                'is_active' => $attributeSet->is_active,
                'created_by' => $userName,
            ]);
        });

        // Log attribute set updates
        static::updated(function ($attributeSet) {
            $changes = $attributeSet->getChanges();
            $original = $attributeSet->getOriginal();

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

                SystemLogService::ecommerce()->info("Attribute set updated", [
                    'attribute_set_id' => $attributeSet->id,
                    'set_name' => $attributeSet->set_name,
                    'changes' => $changesForLog,
                    'updated_by' => $userName,
                ]);
            }
        });

        // Log attribute set deletion
        static::deleted(function ($attributeSet) {
            $userName = Auth::check() 
                ? Auth::user()->name . ' ' . Auth::user()->cognome 
                : 'Sistema';

            SystemLogService::ecommerce()->warning("Attribute set deleted", [
                'attribute_set_id' => $attributeSet->id,
                'set_name' => $attributeSet->set_name,
                'articles_count' => $attributeSet->articles()->count(),
                'deleted_by' => $userName,
            ]);
        });
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

    public function getGroupedAttributes(): array
    {
        $attributes = $this->activeAttributes()->get();

        return [
            'required' => $attributes->filter(function ($attr) {
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

    public function hasAttribute(int $attributeId): bool
    {
        return $this->attributes()->where('attributes.id', $attributeId)->exists();
    }

    public function hasAttributeByCode(string $code): bool
    {
        return $this->attributes()->where('attributes.attribute_code', $code)->exists();
    }

    public function getAttributeByCode(string $code): ?Attribute
    {
        return $this->attributes()->where('attributes.attribute_code', $code)->first();
    }

    public function addAttribute(int $attributeId, array $pivotData = []): void
    {
        $defaultPivot = [
            'sort_order' => $this->attributes()->count(),
            'is_required' => null,
        ];

        $this->attributes()->syncWithoutDetaching([
            $attributeId => array_merge($defaultPivot, $pivotData),
        ]);

        // Log attribute addition to set
        SystemLogService::ecommerce()->info("Attribute added to set", [
            'attribute_set_id' => $this->id,
            'set_name' => $this->set_name,
            'attribute_id' => $attributeId,
            'pivot_data' => array_merge($defaultPivot, $pivotData),
        ]);
    }

    public function removeAttribute(int $attributeId): void
    {
        $this->attributes()->detach($attributeId);

        // Log attribute removal from set
        SystemLogService::ecommerce()->info("Attribute removed from set", [
            'attribute_set_id' => $this->id,
            'set_name' => $this->set_name,
            'attribute_id' => $attributeId,
        ]);
    }

    public function updateAttributePivot(int $attributeId, array $pivotData): void
    {
        $this->attributes()->updateExistingPivot($attributeId, $pivotData);
    }

    public function reorderAttributes(array $attributeIdsInOrder): void
    {
        foreach ($attributeIdsInOrder as $index => $attributeId) {
            $this->attributes()->updateExistingPivot($attributeId, ['sort_order' => $index]);
        }
    }

    public function getFilterableAttributes()
    {
        return $this->activeAttributes()
                    ->where('attributes.is_filterable', true)
                    ->get();
    }

    public function getSearchableAttributes()
    {
        return $this->activeAttributes()
                    ->where('attributes.is_searchable', true)
                    ->get();
    }

    public function getVisibleAttributes()
    {
        return $this->activeAttributes()
                    ->where('attributes.is_visible_on_front', true)
                    ->get();
    }

    public function duplicate(string $newName): self
    {
        $newSet = $this->replicate();
        $newSet->set_name = $newName;
        $newSet->save();

        foreach ($this->attributes as $attribute) {
            $newSet->attributes()->attach($attribute->id, [
                'sort_order' => $attribute->pivot->sort_order,
                'is_required' => $attribute->pivot->is_required,
            ]);
        }

        // Log duplication
        SystemLogService::ecommerce()->info("Attribute set duplicated", [
            'original_set_id' => $this->id,
            'original_set_name' => $this->set_name,
            'new_set_id' => $newSet->id,
            'new_set_name' => $newSet->set_name,
            'attributes_copied' => $this->attributes()->count(),
        ]);

        return $newSet;
    }

    public function getValidationRules(): array
    {
        $rules = [];

        foreach ($this->activeAttributes as $attribute) {
            $attrRules = $attribute->getValidationRules();

            $pivotRequired = $attribute->pivot->is_required;
            if ($pivotRequired !== null) {
                $attrRules = array_filter($attrRules, fn($rule) => !in_array($rule, ['required', 'nullable']));
                array_unshift($attrRules, $pivotRequired ? 'required' : 'nullable');
            }

            $rules['attributes.' . $attribute->attribute_code] = $attrRules;
        }

        return $rules;
    }

    public static function findByName(string $name): ?self
    {
        return static::where('set_name', $name)->first();
    }
}