<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'note', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('sort_order')->orderBy('code');
    }

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class)->orderBy('sort_order')->orderBy('id');
    }
}
