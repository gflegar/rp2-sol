<?php

/**
 * Generate random string.
 *
 * @param $length
 */
function randString($length) {
    $pool = "abcdefghijklmnopqrstuxyvwzABCDEFGHIJKLMNOPQRSTUXYVWZ";
    $poolSize = strlen($pool);
    $result = "";
    for ($i = 0; $i < $length; ++$i) {
        $result .= $pool[mt_rand(0, $poolSize - 1)];
    }
    return $result;
}

