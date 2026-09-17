<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateSegment extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id', 'label', 'type', 'position', 'separator',
        'fixed_value', 'min_length', 'max_length', 'charset',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
