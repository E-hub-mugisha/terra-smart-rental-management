<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Building extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'property_id',
        'building_code',
        'name',
        'description',
        'number_of_floors',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'number_of_floors' => 'integer',
        ];
    }

    public function property()
    {
        return $this->belongsTo(\App\Models\Property::class);
    }

    public function floors()
    {
        return $this->hasMany(\App\Models\Floor::class);
    }

    public function units()
    {
        return $this->hasMany(\App\Models\Unit::class);
    }

    public function images()
    {
        return $this->hasMany(\App\Models\BuildingImage::class)->orderBy('sort_order');
    }
}
