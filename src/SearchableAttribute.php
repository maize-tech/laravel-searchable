<?php

namespace Maize\Searchable;

use Illuminate\Database\Query\Expression;

class SearchableAttribute
{
    /** @var Expression|string */
    private $attribute;

    /** @var float */
    private $weight;

    public function __construct($attribute, float $weight)
    {
        $this->attribute = $attribute;
        $this->weight = $weight;
    }

    public function getAttribute(): Expression|string
    {
        return $this->attribute;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }
}
