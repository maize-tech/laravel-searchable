<p align="center"><img src="/art/socialcard.png" alt="Social Card of Laravel Searchable"></p>

# Laravel Searchable 🔍

[![Latest Version on Packagist](https://img.shields.io/packagist/v/h-farm/laravel-searchable.svg?style=flat-square)](https://packagist.org/packages/h-farm/laravel-searchable)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/h-farm/laravel-searchable/run-tests?label=tests)](https://github.com/h-farm/laravel-searchable/actions?query=workflow%3ATests+branch%3Amaster)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/h-farm/laravel-searchable/Check%20&%20fix%20styling?label=code%20style)](https://github.com/h-farm/laravel-searchable/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/h-farm/laravel-searchable.svg?style=flat-square)](https://packagist.org/packages/h-farm/laravel-searchable)

Easily add weighted searches through model attributes and relationships.

This package currently supports `MySQL` and `PostgreSQL`.

## Installation

You can install the package via composer:

```bash
composer require h-farm/laravel-searchable
```

You can publish the config file with:
```bash
php artisan vendor:publish --provider="HFarm\Searchable\SearchableServiceProvider" --tag="searchable-config"
```

This is the content of the published config file:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Default match weight
    |--------------------------------------------------------------------------
    |
    | The weight of all searched words which match at least one of the
    | list of searchable attributes.
    | Defaults to 1.
    |
    */

    'default_match_weight' => 1,
];
```

## Usage

To use the package, add the `HFarm\Searchable\HasSearch` trait to all models you want to make searchable.

Once done, you can implement the `getSearchableAttributes` abstract method and return the list of attributes or relationships' attributes you want to search for.

You can also define the weight of each searchable attribute. If no weight is specified then `default_match_weight` will be taken from `config/searchable.php`.

Here's an example model including the `HasSearch` trait:

``` php
<?php

namespace App\Models;

use HFarm\Searchable\HasSearch;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Article extends Model
{
    use HasSearch;

    protected $fillable = [
        'id',
        'title',
        'body',
        'creator_name',
        'creator_surname',
    ];

    protected $casts = [
        'body' => 'array',
    ];

    /**
     * Get the model's searchable attributes.
     *
     * @return array
     */
    public function getSearchableAttributes(): array
    {
        return [
            'title' => 5, // Model attribute
            'body.en' => 2, // Single json key of a model attribute
            'tags.name', // Relationship attribute
            'tags.description.*', // All json keys of a relationship attribute
            DB::raw("CONCAT(creator_name, ' ', creator_surname)"), // Raw expressions are supported too
        ];
    }

    /**
     * Allows fetching the tags bound to current article instance
     *
     * @return BelongsToMany
     */
     public function tags(): BelongsToMany
     {
        return $this->belongsToMany(Tag::class)->withTimestamps();
     }
}
```

Now you can just search for a given term using the `scopeSearch` scope method:

``` php
use App\Models\Article;

$searchTerm = 'the search string';

Article::query()
    ->search($searchTerm)
    ->where('column', '=', 'something')
    ->get();
```

That's all!

The package generates an SQL query with an 'or' condition for each search term and each searchable fields.
The given query returns all models matching the search terms.
Also, search results are weighted, which means the query will be ordered by the most matching models.

If you don't want to order the search results by its match weight, you can set the `orderByWeight` flag to false:

``` php
use App\Models\Article;

$searchTerm = 'the search string';

Article::query()
    ->search($searchTerm, false)
    ->where('column', '=', 'something')
    ->get();
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Riccardo Dalla Via](https://github.com/riccardodallavia)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
