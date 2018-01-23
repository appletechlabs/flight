<?php



namespace appletechlabs\flight;
/**
 * LoadParamsFromArray
 *
 * Povides the ability to load parameters in the constructor through an associative array
 *
 * The keys in the associative array should be property names, and if that matches, the values will be set
 * to those properties.
 *
 * @package flight
 * @author Arosha De Silva <aroshajayasanka@gmail.com>
 */
class LoadParamsFromArray
{
	
	/**
     * Construct Request Options object with initialization array
     *
     * @param array $params Initialization parameters
     */
    public function __construct($params = [])
    {
        foreach ($params as $propName => $propValue) {
            if (property_exists($this, $propName)) {
                $this->$propName = $propValue;
                //TODO support for objects that must be instantiated???
            }
        }
    }
}

?>