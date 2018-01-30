<?php

namespace appletechlabs\flight\Helpers;

/**
 * Class Data
 * @package appletechlabs\flight\Helpers
 */
class Data
{


    /**
     * @param $data
     * @return array
     */
    public static function dataToArray($data)
	{
		if(is_array($data))
		{
			return $data;
		}
		else
		{
	        $result = [];
	        $result[] = $data;
	        return $result;
		}

	}
}
