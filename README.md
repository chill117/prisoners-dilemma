# Prisoner's Dilemma (in PHP)

To satisfy my own curiosity, I coded an Iterated Prisoner's Dilemma in PHP. I programmed over a dozen different strategies, and pitted them against one another.

## Example Usage

Here's the simplest example usage:
```php
<?php

  	require_once(APPPATH . '/libraries/prisoners_dilemma.php');

		$prisoners_dilemma = new Prisoners_dilemma();
    
    $info = $prisoners_dilemma->run(25000, 20);

  	die(print_r($info));

?>
```

The result should look something like this:

```
Array
(
    [avg_points_per_match] => Array
        (
            [Naive_peace_maker] => 52.959
            [Tit_for_two_tats] => 52.893
            [Pavlov] => 52.581
            [True_peace_maker] => 52.478
            [Tit_for_tat] => 52.235
            [Always_cooperate] => 51.929
            [Grudger] => 50.949
            [Grudger_soft] => 50.053
            [Tit_for_two_tats_and_random] => 49.573
            [Adaptive] => 49.52
            [Remorseful_prober] => 48.515
            [Jekyll_and_hyde] => 48.39
            [Tit_for_tat_and_random] => 48.332
            [Random] => 46.32
            [Naive_prober] => 45.515
            [Tit_for_tat_suspicious] => 44.007
            [Always_defect] => 35.955
        )

    [total_matches] => Array
        (
            [Tit_for_two_tats_and_random] => 3046
            [Adaptive] => 3017
            [Pavlov] => 3011
            [True_peace_maker] => 3003
            [Random] => 2981
            [Always_cooperate] => 2975
            [Jekyll_and_hyde] => 2971
            [Tit_for_tat_suspicious] => 2953
            [Tit_for_tat] => 2933
            [Grudger_soft] => 2923
            [Naive_prober] => 2923
            [Remorseful_prober] => 2922
            [Naive_peace_maker] => 2914
            [Tit_for_two_tats] => 2882
            [Grudger] => 2873
            [Always_defect] => 2861
            [Tit_for_tat_and_random] => 2812
        )

    [total_points] => Array
        (
            [Pavlov] => 158321
            [True_peace_maker] => 157592
            [Always_cooperate] => 154490
            [Naive_peace_maker] => 154323
            [Tit_for_tat] => 153206
            [Tit_for_two_tats] => 152437
            [Tit_for_two_tats_and_random] => 150999
            [Adaptive] => 149403
            [Grudger] => 146376
            [Grudger_soft] => 146304
            [Jekyll_and_hyde] => 143767
            [Remorseful_prober] => 141761
            [Random] => 138080
            [Tit_for_tat_and_random] => 135909
            [Naive_prober] => 133039
            [Tit_for_tat_suspicious] => 129953
            [Always_defect] => 102868
        )

    [matches] => 25000
    [rounds_per_match] => 20
)
```