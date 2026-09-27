<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Характеристика товара: «Процессор», «Оперативная память».
 * Класс не назван Attribute, чтобы не путать с Eloquent-аксессорами.
 */
#[Table('attributes')]
#[Fillable(['name', 'slug', 'is_filterable', 'sort'])]
class ProductAttribute extends Model
{
    protected function casts(): array
    {
        return [
            'is_filterable' => 'boolean',
            'sort' => 'integer',
        ];
    }

    /** @return HasMany<AttributeValue, $this> */
    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class, 'attribute_id');
    }
}
