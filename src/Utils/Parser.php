<?php

namespace Maize\Searchable\Utils;

class Parser
{
    /**
     * Parse query string into separate words with wildcards if applicable.
     */
    public static function parseQuery(string $query, bool $fulltext = true): array
    {
        $formattedQuery = self::trim($query);
        $formattedQuery = self::lowercase($formattedQuery);
        $words = self::splitString($formattedQuery);

        if ($fulltext) {
            $words = self::addWildcards($words);
        }

        return $words;
    }

    /**
     * Split query string into words/phrases to be searched.
     */
    protected static function splitString(string $query): array
    {
        preg_match_all('/(?<=")[\w ][^"]+(?=")|(?<=\s|^)[^\s"]+(?=\s|$)/u', $query, $matches);

        return reset($matches);
    }

    /**
     * Transform the query string to lowercase.
     */
    protected static function lowercase(string $query): string
    {
        return mb_strtolower($query, 'UTF8');
    }

    /**
     * Trim the string.
     */
    protected static function trim(string $query): string
    {
        return trim($query);
    }

    /**
     * Add wildcard to the words.
     */
    protected static function addWildcards(array $words): array
    {
        return array_map(function ($word) {
            return "%$word%";
        }, $words);
    }
}
