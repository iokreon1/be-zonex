<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Traits\UUID;

#[Fillable([
    'court_id',
    'image_path',
    'is_primary'
])]
class CourtImage extends Model
{
    use HasFactory, UUID;

    /**
     * Get the court that owns the image.
     */
    public function court()
    {
        return $this->belongsTo(Court::class);
    }
}
