<?php

use havana\dbobject;

class Dict extends dbobject
{
    const GOAL = 10;
    const TABLE_NAME = 'dicts';

    public $name;

    static function load($id)
    {
        $s = new self($id);
        $s->id = $id;
        return $s;
    }

    function pick($size, $dir)
    {
        return Entry::pick($this->id, $size, $dir);
    }

    function append($tuples)
    {
        foreach ($tuples as $t) {
            $entry = new Entry;
            $entry->dict_id = $this->id;
            $entry->q = $t[0];
            $entry->a = $t[1];
            if ($this->has($entry)) {
                continue;
            }
            $entry->save();
        }
        return $this;
    }

    private function has(Entry $e)
    {
        return Entry::db()->getValue(
            "select count(*)
            from words
            where dict_id = ? and q = ? and a = ?",
            $this->id,
            $e->q,
            $e->a
        ) > 0;
    }

    function stats()
    {
        $goal = self::GOAL;
        $r = self::db()->getRow(
            'select count(*) as n, sum(answers1+answers2) as ok
            from words
            where dict_id = ?',
            $this->id
        );
        $n = $r['n'];
        $ok = $r['ok'];

        $finished = self::db()->getValue(
            "select sum(a1 + a2)
            from (select answers1 >= $goal as a1, answers2 >= $goal as a2
                from words where dict_id = ?) a",
            $this->id
        );
        $started = self::db()->getValue(
            "select count(*)
            from words
            where dict_id = ?
                and answers1 + answers2 between 1 and 2 * $goal - 1",
            $this->id
        );

        return [
            'pairs' => $n,
            'progress' => $ok / $goal / $n / 2,
            'finished' => $finished / 2,
            'started' => $started,
            'successRate' => $this->successRate()
        ];
    }

    private function successRate()
    {
        $scores = self::db()->getValues(
            'select 1.0 * right / (right + wrong)
            from results
            where dict_id = ?
            order by id desc
            limit 10',
            $this->id
        );
        $n = count($scores);
        if ($n == 0) return 1;
        return array_sum($scores) / $n;
    }
}
