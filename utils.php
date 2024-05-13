<?php

function array_splice_after_key($array, $key, $array_to_insert)
{
    $key_pos = array_search($key, array_keys($array));
    if($key_pos !== false){
        $key_pos++;
        $second_array = array_splice($array, $key_pos);
        $array = array_merge($array, $array_to_insert, $second_array);
    }
    return $array;
}