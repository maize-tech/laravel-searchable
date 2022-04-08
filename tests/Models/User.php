<?php

namespace Maize\Searchable\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;
use Maize\Searchable\HasSearch;

class User extends Model
{
    use HasFactory;
    use HasSearch;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'description',
    ];

    /**
     * Get the team that owns the user.
     *
     * @return BelongsTo
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return MorphToMany
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * @return HasMany
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * @return MorphTo
     */
    public function morphs(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the model's searchable attributes.
     *
     * @return array
     */
    public function getSearchableAttributes(): array
    {
        $table = self::getTable();

        return [
            'first_name',
            'last_name',
            'email' => 5,
            'description',
            // concat first_name and last_name (sqlite does not support CONCAT function)
            DB::raw("{$table}.first_name || ' ' || {$table}.last_name"),
            'team.name',
        ];
    }
}
