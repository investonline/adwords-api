<?php

namespace InvestOnlineAdWordsApi;

/**
 * @param callable $callback
 * @param array $array
 * @return float|int
 */
function array_average(callable $callback, array $array)
{
    if (count($array) === 0) return 0;

    $total = array_reduce($array, function($total, $item) use ($callback) {
        return $total + $callback($item);
    }, 0);

    return round($total / count($array));
}
