<?php

namespace Maize\Searchable\Tests\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Maize\Searchable\HasSearch;

class Document extends Model
{
    use HasSearch;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'code',
        'payload',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Get the model's searchable attributes.
     */
    public function getSearchableAttributes(): array
    {
        return [
            'code' => 5,
            'payload.name' => 3,
            'payload.description',
        ];
    }
}
