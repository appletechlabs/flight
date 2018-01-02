<?php

namespace appletechlabs\flight\Recommendations;


use appletechlabs\flight\Helpers\Data;

class Recommendation
{

	public $ref;
	public $flightDetails;
	public $majCabin;
	public $majAirline;	
	public $stopInfo;
	public $airports;
	public $seatAvailability;
	public $origin;
	public $destination;
	public $fareSummary;

	function __construct($data = [])
	{
		 $this->loadFromArray($data);
		 $this->setOriginAndDestination();
	}

	protected function loadFromArray(array $data)
	  {
	       if (count($data) > 0) {
	          $this->ref = $data['ref'];
	          $this->flightDetails = $data['flightDetails'];
	          $this->majCabin = $data['majCabin'];
	          $this->majAirline = $data['majAirline'];
	          $this->stopInfo = $data['stopInfo'];
	          $this->airports = $data['airports'];
	          $this->seatAvailability = $data['seatAvailability'];         
	          $this->fareSummary = $data['fareSummary'];         
	    	}
	  }

	  private function setOriginAndDestination()
	  {
	  		$flightDetails = $this->flightDetails;
		  	if(!is_array($flightDetails))
		  	{
	 			$flightDetails = Data::dataToArray($flightDetails);
		  	}
		  	$start = 0;
		  	$end = count($flightDetails)-1;
		  	$this->origin = $flightDetails[$start]->departure;
		  	$this->destination = $flightDetails[$end]->arrival;
	  }

}

?>