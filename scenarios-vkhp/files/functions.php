<?php

function strpos_array(string $haystack, array $needle) {
    foreach ($needle as $what) {
        if (($pos = strpos($haystack, $what)) !== false) {
            return $pos;
        }
    }
    return false;
}
