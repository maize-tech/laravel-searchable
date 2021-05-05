<?php

namespace HFarm\Searchable;

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

    /**
     * @return \Illuminate\Database\Query\Expression|string
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * @return float
     */
    public function getWeight(): float
    {
        return $this->weight;
    }
}
