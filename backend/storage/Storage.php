<?php

interface Storage
{
    function dicts(): array;
    function dict(string $id): Dict;
    function saveDict(Dict $d);

    function lastScores(string $dict_id): array;
    function saveScore(Score $score);
    function scores(): array;

    function entry(string $id): Entry;
    function saveEntry(Entry $e): Entry;
    function entries(array $ids): array;
    function allEntries(string $dict_id): array;
    function hasEntry(string $dict_id, Entry $e): bool;
}
