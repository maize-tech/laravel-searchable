<?php

namespace Maize\Searchable;

class SearchableAttribute
{
    /** @var \Illuminate\Database\Query\Expression|string */
    private $attribute;

    /** @var float */
    private $weight;

    public function __construct($attribute, float $weight)
    {
        $this->attribute = $attribute;
        $this->weight = $weight;
    }

    public function getAttribute(): \Illuminate\Database\Query\Expression|string
    {
        return $this->attribute;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }
}
