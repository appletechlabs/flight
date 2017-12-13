<?php

namespace appletechlabs\flight\Providers;

use Amadeus\Client as AmadeusClient;
use Amadeus\Client\Params;
use Amadeus\Client\Result;
use Amadeus\Client\RequestOptions\PnrRetrieveOptions;

use Amadeus\Client\RequestOptions\FareMasterPricerCalendarOptions;
use Amadeus\Client\RequestOptions\FareMasterPricerTbSearch;

use Amadeus\Client\RequestOptions\Fare\MPPassenger;
use Amadeus\Client\RequestOptions\Fare\MPItinerary;
use Amadeus\Client\RequestOptions\Fare\MPDate;
use Amadeus\Client\RequestOptions\Fare\MPLocation;

use Amadeus\Client\RequestOptions\FareInformativePricingWithoutPnrOptions;
use Amadeus\Client\RequestOptions\Fare\InformativePricing\Passenger;
use Amadeus\Client\RequestOptions\Fare\InformativePricing\Segment;

use Amadeus\Client\RequestOptions\AirFlightInfoOptions;

use Amadeus\Client\RequestOptions\AirRetrieveSeatMapOptions;
use Amadeus\Client\RequestOptions\Air\RetrieveSeatMap\FlightInfo;


use Amadeus\Client\RequestOptions\SalesReportsDisplayQueryReportOptions;
use Psr\Log\NullLogger;

class AmadeusSoapProvider
{
	const PROVIDER = "AmadeusSoap";

	public $officeId;
	public $userId;
	public $passwordData;
	public $wsdl;
	public $passwordLength;
	public $receivedFrom;
  
	
	public function setup()
	{
		$params = new Params([
            'returnXml' => false,
            'authParams' => [
                'officeId' => $this->officeId,
                'userId' => $this->userId, 
                'passwordData' => $this->passwordData, 
                'passwordLength' => $this->passwordLength,

            ],
            'sessionHandlerParams' => [
                'soapHeaderVersion' => AmadeusClient::HEADER_V4,
                'wsdl' => $this->wsdl, 
                'stateful' => false,
                'logger' => new NullLogger()
            ],
            'requestCreatorParams' => [
                'receivedFrom' => $this->receivedFrom 
            ]
        ]);

        $this->amadeusClient = new AmadeusClient($params);
        $this->amadeusClient->securityAuthenticate();
	}



  public function securitySignOut()
  {
    $this->amadeusClient->securitySignOut();
  }

  public function getPassengersCount($passengers)
  {

  	foreach ($passengers as $passenger => $value) {
  		switch ($passenger) 
  		{
  			case 'ADT':
  				$MPPassenger = new MPPassenger([
                   'type' => MPPassenger::TYPE_ADULT,
                   'count' => $value
               	]);
  				break;
  			
  			case 'CH':
  				$MPPassenger = new MPPassenger([
                   'type' => MPPassenger::TYPE_CHILD,
                   'count' => $value
               	]);
  				break;
  			
  			case 'INF':
  				$MPPassenger = new MPPassenger([
                   'type' => MPPassenger::TYPE_INFANT,
                   'count' => $value
               	]);
  				break;
  			
  			default:
  				# code...
  				break;
  		}
  		$MPPassengers[] = $MPPassenger;
  	}
  	return $MPPassengers;

  }
  public function getRangeMode($rangeModeText)
  {
  	switch ($rangeModeText) {
  		case 'RANGEMODE_MINUS_PLUS':
  			return MPDate::RANGEMODE_MINUS_PLUS;
  			break;
  		
  		case 'RANGEMODE_MINUS':
  			return MPDate::RANGEMODE_MINUS;
  			break;
  		
  		case 'RANGEMODE_PLUS':
  			return MPDate::RANGEMODE_PLUS;
  			break;
  		
  		default:
  			# code...
  			break;
  	}
  }

  public function getItinerarCount($itineraries)
  {

  	foreach ($itineraries as $itinerary => $value) {

  		//var_dump($value['departureLocation']);
  		if (isset($value['rangeMode'])) {
  			$MPItinerary = new MPItinerary([
                   'departureLocation' => new MPLocation(['city' => $value['departureLocation']]),
                   'arrivalLocation' => new MPLocation(['city' => $value['arrivalLocation']]),
                   'date' => new MPDate([
                       'date' => $value['date'],
                       'rangeMode' => $this->getRangeMode($value['rangeMode']),
                       'range' => $value['range'],
                   ])
               ]);
  		}
  		else
  		{
  			$MPItinerary = new MPItinerary([
                   'departureLocation' => new MPLocation(['city' => $value['departureLocation']]),
                   'arrivalLocation' => new MPLocation(['city' => $value['arrivalLocation']]),
                   'date' => new MPDate([
                       'date' => $value['date'],
                   ])
               ]);
  		}	
  		$MPItineraries[] = $MPItinerary;
  	}
  	return $MPItineraries;

  }


  	public function getflightPrice($ref, $recommendations)
	{
	    //var_dump($recommendations);
	    foreach ($recommendations as $recommendation) 
	    {
	        $segments = $recommendation->segmentFlightRef;
	        foreach ($segments as $segmentKey => $segment)
	        { 
	           if (isset($segment->referencingDetail)) 
	           	{
	           		if (is_array($segment->referencingDetail)) 
	           		{
	           			foreach ($segment->referencingDetail as $rd) 
	           			{
	           				if ($rd->refQualifier == "S") 
	           				{
	           					$refQualifier = $rd->refQualifier;
	                			$refNumber = $rd->refNumber;
	           				}
	           			}
	           		}
	           		else
	           		{
	           			$refQualifier = $segment->referencingDetail->refQualifier;
	                	$refNumber = $segment->referencingDetail->refNumber;
	           		}
	                
	            }
	            else
	            {
	            	/*if only one recommendation for this particular pricing*/
	            	//var_dump($segment);
	            	if (is_array($segment)) {
	            		foreach ($segment as $sg) 
	           			{
	           				if ($sg->refQualifier == "S") 
	           				{
	           					$refQualifier = $sg->refQualifier;
	                			$refNumber = $sg->refNumber;
	           				}
	           			}
	            	}
	            	else
	            	{
	            		$refQualifier = $segment->refQualifier;
	                	$refNumber = $segment->refNumber;
	            	} 
	            }

	            if ($refNumber == $ref) 
	            {
	                $price = $recommendation->paxFareProduct;
	                return $price;
	            }
	        }
	    }
	}

	public function calendarMin($amflightResults)
	{
		//var_dump($amflightResults->response->flightIndex[0]->groupOfFlights);
		/* This doesn't work with multiple itineray options */
		/* Todo : array check*/
	    $groupOfFlights = $amflightResults->response->flightIndex->groupOfFlights;
	    $recommendations = $amflightResults->response->recommendation;
	    foreach ($groupOfFlights as $key => $flight) 
	    {
	        $propFlightRef = $flight->propFlightGrDetail->flightProposal[0]->ref;
	        $flightPrice = $this->getflightPrice($propFlightRef,$recommendations);
	        $date = $flight->flightDetails[0]->flightInformation->productDateTime->dateOfDeparture;
	        $dateOfDeparture  = date_create_from_format('dmy',$date);

	        $result->flight[$key] = new \stdClass();/* fix undefined stdObject warning */
	        $result->flight[$key]->ref = $propFlightRef;
	        $result->flight[$key]->dateOfDeparture =  $dateOfDeparture->format('d-m-y');
	        $result->flight[$key]->dateMonth =  $dateOfDeparture->format('d M');
	        $result->flight[$key]->flightPrice =  $flightPrice;
	    }

	   usort($result->flight,function ($a, $b){
			    return strtotime($a->dateOfDeparture) - strtotime($b->dateOfDeparture);
			});

	   return $result->flight;
	}
	public function getCabinDescription($majCabin)
	{
		 switch($majCabin) {
            case 'C':
                return "Business";
                break;
            case 'F':
                return "First, supersonic";
                break;
            case 'M':
                return "Economic Standard";
                break;          
            case 'W':
                return "Economic Premium";
                break;          
            case 'Y':
                return "Economic";
                break;          
            default:
                return "N/A";
        }
	}

	public function seatStatus($groupOfFares)
	{
		$status = "Seats Available";
		if (is_array($groupOfFares)) {
			foreach ($groupOfFares as $key => $Fare) {
				if (($Fare->productInformation->cabinProduct->avlStatus)<9) {
	                $status = "few Seats Available";
	            }
			}
		}
		else
	   	{
	    if ($groupOfFares->productInformation) {
	        if ($groupOfFares->productInformation) {
	           if (($groupOfFares->productInformation->cabinProduct->avlStatus)<9) {
	                $status = "few Seats Available";
	            } 
	        }
	    }   
	   }
	   return $status;
	}
	public function flightStops($flightDetails)
	{
		//var_dump($flightDetails);
		$stopInfo =  new \stdClass();
		$stopInfo->stops = "Direct";
    	$stopInfo->TimeDelay = " ";

    	if (is_array($flightDetails)) {
    		//var_dump($flightDetails);
    		if (count($flightDetails)-1 == 1) {
	            $stopInfo->stops = (count($flightDetails)-1 ) . " Stop";
	        }
	        else
	        {
	        	 $stopInfo->stops = (count($flightDetails)-1 ) . " Stops";
	        }

    	}

    	 return $stopInfo;
	}

	public function optimizeInfo($flightDetails)
	{
		$info = new \stdClass();
		$info->stopInfo = "Direct";
		$airports = [];

		if (is_array($flightDetails)) {

			if (count($flightDetails)-1 == 1) {
	            $info->stopInfo = (count($flightDetails)-1 ) . " Stop";
	        }
	        else
	        {
	        	 $info->stopInfo = (count($flightDetails)-1 ) . " Stops";
	        }

			foreach ($flightDetails as $flight) {


				foreach ($flight->flightInformation->location as $location) {
					if ($location->locationId == end($airports))
						continue;
					$airports[] = $location->locationId;
				}
			}
		}
		else
		{
			# Direct Flights
			foreach ($flightDetails->flightInformation->location as $location) {
				if ($location->locationId == end($airports))
					continue;
				$airports[] = $location->locationId;
			}
		}
		$info->airports =  $airports;
		return $info;
	}

	public function optimizeResults($amflightResults)
	{
		//var_dump($amflightResults);
		/* This doesn't work with multiple itineray options */
		/* Todo : array check*/

	    $groupOfFlights = $amflightResults->response->flightIndex->groupOfFlights;
	    $recommendations = $amflightResults->response->recommendation;
	    foreach ($groupOfFlights as $key => $flight) 
	    {
	        $propFlightRef = $flight->propFlightGrDetail->flightProposal[0]->ref;
	        $flightPrice = $this->getflightPrice($propFlightRef,$recommendations);
	        $flightDetails = $flight->flightDetails;
	        $info = $this->optimizeInfo($flightDetails);
	        //$dateOfDeparture  = date_create_from_format('dmy',$date);
	        $majCabin = $this->getCabinDescription($flightPrice->fareDetails->majCabin->bookingClassDetails->designator);
	        $seatstatus = $this->seatStatus($flightPrice->fareDetails->groupOfFares);
	        //$stopInfo =  $this->flightStops($flightDetails);

	        $result->flight[$key] = new \stdClass();/* fix undefined stdObject warning */
	        $result->flight[$key]->ref = $propFlightRef;
	        $result->flight[$key]->flightDetails =  $flightDetails;
	        $result->flight[$key]->flightPrice =  $flightPrice;
	        $result->flight[$key]->stopInfo = $info->stopInfo;
	        $result->flight[$key]->airports = $info->airports;
	        $result->flight[$key]->majCabinDesc =  $majCabin;
	        $result->flight[$key]->seatstatus =  $seatstatus;
	        //$result->flight[$key]->stopInfo =  $stopInfo;
	    }

	   return $result->flight;
	}

	public function FareMasterPricerCalendar($opt)
	{

		$passengers = $this->getPassengersCount($opt->passengers);

		$itineraries = $this->getItinerarCount($opt->itineraries);

       	$calendarSearchOpt = new FareMasterPricerCalendarOptions([
           'nrOfRequestedResults' => $opt->nrOfRequestedResults,
           'nrOfRequestedPassengers' => $opt->nrOfRequestedPassengers,
           'passengers' => $passengers,
           'itinerary' => $itineraries,
            'currencyOverride' => 'USD'
       ]);

       $fareMPC = $this->amadeusClient->fareMasterPricerCalendar($calendarSearchOpt);
       return [ 'provider' => self::PROVIDER,
       'result' => $fareMPC ];
   }

  public function FareMasterPricerTravelboardSearch($opt)
  {
  	$passengers = $this->getPassengersCount($opt->passengers);
	$itineraries = $this->getItinerarCount($opt->itineraries);

    $opt = new FareMasterPricerTbSearch([
        'nrOfRequestedResults' => $opt->nrOfRequestedResults,
        'nrOfRequestedPassengers' => $opt->nrOfRequestedPassengers,
        'passengers' => $passengers,
        'itinerary' => $itineraries,
         'currencyOverride' => 'USD'
    ]);

    $fareMPTS = $this->amadeusClient->fareMasterPricerTravelBoardSearch($opt);
    return [ 'provider' => self::PROVIDER,
       'result' => $fareMPTS ];

  }

  public function Fare_InformativePricingWithoutPNR()
  {

    $informativePricingResponse = $this->amadeusClient->fareInformativePricingWithoutPnr(
          new FareInformativePricingWithoutPnrOptions([
              'passengers' => [
                  new Passenger([
                      'tattoos' => [1],
                      'type' => Passenger::TYPE_ADULT
                  ])
              ],
              'segments' => [
                  new Segment([
                      'departureDate' => \DateTime::createFromFormat('Y-m-d H:i:s', '2017-12-15 14:45:00'),
                      'from' => 'CMB',
                      'to' => 'SIN',
                      'marketingCompany' => 'EK',
                      'flightNumber' => '348',
                      'bookingClass' => 'Y',
                      'segmentTattoo' => 1,
                      'groupNumber' => 7
                  ])
              ]
          ])
      );

    return $informativePricingResponse;

  }

  public function Air_FlightInfo()
  {
    $flightInfo = $this->amadeusClient->airFlightInfo(
        new AirFlightInfoOptions([
            'airlineCode' => 'OD',
            'flightNumber' => '186',
            'departureDate' => \DateTime::createFromFormat('Y-m-d', '2017-12-15'),
            'departureLocation' => 'CMB',
            'arrivalLocation' => 'KUL'
        ])
    );

    return $flightInfo;
  }

public function Air_RetrieveSeatMap()
{
  $seatmapInfo = $this->amadeusClient->airRetrieveSeatMap(
        new AirRetrieveSeatMapOptions([
            'flight' => new FlightInfo([
                'departureDate' => \DateTime::createFromFormat('Ymd', '20171215'),
                'departure' => 'CMB',
                'arrival' => 'KUL',
                'airline' => 'OD',
                'flightNumber' => '186'
            ])
        ])
    );
  return $seatmapInfo;
}




}


?>