<?php

namespace Maize\Searchable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Get the tag users.
     *
     * @return MorphToMany
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'taggable');
    }
}
