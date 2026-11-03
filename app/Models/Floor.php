<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Floor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'building_id',
        'floor_number',
        'name',
        'description',
        'status'
    ];

    protected function casts(): array
    {
        return [
            'floor_number' => 'integer',
        ];
    }
    public function building()
    {
        return $this->belongsTo(\App\Models\Building::class);
    }

    public function units()
    {
        return $this->hasMany(\App\Models\Unit::class);
    }

    public function images()
    {
        return $this->hasMany(\App\Models\FloorImage::class)->orderBy('sort_order');
    }
}
