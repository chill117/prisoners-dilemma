<?php

class Prisoners_dilemma
{

	/*
		http://en.wikipedia.org/wiki/Prisoner's_dilemma
	*/

	// Each Strategy's % of total population
	private $strategies = array(

		// Nice Strategies
		'Tit_for_tat' => 6,
		'Tit_for_tat_suspicious' => 6,
		'Tit_for_two_tats' => 6,
		'Tit_for_tat_and_random' => 6,
		'Tit_for_two_tats_and_random' => 6,
		'Always_cooperate' => 6,
		'Naive_peace_maker' => 4,
		'True_peace_maker' => 4,
		'Pavlov' => 4,

		// Neutral Strategies
		'Random' => 8,
		'Adaptive' => 8,

		// Mean Strategies
		'Always_defect' => 6,
		'Grudger' => 6,
		'Grudger_soft' => 6,
		'Naive_prober' => 6,
		'Remorseful_prober' => 6,
		'Jekyll_and_hyde' => 6,

	);

	private $use_population = false;

	private $points_per_outcome = array(
		// YOU:OPPONENT => YOUR POINTS
		'c:c' => 3,
		'c:d' => 1,
		'd:c' => 4,
		'd:d' => 1,
	);

	private $total_matches = array();
	private $total_points = array();
	private $avg_points_per_match = array();

	function __construct()
	{
		$this->prepare_variables();
	}

	public function use_population()
	{
		$this->use_population = true;
	}

	public function run($matches = 10000, $rounds_per_match = 20)
	{
		for ($i = 0; $i < $matches; $i++)
			$this->match($rounds_per_match);

		$this->calculate_averages();
		$this->sort_data();

		$info = array();
		$info['avg_points_per_match'] = $this->avg_points_per_match;
		$info['total_matches'] = $this->total_matches;
		$info['total_points'] = $this->total_points;
		$info['matches'] = $matches;
		$info['rounds_per_match'] = $rounds_per_match;

		return $info;
	}

	private function match($rounds_per_match)
	{
		$strategy1 = $this->pick_opponent();
		$strategy2 = $this->pick_opponent();

		$opponent1 = new $strategy1();
		$opponent2 = new $strategy2();

		for ($i = 0; $i < $rounds_per_match; $i++)
		{
			$decision1 = $opponent1->decide();
			$decision2 = $opponent2->decide();

			list($score1, $score2) = $this->calculate_scores($decision1, $decision2);

			$this->total_points[$strategy1] += $score1;
			$this->total_points[$strategy2] += $score2;

			$opponent1->remember(array(
				'opponent_choice' => $decision2,
				'opponent_score' => $score2,
				'my_choice' => $decision1,
				'my_score' => $score1,
			));

			$opponent2->remember(array(
				'opponent_choice' => $decision1,
				'opponent_score' => $score1,
				'my_choice' => $decision2,
				'my_score' => $score2,
			));
		}

		return array($score1, $score2);
	}

	private function pick_opponent()
	{
		if (!$this->use_population)
			// Purely random selection of opponent.
			return $this->pick_opponent__random();

		// Random, but weighted by population percentage.
		return $this->pick_opponent__random_weighted_by_population();
	}

	private function pick_opponent__random()
	{
		$strategy = array_rand($this->strategies);
		$this->total_matches[$strategy]++;

		return $strategy;
	}

	private function pick_opponent__random_weighted_by_population()
	{
		static $max = 1;
		static $ranges;

		if ($ranges === null)
			foreach ($this->strategies as $strategy => $population)
			{
				$i = $population * 10;

				$ranges[$strategy] = array();

				for ($n = $max; $n < ($i + $max); $n++)
					$ranges[$strategy][$n] = true;

				$max += $i;
			}

		$x = mt_rand(1, $max - 1);

		foreach ($ranges as $strategy => $range)
			if (isset($range[$x]))
			{
				$this->total_matches[$strategy]++;
				return $strategy;
			}

		die('Should never get this far.');
	}

	private function calculate_scores($decision1, $decision2)
	{
		$outcome = $decision1 . ':' . $decision2;

		return array(
			$this->points_per_outcome[$outcome],
			$this->points_per_outcome[strrev($outcome)],
		);
	}

	private function calculate_averages()
	{
		foreach ($this->total_points as $strategy => $total_points)
			$this->avg_points_per_match[$strategy] = round($total_points / $this->total_matches[$strategy], 3);
	}

	private function sort_data()
	{
		arsort($this->avg_points_per_match, SORT_NUMERIC);
		arsort($this->total_matches, SORT_NUMERIC);
		arsort($this->total_points, SORT_NUMERIC);
	}

	private function prepare_variables()
	{
		foreach ($this->strategies as $strategy => $dummy)
		{
			$this->total_points[$strategy] = 0;
			$this->total_matches[$strategy] = 0;
		}
	}
}

interface Prisoner_strategy_interface
{
	public function decide();
	public function remember($round);
}

class Prisoner_strategy
{
	protected $opponent_choices = array();
	protected $opponent_scores = array();
	protected $my_choices = array();
	protected $my_scores = array();

	public function remember($round)
	{
		$this->opponent_choices[] = $round['opponent_choice'];
		$this->opponent_scores[] = $round['opponent_score'];
		$this->my_choices[] = $round['my_choice'];
		$this->my_scores[] = $round['my_score'];
	}
}

/*
	Tit For Tat

	- Start by Cooperating
	- Repeat opponent's last choice
*/
class Tit_for_tat extends Prisoner_strategy implements Prisoner_strategy_interface
{
	public function decide()
	{
		if (count($this->opponent_choices) > 0)
			return end($this->opponent_choices);

		return 'c';
	}
}

/*
	Suspicious Tit For Tat

	- Start by Defecting
	- Repeat opponent's last choice
*/
class Tit_for_tat_suspicious extends Prisoner_strategy implements Prisoner_strategy_interface
{
	public function decide()
	{
		if (count($this->opponent_choices) > 0)
			return end($this->opponent_choices);

		return 'd';
	}
}

/*
	Tit For Tat and Random

	- Repeat opponent's last choice.
	- Choice is skewed by random setting.
*/
class Tit_for_tat_and_random extends Prisoner_strategy implements Prisoner_strategy_interface
{
	private $one_in_x_chance_of_random = 4;

	public function decide()
	{
		if (mt_rand(1, $this->one_in_x_chance_of_random) === 1)
			return mt_rand(1, 2) === 1 ? 'c' : 'd';

		if (count($this->opponent_choices) > 0)
			return end($this->opponent_choices);

		return 'c';
	}
}

/*
	Tit For Two Tats

	- Like Tit For Tat except that opponent must make the same
	choice twice in row before it is reciprocated.
*/
class Tit_for_two_tats extends Prisoner_strategy implements Prisoner_strategy_interface
{
	public function decide()
	{
		if (count($this->opponent_choices) > 1)
		{
			$last_two_choices = array_slice($this->opponent_choices, -2);

			if ($last_two_choices[0] === $last_two_choices[1])
				return $last_two_choices[0];
		}

		return 'c';
	}
}

/*
	Tit For Two Tats and Random

	- Like Tit For Tat except that opponent must make the same choice
	twice in a row before it is reciprocated.
	- Choice is skewed by random setting.*
*/
class Tit_for_two_tats_and_random extends Prisoner_strategy implements Prisoner_strategy_interface
{
	private $one_in_x_chance_of_random = 4;

	public function decide()
	{
		if (mt_rand(1, $this->one_in_x_chance_of_random) === 1)
			return mt_rand(1, 2) === 1 ? 'c' : 'd';

		if (count($this->opponent_choices) > 1)
		{
			$last_two_choices = array_slice($this->opponent_choices, -2);

			if ($last_two_choices[0] === $last_two_choices[1])
				return $last_two_choices[0];
		}

		return 'c';
	}
}

/*
	Always Cooperate

	- Name says it all
*/
class Always_cooperate extends Prisoner_strategy implements Prisoner_strategy_interface
{
	public function decide()
	{
		return 'c';
	}
}

/*
	Always Defect

	- Name says it all
*/
class Always_defect extends Prisoner_strategy implements Prisoner_strategy_interface
{
	public function decide()
	{
		return 'd';
	}
}

/*
	Random

	- 50% Chance of Cooperating
	- 50% Chance of Defecting
*/
class Random extends Prisoner_strategy implements Prisoner_strategy_interface
{
	public function decide()
	{
		return mt_rand(1, 2) === 1 ? 'c' : 'd';
	}
}

/*
	Grudger

	- Co-operate until the opponent defects. Then always defect unforgivingly.
*/
class Grudger extends Prisoner_strategy implements Prisoner_strategy_interface
{
	private $grudge = false;

	public function decide()
	{
		if (
			$this->grudge ||
			(
				count($this->opponent_choices) > 0 &&
				in_array('d', $this->opponent_choices)
			)
		)
		{
			$this->hold_grudge();
			return 'd';
		}

		return 'c';
	}

	private function hold_grudge()
	{
		$this->grudge = true;
	}
}

/*
	Soft Grudger

	- Cooperates until the opponent defects, in such case opponent is punished with d,d,d,d,c,c.
*/
class Grudger_soft extends Prisoner_strategy implements Prisoner_strategy_interface
{
	private $grudge = 0;

	public function decide()
	{
		if (
			$this->grudge > 0 ||
			(
				count($this->opponent_choices) > 0 &&
				end($this->opponent_choices) === 'd'
			)
		)
		{
			if ($this->grudge > 0)
				$this->grudge--;
			else
				$this->grudge += 4;

			return 'd';
		}

		return 'c';
	}
}

/*
	Adaptive

	- Starts with c,c,c,c,c,c,d,d,d,d,d and then takes choices
	which have given the best average score re-calculated after every move.
*/
class Adaptive extends Prisoner_strategy implements Prisoner_strategy_interface
{
	private $start_choices = array(
		'c', 'c', 'c', 'c', 'c', 'c',
		'd', 'd', 'd', 'd', 'd'
	);

	public function decide()
	{
		if (isset($this->start_choices[count($this->my_choices)]))
			return $this->start_choices[count($this->my_choices)];

		$c_avg = $this->calculate_average('c');
		$d_avg = $this->calculate_average('d');

		return $c_avg > $d_avg ? 'c' : 'd';
	}

	private function calculate_average($choice)
	{
		$total_score = 0;
		$n = 0;

		foreach ($this->my_choices as $i => $my_choice)
			if ($my_choice === $choice)
			{
				$total_score += $this->my_scores[$i];
				$n++;
			}

		return $total_score / $n;
	}
}

/*
	Naive Prober

	- Tit For Tat with Random Defection
	- Repeat opponent's last choice, but sometimes probe by defecting in lieu of co-operating.
*/
class Naive_prober extends Prisoner_strategy implements Prisoner_strategy_interface
{
	private $one_in_x_chance_of_defect = 4;

	public function decide()
	{
		if (count($this->opponent_choices) > 0)
		{
			$opponent_last_choice = end($this->opponent_choices);

			if (
				$opponent_last_choice === 'c' &&
				mt_rand(1, $this->one_in_x_chance_of_defect) === 1
			)
				return $this->probe();

			return $opponent_last_choice;
		}

		return 'c';
	}

	private function probe()
	{
		return 'd';
	}
}

/*
	Remorseful Prober

	- Tit For Tat with Random Defection
	- Repeat opponent's last choice, but sometimes probe by defecting in lieu of co-operating.
	- If the opponent defects in response to probing, show remorse by co-operating once.*
*/
class Remorseful_prober extends Prisoner_strategy implements Prisoner_strategy_interface
{
	private $one_in_x_chance_of_defect = 4;
	private $probed_last_choice = false;

	public function decide()
	{
		if (count($this->opponent_choices) > 0)
		{
			$opponent_last_choice = end($this->opponent_choices);
			
			if ($this->probed_last_choice)
			{
				$probed_last_choice = false;

				if ($opponent_last_choice === 'd')
					return 'c';
			}

			if (
				$opponent_last_choice === 'c' &&
				mt_rand(1, $this->one_in_x_chance_of_defect) === 1
			)
				return $this->probe();

			return $opponent_last_choice;
		}

		return 'c';
	}

	private function probe()
	{
		$this->probed_last_choice = true;
		return 'd';
	}
}

/*
	Jekyll and Hyde

	- Alternate between Defect and Cooperate
*/
class Jekyll_and_hyde extends Prisoner_strategy implements Prisoner_strategy_interface
{
	public function decide()
	{
		return count($this->my_scores) % 2 ? 'c' : 'd';
	}
}

/*
	Naive Peace Maker

	- Tit For Tat with Random Co-operation
	- Repeat opponent's last choice, but sometimes make peace by
	co-operating in lieu of defecting.*
*/
class Naive_peace_maker extends Prisoner_strategy implements Prisoner_strategy_interface
{
	private $one_in_x_chance_of_cooperate = 4;

	public function decide()
	{
		if (count($this->opponent_choices) > 0)
		{
			$opponent_last_choice = end($this->opponent_choices);

			if (
				$opponent_last_choice === 'd' &&
				mt_rand(1, $this->one_in_x_chance_of_cooperate) === 1
			)
				return 'c';

			return $opponent_last_choice;
		}

		return 'c';
	}
}

/*
	True Peace Maker

	- Hybrid of Tit For Tat and Tit For Two Tats with Random Co-operation
	- Co-operate unless opponent defects twice in a row, then defect once,
	but sometimes make peace by co-operating in lieu of defecting.*
*/
class True_peace_maker extends Prisoner_strategy implements Prisoner_strategy_interface
{
	private $one_in_x_chance_of_cooperate = 4;

	public function decide()
	{
		if (count($this->opponent_choices) > 1)
		{
			if (end($this->my_choices) === 'd')
				return 'c';

			$last_two_choices = array_slice($this->opponent_choices, -2);

			if (
				$last_two_choices[0] === $last_two_choices[1] &&
				$last_two_choices[0] === 'd'
			)
			{
				if (mt_rand(1, $this->one_in_x_chance_of_cooperate) === 1)
					return 'c';

				return 'd';
			}
		}

		return 'c';
	}
}

/*
	Pavlov

	- Cooperate at start and when last choice matches last choice of opponent.
	- Defect when last choice is different from last choice of opponent
*/
class Pavlov extends Prisoner_strategy implements Prisoner_strategy_interface
{
	public function decide()
	{
		if (
			count($this->opponent_choices) > 0 &&
			$this->last_choice_differs_from_opponent()
		)
			return 'd';

		return 'c';
	}

	private function last_choice_differs_from_opponent()
	{
		return end($this->opponent_choices) !== end($this->my_choices);
	}
}