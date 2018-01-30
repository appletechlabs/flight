<?php

namespace appletechlabs\flight\Providers;

use Amadeus\Client as AmadeusClient;
use Amadeus\Client\Params;
use Amadeus\Client\Result;


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

use Amadeus\Client\RequestOptions\AirSellFromRecommendationOptions;
use Amadeus\Client\RequestOptions\Air\SellFromRecommendation\Itinerary;
use Amadeus\Client\RequestOptions\Air\SellFromRecommendation\Segment as AirsellSegment;

use Amadeus\Client\RequestOptions\PnrCreatePnrOptions;
use Amadeus\Client\RequestOptions\Pnr\Traveller;
use Amadeus\Client\RequestOptions\Pnr\Itinerary as PnrItinerary;
use Amadeus\Client\RequestOptions\Pnr\Segment as PnrSegment;
use Amadeus\Client\RequestOptions\Pnr\Segment\Miscellaneous;
use Amadeus\Client\RequestOptions\Pnr\Element\Ticketing;
use Amadeus\Client\RequestOptions\Pnr\Element\Contact;
use Amadeus\Client\RequestOptions\Pnr\Segment\Air;

use Amadeus\Client\RequestOptions\PnrAddMultiElementsOptions;

use Amadeus\Client\RequestOptions\PnrRetrieveOptions;
use Amadeus\Client\RequestOptions\PnrCancelOptions;
use Amadeus\Client\RequestOptions\PnrRetrieveAndDisplayOptions;


use Psr\Log\NullLogger;

use appletechlabs\flight\Helpers\Data;
use appletechlabs\flight\Recommendations\Recommendation;
use appletechlabs\flight\Recommendations\fareSummary;
use appletechlabs\flight\Recommendations\paxFare;
use appletechlabs\flight\Recommendations\Rules;


use appletechlabs\flight\Providers\AmadeusSoapProvider\AirSellFromRecommendation;


/**
 * Class AmadeusSoapProvider
 * @package appletechlabs\flight\Providers
 */
class AmadeusSoapProvider
{
	const PROVIDER = "AmadeusSoap";
 
  public $params;


    /**
     * @param array $options
     */
    public function setup(array $options)
	{
    $this->params = new Params([
        'returnXml' => false,
        'authParams' => [
            'officeId' => $options['officeId'],
            'userId' => $options['userId'], 
            'passwordData' => $options['passwordData'], 
            'passwordLength' => $options['passwordLength'],
        ],
        'sessionHandlerParams' => [
            'soapHeaderVersion' => AmadeusClient::HEADER_V4,
            'wsdl' => $options['wsdl'], 
            'stateful' => false,
            'logger' => new NullLogger()
        ],
        'requestCreatorParams' => [
            'receivedFrom' => $options['receivedFrom'] 
        ]
    ]);

    
	}

    /**
     * @return mixed
     */
    public function securitySignIn()
  {
    $this->amadeusClient = new AmadeusClient($this->params);
    $authResult = $this->amadeusClient->securityAuthenticate();

    return $authResult;
  }


    /**
     * @return mixed
     */
    public function securitySignOut()
  {
    $this->amadeusClient = new AmadeusClient($this->params);
    return $this->amadeusClient->securitySignOut();
  }

    /**
     * @param $passengers
     * @return array
     */
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

    /**
     * @param $rangeModeText
     * @return mixed
     */
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

    /**
     * @param $itineraries
     * @return array
     */
    public function getItinerarCount($itineraries)
  {

  	foreach ($itineraries as $itinerary => $value) {

  		//var_dump($value['departureLocation']);
  		if (isset($value['rangeMode'])) {
  			$MPItinerary = new MPItinerary([
                   'departureLocation' => new MPLocation(['airport' => $value['departureLocation']]),
                   'arrivalLocation' => new MPLocation(['airport' => $value['arrivalLocation']]),
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
                   'departureLocation' => new MPLocation(['airport' => $value['departureLocation']]),
                   'arrivalLocation' => new MPLocation(['airport' => $value['arrivalLocation']]),
                   'date' => new MPDate([
                       'date' => $value['date'],
                   ])
               ]);
  		}	
  		$MPItineraries[] = $MPItinerary;
  	}
  	return $MPItineraries;

  }


    /**
     * @param $ref
     * @param $recommendations
     * @return mixed
     */
    public function getflightPrice($ref, $recommendations)
	{
	    //If Multiple Recommendations (Multiple Pricing)
     if(!is_array($recommendations))
     {
        $recommendations = Data::dataToArray($recommendations);
     }
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

    /**
     * @param $amflightResults
     * @return mixed
     */
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

          if(!is_array($flightPrice)) 
          $flightPrice = Data::dataToArray($flightPrice); 

          $totalFareAmount = 0;

          foreach ($flightPrice as $flightPriceItem) {
             $totalFareAmount += $flightPriceItem->paxFareDetail->totalFareAmount;
          }


          if (is_array($flight->flightDetails)) 
          {
            $date = $flight->flightDetails[0]->flightInformation->productDateTime->dateOfDeparture;
          }
          else
          {
            $date = $flight->flightDetails->flightInformation->productDateTime->dateOfDeparture;            
          }
	        $dateOfDeparture  = date_create_from_format('dmy',$date);

	        $result->flight[$key] = new \stdClass();/* fix undefined stdObject warning */
	        $result->flight[$key]->ref = $propFlightRef;
	        $result->flight[$key]->dateOfDeparture =  $dateOfDeparture->format('d-m-y');
	        $result->flight[$key]->dateMonth =  $dateOfDeparture->format('d M');
	        $result->flight[$key]->totalFareAmount =  $totalFareAmount;
	    }


	   usort($result->flight,function ($a, $b){
			    return strtotime($a->dateOfDeparture) - strtotime($b->dateOfDeparture);
			});

	   return $result->flight;
	}

    /**
     * @param $majCabin
     * @return string
     */
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

    /**
     * @param $groupOfFares
     * @return \stdClass
     */
    public function seatStatus($groupOfFares)
	{
    $cabinProduct = new \stdClass();
		$cabinProduct->status = "Seats Available";
    $cabinProduct->class = [];
		if (is_array($groupOfFares)) {
			foreach ($groupOfFares as $key => $Fare) {
				if (($Fare->productInformation->cabinProduct->avlStatus)<9) {
	                $cabinProduct->status = "few Seats Available";
	            }
        $cabinProduct->class[] = $Fare->productInformation->cabinProduct->rbd;
			}
		}
		else
	   	{
	    if ($groupOfFares->productInformation) {
	        if ($groupOfFares->productInformation) {
	           if (($groupOfFares->productInformation->cabinProduct->avlStatus)<9) {
	                $cabinProduct->status = "few Seats Available";
	            } 
              $cabinProduct->class[] = $groupOfFares->productInformation->cabinProduct->rbd;
	        }
	    }   
	   }
	   return $cabinProduct;
	}

    /**
     * @param $flightDetails
     * @return \stdClass
     */
    public function flightStops($flightDetails)
	{
		//var_dump($flightDetails);
		$stopInfo =  new \stdClass();
		$stopInfo->stops = "Non-Stop";
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

    /**
     * @param $flightDetails
     * @return \stdClass
     */
    public function optimizeInfo($flightDetails)
	{
		$info = new \stdClass();
		$info->stopInfo = "Non-Stop";
		$airports = [];

		if (is_array($flightDetails)) 
    {
			if (count($flightDetails)-1 == 1) {
	            $info->stopInfo = (count($flightDetails)-1 ) . " Stop";
	        }
	        else
	        {
	        	 $info->stopInfo = (count($flightDetails)-1 ) . " Stops";
	        }

			foreach ($flightDetails as $flight) 
      {
				foreach ($flight->flightInformation->location as $location) 
        {
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

    /**
     * @param $flightDetails
     * @param $class
     * @return \stdClass
     */
    public function getFlightDetails($flightDetails, $class)
  {
    if (!is_array($flightDetails)) {
      $flightDetails = Data::dataToArray($flightDetails);
    }

    $class = Data::dataToArray($class);

    $results = new \stdClass();

    $results->info = [];

    foreach ($flightDetails as $flightDetailsKey => $flight) {
      
      $info = new \stdClass();

      $depdate = $flight->flightInformation->productDateTime->dateOfDeparture;
      $deptime = $flight->flightInformation->productDateTime->timeOfDeparture;

      $info->departure['dateTime'] =  date_create_from_format('dmyHi',$depdate.$deptime);
      $info->departure['airport'] =  $flight->flightInformation->location[0]->locationId;
      $info->departure['terminal'] =  $flight->flightInformation->location[0]->terminal ?? '';

      $arrdate = $flight->flightInformation->productDateTime->dateOfArrival;
      $arrtime = $flight->flightInformation->productDateTime->timeOfArrival;

      $info->arrival['dateTime'] =  date_create_from_format('dmyHi',$arrdate.$arrtime);
      $info->arrival['airport'] =  $flight->flightInformation->location[1]->locationId;
      $info->arrival['terminal'] =  $flight->flightInformation->location[1]->terminal ?? '';

      if ($flight->flightInformation->attributeDetails->attributeType == 'EFT') 
      {
        $info->flyingTime = $flight->flightInformation->attributeDetails->attributeDescription;
      }

      $info->flightNumber = $flight->flightInformation->flightOrtrainNumber;
      $info->aircraft = $flight->flightInformation->productDetail->equipmentType;
      $info->marketingCarrier = $flight->flightInformation->companyId->marketingCarrier;
      $info->class = $class[$flightDetailsKey];

      if ($flightDetailsKey != 0)
       {
           $beforeArrdate = $flightDetails[$flightDetailsKey-1]->flightInformation->productDateTime->dateOfArrival;
           $beforeArrtime = $flightDetails[$flightDetailsKey-1]->flightInformation->productDateTime->timeOfArrival;
           $beforeDateOfArrival  = date_create_from_format('dmyHi',$beforeArrdate.$beforeArrtime);

           $beforeDate = $beforeDateOfArrival;
           $afterDate =  $info->departure['dateTime'];
           $info->stopOverTime = $beforeDate->diff($afterDate);
       }    

      $results->info[] = $info;

    }


    return $results;
    
   

  }

    /**
     * @param $ref
     * @param $groupOfFlights
     * @return \stdClass
     * @throws \Exception
     */
    public function getFlightPrposals($ref, $groupOfFlights)
  {

    $groupOfFlights = Data::dataToArray($groupOfFlights);


    $result = new \stdClass();
    foreach ($groupOfFlights as $segment) 
    {
      if ($segment->propFlightGrDetail->flightProposal[0]->ref == $ref) 
      {
        $result->flightDetails =  $segment->flightDetails;
        foreach ($segment->propFlightGrDetail->flightProposal as $flightProposal) 
        {

          if (isset($flightProposal->unitQualifier) && $flightProposal->unitQualifier == "MCX") 
          {
           $result->MajAirline = $flightProposal->ref;

          }
          if (isset($flightProposal->unitQualifier) && $flightProposal->unitQualifier == "EFT") 
          {
            $EFT = str_split($flightProposal->ref, 2);
            $FlyingTime = new \DateInterval('PT'.$EFT[0].'H'.$EFT[1].'M');
            $result->EFT = $FlyingTime;
          }
      }
      }
      
    }

    return $result;
  }


    /**
     * @param $amflightResults
     * @return array
     * @throws \Exception
     */
    public function optimizeResults($amflightResults)
	{
		//var_dump($amflightResults);
		/* This doesn't work with multiple itineray options */
		/* Todo : array check*/
      if (is_array($amflightResults->response->conversionRate->conversionRateDetail))
      {
          $currency = $amflightResults->response->conversionRate->conversionRateDetail[0]->currency;
      }
      else
      {
          $currency = $amflightResults->response->conversionRate->conversionRateDetail->currency;
      }
      
	    $groupOfFlights = $amflightResults->response->flightIndex->groupOfFlights;
	    $recommendations = $amflightResults->response->recommendation;
       
      $recommendations = Data::dataToArray($recommendations);
      foreach ($recommendations as $recommendation) 
      {        
       
        $recPriceInfo = Data::dataToArray($recommendation->recPriceInfo->monetaryDetail);

        foreach ($recPriceInfo as $recPriceInfoItem) {
            if (isset($recPriceInfoItem->amountType) && $recPriceInfoItem->amountType == "CR") {
              # Conversion rate not guaranteed results
              $totalAmount = $recPriceInfoItem->amount;
              $rateGuaranteed = false;
              break;
           }
           else
           {
              $totalAmount = $recPriceInfo[0]->amount;
              $rateGuaranteed = true;
           }
        }

         # Recommendaton References
        $segmentFlightReferences = Data::dataToArray($recommendation->segmentFlightRef); 
        $result = new \stdClass(); 
        foreach ($segmentFlightReferences as $segmentFlightRef) 
        {

         # Flight Proposals and Currency Conversions    
          $referencingDetails = Data::dataToArray($segmentFlightRef->referencingDetail);          
          foreach ($referencingDetails as $referencingDetailKey => $referencingDetail) 
          {  # Get Only Segment refrernces from refQualifier = S          
            if($referencingDetail->refQualifier == "S")
            { 


               $flightPrice = Data::dataToArray($recommendation->paxFareProduct);
               $majCabin = $this->getCabinDescription($flightPrice[0]->fareDetails->majCabin->bookingClassDetails->designator);
               $cabinProduct = $this->seatStatus($flightPrice[0]->fareDetails->groupOfFares);

               $paxFareList = [];

               foreach ($flightPrice as $flightPriceKey => $flightPriceitem) 
                {

                  $type = $flightPriceitem->paxReference->ptc;
                  $noOfPassengers = count($flightPriceitem->paxReference->traveller);
                 
                  $taxesAndFees = $flightPriceitem->paxFareDetail->totalTaxAmount;
                  $total = $flightPriceitem->paxFareDetail->totalFareAmount;
                  $baseFare = round($total - $taxesAndFees,2);

                  
                  if (isset($flightPriceitem->fare)) {
                      $fareRules = $flightPriceitem->fare ?? null;

                      if(!is_array($fareRules)) 
                      $fareRules = Data::dataToArray($fareRules);   

                      $paxFareRules = [];
                      foreach ($fareRules as $fareRulekey => $fareRule) 
                      {
                        $informationType = $fareRule->pricingMessage->freeTextQualification->informationType;
                        $description = $fareRule->pricingMessage->description;
                        $monetaryDetail = $fareRule->monetaryInformation->monetaryDetail ?? null;

                        $paxFareRule = new Rules([
                           'informationType' => $informationType,
                           'description' => $description,
                           'monetaryDetail' => $monetaryDetail,
                        ]);
                        $paxFareRules[]= $paxFareRule;                        
                      }
                  }                 

                   $paxFare = new paxFare([
                      'type' => $type,
                      'noOfPassengers' => $noOfPassengers,
                      'baseFare' => $baseFare,
                      'taxesAndFees' => $taxesAndFees,
                      'total' => $total,
                      'paxFareRules' => $paxFareRules ?? null,
                  ]);

                   $paxFareList[] = $paxFare;

                }

                $flightDetails = $this->getFlightPrposals($referencingDetail->refNumber,$groupOfFlights);
                $info = $this->optimizeInfo($flightDetails->flightDetails);

                $flightTiming = $this->getFlightDetails($flightDetails->flightDetails,$cabinProduct->class);
               

               $Recommendation = new Recommendation([
                 'ref' => $referencingDetail->refNumber,
                 'flightDetails' =>  $flightTiming->info,
                 'majCabin' => $majCabin,
                 'majAirline' => $flightDetails->MajAirline,
                 'stopInfo' => $info->stopInfo,
                 'airports' => $info->airports,
                 'seatAvailability' =>$cabinProduct->status,
                 'rateGuaranteed' => $rateGuaranteed,
                 'totalFlyingTime' =>  $flightDetails->EFT,
                 'provider' => self::PROVIDER,
                 'fareSummary' => new fareSummary([
                      'currency' => $currency,
                      'pax' => $paxFareList,
                      'total' =>  $totalAmount,
                    ])           
                ]);



               $Recommendations[] = $Recommendation;



            }
          }

        }

        
      }
	    
	    return $Recommendations;
	}

  // public function optimizeResults2($amflightResults)
  // {
  //   //var_dump($amflightResults);
  //   /* This doesn't work with multiple itineray options */
  //   /* Todo : array check*/
  //   if (is_array($amflightResults->response->conversionRate->conversionRateDetail))
  //   {
  //       $currency = $amflightResults->response->conversionRate->conversionRateDetail[0]->currency;
  //   }
  //   else
  //   {
  //       $currency = $amflightResults->response->conversionRate->conversionRateDetail->currency;
  //   }
    
  //     $groupOfFlights = $amflightResults->response->flightIndex->groupOfFlights;
  //     $recommendations = $amflightResults->response->recommendation;
  //     foreach ($groupOfFlights as $key => $flight) 
  //     {
  //         $propFlightRef = $flight->propFlightGrDetail->flightProposal[0]->ref;
  //         $flightPrice = $this->getflightPrice($propFlightRef,$recommendations);
  //         $flightDetails = $this->getFlightDetails($flight->flightDetails);
  //         $info = $this->optimizeInfo($flight->flightDetails);

  //         if(!is_array($flightPrice)) 
  //         $flightPrice = Data::dataToArray($flightPrice);          

  //         $majCabin = $this->getCabinDescription($flightPrice[0]->fareDetails->majCabin->bookingClassDetails->designator);
  //         $cabinProduct = $this->seatStatus($flightPrice[0]->fareDetails->groupOfFares);
  //         //$stopInfo =  $this->flightStops($flightDetails);

  //         $result->flight[$key] = new \stdClass();/* fix undefined stdObject warning */
  //         $result->flight[$key]->ref = $propFlightRef;
  //         $result->flight[$key]->flightDetails =  $flightDetails;
  //         $result->flight[$key]->flightPrice =  $flightPrice;
  //         $result->flight[$key]->stopInfo = $info->stopInfo;
  //         $result->flight[$key]->airports = $info->airports;
  //         $result->flight[$key]->majCabinDesc =  $majCabin;
  //         $result->flight[$key]->seatstatus =  $seatstatus;

  //         if (is_array($flightPrice[0]->paxFareDetail->codeShareDetails)) {
  //             foreach ($flightPrice[0]->paxFareDetail->codeShareDetails as $codeShareDetail) {
  //                 if (isset($codeShareDetail->transportStageQualifier) && $codeShareDetail->transportStageQualifier == "V") {
  //                    $result->flight[$key]->MajAirline  = $codeShareDetail->company;
  //                 }
  //             }
  //         }
  //         else
  //         {
  //            $result->flight[$key]->MajAirline  = $flightPrice[0]->paxFareDetail->codeShareDetails->company;
  //         }

  //         $paxFareList = [];

  //         foreach ($flightPrice as $flightPriceKey => $flightPriceitem) 
  //         {

  //           $type = $flightPriceitem->paxReference->ptc;
  //           $noOfPassengers = count($flightPriceitem->paxReference->traveller);
           
  //           $taxesAndFees = $flightPriceitem->paxFareDetail->totalTaxAmount;
  //           $total = $flightPriceitem->paxFareDetail->totalFareAmount;
  //           $baseFare = round($total - $taxesAndFees,2);

  //           $fareRules = $flightPriceitem->fare ?? null;

  //           if(!is_array($fareRules)) 
  //           $fareRules = Data::dataToArray($fareRules);   

  //           $paxFareRules = [];

  //           foreach ($fareRules as $fareRulekey => $fareRule) {
  //             $informationType = $fareRule->pricingMessage->freeTextQualification->informationType;
  //             $description = $fareRule->pricingMessage->description;
  //             $monetaryDetail = $fareRule->monetaryInformation->monetaryDetail ?? null;

  //             $paxFareRule = new Rules([
  //                'informationType' => $informationType,
  //                'description' => $description,
  //                'monetaryDetail' => $monetaryDetail,
  //             ]);

  //             $paxFareRules[]= $paxFareRule;
            
  //           }

  //            $paxFare = new paxFare([
  //               'type' => $type,
  //               'noOfPassengers' => $noOfPassengers,
  //               'baseFare' => $baseFare,
  //               'taxesAndFees' => $taxesAndFees,
  //               'total' => $total,
  //               'paxFareRules' =>$paxFareRules
  //           ]);

  //            $paxFareList[] = $paxFare;

  //         }
  //         //   var_dump($currency);
  //         // exit();          

  //         $Recommendation = new Recommendation([
  //          'ref' => $propFlightRef,
  //          'flightDetails' => $flightDetails,
  //          'majCabin' => $majCabin,
  //          'majAirline' => $result->flight[$key]->MajAirline,
  //          'stopInfo' => $info->stopInfo,
  //          'airports' => $info->airports,
  //          'seatAvailability' => $cabinProduct->status,
  //          'fareSummary' => new fareSummary([
  //               'currency' => $currency,
  //               'pax' => $paxFareList
  //             ])           
  //         ]);

  //         $Recommendations[] = $Recommendation;
         
  //         //$result->flight[$key]->stopInfo =  $stopInfo;
  //     }
  //     return $Recommendations;

  //    //return $result->flight;
  // }

    /**
     * @param $opt
     * @return array
     */
    public function FareMasterPricerCalendar($opt)
	{

		$passengers = $this->getPassengersCount($opt->passengers);

		$itineraries = $this->getItinerarCount($opt->itineraries);

       	$calendarSearchOpt = new FareMasterPricerCalendarOptions([
           'nrOfRequestedResults' => $opt->nrOfRequestedResults,
           'nrOfRequestedPassengers' => $opt->nrOfRequestedPassengers,
           'passengers' => $passengers,
           'itinerary' => $itineraries,
            'currencyOverride' => $opt->currencyOverride
       ]);

       $fareMPC = $this->amadeusClient->fareMasterPricerCalendar($calendarSearchOpt);
       return [ 'provider' => self::PROVIDER,
       'result' => $fareMPC ];
   }

    /**
     * @param $opt
     * @return array
     */
    public function FareMasterPricerTravelboardSearch($opt)
  {
  	$passengers = $this->getPassengersCount($opt->passengers);
	$itineraries = $this->getItinerarCount($opt->itineraries);

    $opt = new FareMasterPricerTbSearch([
        'nrOfRequestedResults' => $opt->nrOfRequestedResults,
        'nrOfRequestedPassengers' => $opt->nrOfRequestedPassengers,
        'passengers' => $passengers,
        'itinerary' => $itineraries,
        'flightOptions' => [
            FareMasterPricerTbSearch::FLIGHTOPT_PUBLISHED,
            FareMasterPricerTbSearch::FLIGHTOPT_UNIFARES,
            FareMasterPricerTbSearch::FLIGHTOPT_NO_SLICE_AND_DICE,
            "CUC",
        ],
         'currencyOverride' => $opt->currencyOverride
    ]);

    $fareMPTS = $this->amadeusClient->fareMasterPricerTravelBoardSearch($opt);
    return [ 'provider' => self::PROVIDER,
       'result' => $fareMPTS ];

  }

    /**
     * @return mixed
     */
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
                      'departureDate' => \DateTime::createFromFormat('Y-m-d H:i:s', '2018-02-20 01:00:00'),
                      'from' => 'CMB',
                      'to' => 'SIN',
                      'marketingCompany' => 'UL',
                      'flightNumber' => '306',
                      'bookingClass' => 'V',
                      'segmentTattoo' => 1,
                      'groupNumber' => 1
                  ])
              ]
          ])
      );

    return $informativePricingResponse;

  }

    /**
     * @param $options
     * @return mixed
     */
    public function Air_SellFromRecommendation($options)
  {
     $opt = new AirSellFromRecommendation($options);

     $sellResult = $this->amadeusClient->airSellFromRecommendation($opt->RecOption);

     return $sellResult;

  }

    /**
     * @return mixed
     */
    public function PNR_AddMultiElements()
{
    $opt = new PnrCreatePnrOptions();
    $opt->actionCode = PnrCreatePnrOptions::ACTION_END_TRANSACT_RETRIEVE; //0 Do not yet save the PNR and keep in context.
    $opt->travellers[] = new Traveller([
        'number' => 1,
        'firstName' => 'THUIYA HENNADIGE',
        'lastName' => 'AROSHA JAYASANKA DE SILVA',
        'dateOfBirth' => \DateTime::createFromFormat('Y-m-d H:i:s', "1990-05-20 00:15:00", new \DateTimeZone('UTC'))
    ]);
    $opt->itineraries[] = new PnrItinerary([
            'origin' => 'CMB',
            'destination' => 'SIN',
            'segments' => [
                new Air([
                    'date' => \DateTime::createFromFormat('Y-m-d H:i:s', "2018-02-20 00:15:00", new \DateTimeZone('UTC')),
                    'origin' => 'CMB',
                    'destination' => 'KUL',
                    'flightNumber' => '178',
                    'bookingClass' => 'N',
                    'company' => 'MH'
                ])
        ]
    ]);
    $opt->elements[] = new Ticketing([
        'ticketMode' => Ticketing::TICKETMODE_OK
    ]);
    $opt->elements[] = new Contact([
        'type' => Contact::TYPE_PHONE_MOBILE,
        'value' => '+94765411990'
    ]);

    //The required Received From (RF) element will automatically be added by the library if you didn't provide one.

    $createdPnr = $this->amadeusClient->pnrCreatePnr($opt);

    // $pnrReply = $this->amadeusClient->pnrAddMultiElements(
    //     new PnrAddMultiElementsOptions([
    //         'actionCode' => PnrAddMultiElementsOptions::ACTION_END_TRANSACT_RETRIEVE //ET: END AND RETRIEVE
    //     ])
    // );

    return $createdPnr;
}

    /**
     * @return mixed
     */
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

    /**
     * @return mixed
     */
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


    /**
     * @param $pnr
     * @return mixed
     */
    public function PNR_Retrieve($pnr)
{
      $pnrContent = $this->amadeusClient->pnrRetrieve(
             new PnrRetrieveOptions(['recordLocator' => $pnr])
      );

      return $pnrContent;
}


    /**
     * @param $pnr
     * @return mixed
     */
    public function PNR_Cancel($pnr)
  {
       $cancelReply = $this->amadeusClient->pnrCancel(
             new PnrCancelOptions([
              'recordLocator' => $pnr,
              'cancelItinerary' => true,
              'actionCode' => PnrCancelOptions::ACTION_END_TRANSACT
        ])
      );
        return $cancelReply;
  }

  public function FarePricePnrWithBookingClassOptions()
  {
    # code...
  }




}

/*test*/






