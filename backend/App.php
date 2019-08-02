<?php

class App
{
    /**
     * How many correct answers needed for an entry to be "finished".
     */
    const GOAL = 10;

    /**
     * How many entries are in the "learning pool".
     * This limit is applied separately for both directions,
     * so the actual pool limit is twice this value.
     */
    const WINDOW = 200;

    private $storage;

    function setStorage(Storage $s)
    {
        $this->storage = $s;
    }

    function generateTest(string $dict_id): Test
    {
        $storage = $this->storage;
        $size = 20;
        $entries = $storage->allEntries($dict_id);
        $pick1 = $this->pick($entries, $size, 0);
        $pick2 = $this->pick($entries, $size, 1);

        // Mark the entries as touched.
        foreach (array_merge($pick1, $pick2) as $e) {
            if (!$e->touched) {
                $e->touched = 1;
                $storage->saveEntry($e);
            }
        }

        $questions1 = [];
        foreach ($pick1 as $entry) {
            $questions1[] = new Question($entry, false);
        }
        $questions2 = [];
        foreach ($pick2 as $entry) {
            $questions2[] = new Question($entry, true);
        }

        $test = new Test($questions1, $questions2);
        return $test;
    }

    private function pick(array $entries, int $size, $dir): array
    {
        $unfinished = [];
        foreach ($entries as $e) {
            if ($dir == 0 && $e->answers1 >= self::GOAL) {
                continue;
            }
            if ($dir == 1 && $e->answers2 >= self::GOAL) {
                continue;
            }
            $unfinished[] = $e;
        }
        usort($unfinished, function ($a, $b) {
            return $b->touched <=> $a->touched;
        });
        $unfinished = array_slice($unfinished, 0, self::WINDOW);
        shuffle($unfinished);
        $entries = array_slice($unfinished, 0, $size);
        return $entries;
    }

    function verifyTest(string $dict_id, array $answers): TestResults
    {
        $storage = $this->storage;
        $questions = [];
        $correct = [];
        foreach ($answers as $a) {
            $entry = $storage->entry($a->entryID);
            $question = new Question($entry, $a->reverse);
            $questions[] = $question;
            $ok = checkAnswer($question, $a->answer);
            $correct[] = $ok;
            if (!$ok) {
                continue;
            }

            // Update correct answer counters
            // For all questions that are correct, increment the corresponding counter (dir 0/1) and save.
            if ($question->reverse) {
                $question->entry()->answers2++;
            } else {
                $question->entry()->answers1++;
            }
            $storage->saveEntry($question->entry());
        }

        // Save a score record.
        $right = 0;
        $wrong = 0;
        foreach ($correct as $ok) {
            if ($ok) $right++;
            else $wrong++;
        }
        $score = new Score;
        $score->dict_id = $dict_id;
        $score->right = $right;
        $score->wrong = $wrong;
        $storage->saveScore($score);

        return new TestResults($dict_id, $questions, $answers, $correct);
    }

    function appendWords(string $dict_id, array $entries): array
    {
        $storage = $this->storage;
        $added = 0;
        $skipped = 0;
        foreach ($entries as $entry) {
            if (!$storage->hasEntry($dict_id, $entry)) {
                $storage->saveEntry($entry);
                $added++;
            } else {
                $skipped++;
            }
        }
        return compact('added', 'skipped');
    }

    function dicts(): array
    {
        return $this->storage->dicts();
    }

    function dictStats(string $dict_id): Stats
    {
        $storage = $this->storage;
        $entries = $storage->allEntries($dict_id);

        $stats = new Stats;
        $stats->totalEntries = count($entries);

        foreach ($entries as $e) {
            $isfinished = $e->answers1 >= self::GOAL && $e->answers2 >= self::GOAL;
            if ($isfinished) {
                $stats->finished++;
                continue;
            }
            if ($e->touched) {
                $stats->touched++;
            }
        }

        $stats->successRate = successRate($storage, $dict_id);
        return $stats;
    }

    function hint(Question $q)
    {
        $storage = $this->storage;
        $entry = $q->entry();
        $sim = $storage->similars($entry, $q->reverse);
        if (count($sim) == 0) {
            return null;
        }
        $field = $q->reverse ? 'q' : 'a';
        $values = [];
        foreach ($sim as $entry) {
            $values[] = $entry->$field;
        }
        $hint = h($q->entry()->$field, $values);
        return preg_replace('/\*+/', '...', $hint);
    }

    function entry(string $id): Entry
    {
        return $this->storage->entry($id);
    }

    function updateEntry(Entry $entry)
    {
        $this->storage->saveEntry($entry);
    }
}

function successRate(Storage $s, string $dict_id): float
{
    $scores = $s->lastScores($dict_id);
    $total = 0;
    $n = 0;
    foreach ($scores as $score) {
        $n++;
        $total += $score->right / ($score->right + $score->wrong);
    }
    return $n > 0 ? $total / $n : 1;
}

function h($word, $others)
{
    $list = array_unique(array_merge([$word], $others));
    if (count($list) < 2) return null;

    $first = array_map(function ($str) {
        return mb_substr($str, 0, 1);
    }, $list);

    if (count(array_unique($first)) == count($first)) {
        return $first[0] . (mb_strlen($word) > 1 ? '*' : '');
    }
    $rest = function ($str) {
        return mb_substr($str, 1);
    };
    $replace = $first[0] == ' ' ? ' ' : '*';
    return $replace . h($rest($word), array_map($rest, $others));
}

function checkAnswer(Question $q, $answer)
{
    $realAnswer = $q->reverse ? $q->entry()->q : $q->entry()->a;
    return mb_strtolower($realAnswer) == mb_strtolower($answer);
}
