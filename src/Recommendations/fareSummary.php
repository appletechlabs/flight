<?php

namespace appletechlabs\flight\Recommendations;

class fareSummary
{
	public $currency;
	public $total;
	public $pax;

	function __construct($data = [])
	{
		 $this->loadFromArray($data);
	}

	protected function loadFromArray(array $data)
	{
		if (count($data) > 0) {
			$this->currency =  $data['currency'];
			$this->pax =  $data['pax'];
			$this->total =  $data['total'] ?? '';
		}
	}
	
}

?>