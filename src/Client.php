<?php




use Amadeus\Client;
use Amadeus\Client\Params;
use Amadeus\Client\Result;
use Amadeus\Client\RequestOptions\PnrRetrieveOptions;

use Amadeus\Client\RequestOptions\FareMasterPricerCalendarOptions;
use Amadeus\Client\RequestOptions\Fare\MPPassenger;
use Amadeus\Client\RequestOptions\Fare\MPItinerary;
use Amadeus\Client\RequestOptions\Fare\MPDate;
use Amadeus\Client\RequestOptions\Fare\MPLocation;

use Amadeus\Client\RequestOptions\SalesReportsDisplayQueryReportOptions;


namespace appletechlabs\flight;

class Client
{
	private $ApiProvider = 'AMADEUS';
	
	public function setup()
	{
		$params = new Params;
		return $params;
	}




}


?>