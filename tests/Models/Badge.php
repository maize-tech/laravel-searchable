<?php

namespace Maize\Searchable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Badge extends Model
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
     * Get the badge team.
     *
     * @return MorphOne
     */
    public function team(): MorphOne
    {
        return $this->morphOne(Team::class, 'morphable');
    }
}
