<?php

class Question
{
	public $reverse;
	private $e;

	function entry()
	{
		return $this->e;
	}

	/**
	 * Returns true if the given answer is correct for this question.
	 */
	function checkAnswer(string $answer): bool
	{
		$realAnswer = $this->reverse ? $this->entry()->q : $this->entry()->a;
		return mb_strtolower($realAnswer) == mb_strtolower($answer);
	}

	function __construct(Entry $e, $reverse)
	{
		$this->reverse = $reverse;
		$this->e = $e;
	}

	private function id()
	{
		return $this->e->id;
	}

	private function q()
	{
		return $this->reverse ? $this->e->a : $this->e->q;
	}

	function a()
	{
		return $this->reverse ? $this->e->q : $this->e->a;
	}

	private function times()
	{
		return $this->reverse ? $this->e->answers2 : $this->e->answers1;
	}

	function format()
	{
		return [
			'id' => $this->id(),
			'q' => $this->q(),
			'a' => $this->a(),
			'times' => $this->times(),
			'wikiURL' => $this->e->wikiURL(),
			'dir' => $this->reverse ? 1 : 0
		];
	}
}
