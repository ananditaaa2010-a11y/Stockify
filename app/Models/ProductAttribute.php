<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    use HasFactory;

     protected $fillable = [
        'product_id',
         'definition_id',
         'name',
         'value',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function definition()
    {
        return $this->belongsTo(ProductAttributeDefinition::class, 'definition_id');
    }
}
