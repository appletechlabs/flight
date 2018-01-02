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
		 $this->setTotal();
	}

	protected function loadFromArray(array $data)
	{
		if (count($data) > 0) {
			$this->currency =  $data['currency'];
			$this->pax =  $data['pax'];
		}
	}

	protected function setTotal()
	{
		if (is_array($this->pax)) 
		{
			foreach ($this->pax as $paxItem) 
			{
				$this->total += $paxItem->total;
			}
		}
		else{
			$this->total = true;
		}
		
	}	
}

?>