<?php

namespace appletechlabs\flight\Helpers;

class Data
{
    /**
     * Convert variables in to Array.
     */
    public static function dataToArray($data)
    {
        if (is_array($data)) {
            return $data;
        } else {
            $result = [];
            $result[] = $data;

            return $result;
        }
    }
}
