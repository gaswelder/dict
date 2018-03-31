<?php
use havana\dbobject;

class Entry extends dbobject
{
    const TABLE_NAME = 'words';
    const DATABASE = 'sqlite://dict.sqlite';

    public $q;
    public $a;
    public $answers1 = 0;
    public $answers2 = 0;
    public $id;

    static function stats()
    {
        $goal = Dict::GOAL;
        $r = self::db()->getRow('select count(*) as n, sum(answers1+answers2) as ok from words');
        $n = $r['n'];
        $ok = $r['ok'];

        $finished = self::db()->getValue("select sum(a1 + a2) from (select answers1 >= $goal as a1, answers2 >= $goal as a2 from words) a");
        $started = self::db()->getValue("select count(*) from words where answers1 + answers2 between 1 and 2 * $goal - 1");
        return [
            'pairs' => $n,
            'progress' => $ok / $goal / $n / 2,
            'finished' => $finished / 2,
            'started' => $started
        ];
    }

    /**
     * Returns a given number of random questions.
     *
     * @param int $n Number of questions
     * @param int $dir Translation direction: 0 for direct, 1 for reverse
     * @return array
     */
    static function pick($n, $dir)
    {
        $f = $dir == 0 ? 'answers1' : 'answers2';
        $n = intval($n);
        $goal = Dict::GOAL;

        $rows = self::db()->getRows("select * from words where $f < $goal order by random() limit $n");
        return array_map(function (Entry $e) use ($dir) {
            return new Question($e, $dir == 1);
        }, self::fromRows($rows));
    }

    function toRow()
    {
        return [$this->q, $this->a, $this->answers1, $this->answers2];
    }
}
