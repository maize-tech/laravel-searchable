<?php

namespace Maize\Searchable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Maize\Searchable\HasSearch;

class Article extends Model
{
    use HasSearch;

    /**
     * The searchable attributes used by the current test.
     */
    public static array $searchableAttributes = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'team_id',
        'title',
        'subtitle',
        'body',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'body' => 'array',
    ];

    /**
     * Get the team that owns the article.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the article comments.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get the article labels.
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class);
    }

    /**
     * Get the model's searchable attributes.
     */
    public function getSearchableAttributes(): array
    {
        return static::$searchableAttributes;
    }
}
