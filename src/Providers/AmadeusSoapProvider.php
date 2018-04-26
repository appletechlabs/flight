<?php

namespace appletechlabs\flight\Providers;

use Amadeus\Client as AmadeusClient;
use Amadeus\Client\Params;
use Amadeus\Client\RequestOptions\Air\RetrieveSeatMap\FlightInfo;
use Amadeus\Client\RequestOptions\Air\SellFromRecommendation\Itinerary;
use Amadeus\Client\RequestOptions\AirFlightInfoOptions;
use Amadeus\Client\RequestOptions\AirRetrieveSeatMapOptions;
use Amadeus\Client\RequestOptions\DocIssuanceIssueTicketOptions;
use Amadeus\Client\RequestOptions\Fare\InformativePricing\Passenger;
use Amadeus\Client\RequestOptions\Fare\InformativePricing\Segment;
use Amadeus\Client\RequestOptions\Fare\MPDate;
use Amadeus\Client\RequestOptions\Fare\MPItinerary;
use Amadeus\Client\RequestOptions\Fare\MPLocation;
use Amadeus\Client\RequestOptions\Fare\MPPassenger;
use Amadeus\Client\RequestOptions\FareInformativePricingWithoutPnrOptions;
use Amadeus\Client\RequestOptions\FareMasterPricerCalendarOptions;
use Amadeus\Client\RequestOptions\FareMasterPricerTbSearch;
// use Amadeus\Client\RequestOptions\PnrCreatePnrOptions;
// use Amadeus\Client\RequestOptions\Pnr\Traveller;
// use Amadeus\Client\RequestOptions\Pnr\Itinerary as PnrItinerary;
// use Amadeus\Client\RequestOptions\Pnr\Segment as PnrSegment;
// use Amadeus\Client\RequestOptions\Pnr\Segment\Miscellaneous;
// use Amadeus\Client\RequestOptions\Pnr\Element\Ticketing;
// use Amadeus\Client\RequestOptions\Pnr\Element\Contact;
// use Amadeus\Client\RequestOptions\Pnr\Segment\Air;

use Amadeus\Client\RequestOptions\FarePricePnrWithBookingClassOptions;
use Amadeus\Client\RequestOptions\PnrAddMultiElementsOptions;
use Amadeus\Client\RequestOptions\PnrCancelOptions;
use Amadeus\Client\RequestOptions\PnrRetrieveOptions;
use Amadeus\Client\RequestOptions\Ticket\Pricing;
use Amadeus\Client\RequestOptions\TicketCreateTstFromPricingOptions;
use Amadeus\Client\Result;
use appletechlabs\flight\Helpers\Data;
use appletechlabs\flight\Providers\AmadeusSoapProvider\AirSellFromRecommendation;
use appletechlabs\flight\Providers\AmadeusSoapProvider\PNR_AddMultiElements;
use appletechlabs\flight\Recommendations\fareSummary;
use appletechlabs\flight\Recommendations\paxFare;
use appletechlabs\flight\Recommendations\Recommendation;
use appletechlabs\flight\Recommendations\Rules;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class AmadeusSoapProvider
{
    const PROVIDER = 'AmadeusSoap';

    public $params;

    public function setup(array $options)
    {
        $msgLog = new Logger('RequestResponseLogs');
        $msgLog->pushHandler(new StreamHandler('logs/requestresponse.log', Logger::INFO));

        $this->params = new Params([
        'returnXml'  => false,
        'authParams' => [
            'officeId'       => $options['officeId'],
            'userId'         => $options['userId'],
            'passwordData'   => $options['passwordData'],
            'passwordLength' => $options['passwordLength'],
        ],
        'sessionHandlerParams' => [
            'soapHeaderVersion' => AmadeusClient::HEADER_V4,
            'wsdl'              => $options['wsdl'],
            'stateful'          => $options['stateful'],
            'logger'            => $msgLog,
        ],
        'requestCreatorParams' => [
            'receivedFrom' => $options['receivedFrom'],
        ],
    ]);
    }

    public function securitySignIn()
    {
        $this->amadeusClient = new AmadeusClient($this->params);
        $authResult = $this->amadeusClient->securityAuthenticate();

        return $authResult;
    }

    public function securitySignOut()
    {
        $this->amadeusClient = new AmadeusClient($this->params);

        return $this->amadeusClient->securitySignOut();
    }

    public function getPassengersCount($passengers)
    {
        foreach ($passengers as $passenger => $value) {
            switch ($passenger) {
            case 'ADT':
                $MPPassenger = new MPPassenger([
                   'type'  => MPPassenger::TYPE_ADULT,
                   'count' => $value,
                   ]);
                break;

            case 'CH':
                $MPPassenger = new MPPassenger([
                   'type'  => MPPassenger::TYPE_CHILD,
                   'count' => $value,
                   ]);
                break;

            case 'INF':
                $MPPassenger = new MPPassenger([
                   'type'  => MPPassenger::TYPE_INFANT,
                   'count' => $value,
                   ]);
                break;

            default:
                // code...
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
            // code...
            break;
    }
    }

    public function getItinerarCount($itineraries)
    {
        foreach ($itineraries as $itinerary => $value) {

        //var_dump($value['departureLocation']);
            if (isset($value['rangeMode'])) {
                $MPItinerary = new MPItinerary([
                   'departureLocation' => new MPLocation(['airport' => $value['departureLocation']]),
                   'arrivalLocation'   => new MPLocation(['airport' => $value['arrivalLocation']]),
                   'date'              => new MPDate([
                       'date'      => $value['date'],
                       'rangeMode' => $this->getRangeMode($value['rangeMode']),
                       'range'     => $value['range'],
                   ]),
               ]);
            } else {
                $MPItinerary = new MPItinerary([
                   'departureLocation' => new MPLocation(['airport' => $value['departureLocation']]),
                   'arrivalLocation'   => new MPLocation(['airport' => $value['arrivalLocation']]),
                   'date'              => new MPDate([
                       'date' => $value['date'],
                   ]),
               ]);
            }
            $MPItineraries[] = $MPItinerary;
        }

        return $MPItineraries;
    }

    public function getflightPrice($ref, $recommendations)
    {
        //If Multiple Recommendations (Multiple Pricing)
        if (!is_array($recommendations)) {
            $recommendations = Data::dataToArray($recommendations);
        }
        foreach ($recommendations as $recommendation) {
            $segments = $recommendation->segmentFlightRef;
            foreach ($segments as $segmentKey => $segment) {
                if (isset($segment->referencingDetail)) {
                    if (is_array($segment->referencingDetail)) {
                        foreach ($segment->referencingDetail as $rd) {
                            if ($rd->refQualifier == 'S') {
                                $refQualifier = $rd->refQualifier;
                                $refNumber = $rd->refNumber;
                            }
                        }
                    } else {
                        $refQualifier = $segment->referencingDetail->refQualifier;
                        $refNumber = $segment->referencingDetail->refNumber;
                    }
                } else {
                    /*if only one recommendation for this particular pricing*/
                    //var_dump($segment);
                    if (is_array($segment)) {
                        foreach ($segment as $sg) {
                            if ($sg->refQualifier == 'S') {
                                $refQualifier = $sg->refQualifier;
                                $refNumber = $sg->refNumber;
                            }
                        }
                    } else {
                        $refQualifier = $segment->refQualifier;
                        $refNumber = $segment->refNumber;
                    }
                }

                if ($refNumber == $ref) {
                    $price = $recommendation->paxFareProduct;

                    return $price;
                }
            }
        }
    }

    public function calendarMin($amflightResults)
    {
        $flightIndex = Data::dataToArray($amflightResults->response->flightIndex);
        /* If only one recommendation */
        $recommendations = Data::dataToArray($amflightResults->response->recommendation);

        $recommendationRef = 0;

        foreach ($recommendations as $recommendation) {
            $recommendation->segmentFlightRef = Data::dataToArray($recommendation->segmentFlightRef);
            foreach ($recommendation->segmentFlightRef as  $key =>  $segmentFlightRef) {
                $result = new \stdClass();
                $result->ref = ++$recommendationRef;
                /* Get Total Base Fare */

                $segmentFlightRef->referencingDetail = Data::dataToArray($segmentFlightRef->referencingDetail);
                foreach ($segmentFlightRef->referencingDetail as $segmentRef => $referencingDetail) {
                    if ($referencingDetail->refQualifier == 'S') {
                        //$result->segmentRef[$segmentRef+1] = $referencingDetail->refNumber;
                        $flight = $this->getFlightPrposals($referencingDetail->refNumber, $flightIndex[$segmentRef]->groupOfFlights);
                        $flight->flightDetails = Data::dataToArray($flight->flightDetails);
                        $dateOfDeparture = $flight->flightDetails[0]->flightInformation->productDateTime->dateOfDeparture;
                        $result->dateOfDeparture[] = date_create_from_format('dmy', $dateOfDeparture)->format('d-m-y');
                        $result->dateMonth[] = date_create_from_format('dmy', $dateOfDeparture)->format('d M');
                    }
                }
                $result->totalFareAmount = $recommendation->recPriceInfo->monetaryDetail[0]->amount;
                $results[] = $result;
            }
        }

        /* check for return flight*/
        if (isset($flightIndex[1])) {
            usort($results, function ($a, $b) {
                $ad = date_create_from_format('d-m-y', ($a->dateOfDeparture[0]));
                $bd = date_create_from_format('d-m-y', ($b->dateOfDeparture[0]));

                if ($ad == $bd) {
                    $ad2 = date_create_from_format('d-m-y', ($a->dateOfDeparture[1]));
                    $bd2 = date_create_from_format('d-m-y', ($b->dateOfDeparture[1]));

                    return $ad2 < $bd2 ? -1 : 1;
                }

                return $ad < $bd ? -1 : 1;
            });
        } else {
            usort($results, function ($a, $b) {
                $ad = date_create_from_format('d-m-y', ($a->dateOfDeparture[0]));
                $bd = date_create_from_format('d-m-y', ($b->dateOfDeparture[0]));

                if ($ad == $bd) {
                    return 0;
                }

                return $ad < $bd ? -1 : 1;
            });
        }

        return $results;
        //var_dump($amflightResults->response->flightIndex[0]->groupOfFlights);
        /* This doesn't work with multiple itineray options */
        /* Todo : array check*/
        $groupOfFlights = $amflightResults->response->flightIndex->groupOfFlights;
        $recommendations = $amflightResults->response->recommendation;
        foreach ($groupOfFlights as $key => $flight) {
            $propFlightRef = $flight->propFlightGrDetail->flightProposal[0]->ref;
            $flightPrice = $this->getflightPrice($propFlightRef, $recommendations);

            if (!is_array($flightPrice)) {
                $flightPrice = Data::dataToArray($flightPrice);
            }

            $totalFareAmount = 0;

            foreach ($flightPrice as $flightPriceItem) {
                $totalFareAmount += $flightPriceItem->paxFareDetail->totalFareAmount;
            }

            if (is_array($flight->flightDetails)) {
                $date = $flight->flightDetails[0]->flightInformation->productDateTime->dateOfDeparture;
            } else {
                $date = $flight->flightDetails->flightInformation->productDateTime->dateOfDeparture;
            }
            $dateOfDeparture = date_create_from_format('dmy', $date);

            $result->flight[$key] = new \stdClass();
            $result->flight[$key]->ref = $propFlightRef;
            $result->flight[$key]->dateOfDeparture = $dateOfDeparture->format('d-m-y');
            $result->flight[$key]->dateMonth = $dateOfDeparture->format('d M');
            $result->flight[$key]->totalFareAmount = $totalFareAmount;
        }

        usort($result->flight, function ($a, $b) {
            return strtotime($a->dateOfDeparture) - strtotime($b->dateOfDeparture);
        });

        return $result->flight;
    }

    public function getCabinDescription($majCabin)
    {
        switch ($majCabin) {
            case 'C':
                return 'Business';
                break;
            case 'F':
                return 'First, supersonic';
                break;
            case 'M':
                return 'Economic Standard';
                break;
            case 'W':
                return 'Economic Premium';
                break;
            case 'Y':
                return 'Economic';
                break;
            default:
                return 'N/A';
        }
    }

    public function seatStatus($groupOfFares)
    {
        $cabinProduct = new \stdClass();
        $cabinProduct->status = 'Seats Available';
        $cabinProduct->class = [];
        if (is_array($groupOfFares)) {
            foreach ($groupOfFares as $key => $Fare) {
                if (($Fare->productInformation->cabinProduct->avlStatus) < 9) {
                    $cabinProduct->status = 'few Seats Available';
                }
                $cabinProduct->class[] = $Fare->productInformation->cabinProduct->rbd;
            }
        } else {
            if ($groupOfFares->productInformation) {
                if ($groupOfFares->productInformation) {
                    if (($groupOfFares->productInformation->cabinProduct->avlStatus) < 9) {
                        $cabinProduct->status = 'few Seats Available';
                    }
                    $cabinProduct->class[] = $groupOfFares->productInformation->cabinProduct->rbd;
                }
            }
        }

        return $cabinProduct;
    }

    public function flightStops($flightDetails)
    {
        //var_dump($flightDetails);
        $stopInfo = new \stdClass();
        $stopInfo->stops = 'Non-Stop';
        $stopInfo->TimeDelay = ' ';

        if (is_array($flightDetails)) {
            //var_dump($flightDetails);
            if (count($flightDetails) - 1 == 1) {
                $stopInfo->stops = (count($flightDetails) - 1).' Stop';
            } else {
                $stopInfo->stops = (count($flightDetails) - 1).' Stops';
            }
        }

        return $stopInfo;
    }

    public function optimizeInfo($flightDetails)
    {
        $info = new \stdClass();
        $info->stopInfo = 'Non-Stop';
        $airports = [];

        if (is_array($flightDetails)) {
            if (count($flightDetails) - 1 == 1) {
                $info->stopInfo = (count($flightDetails) - 1).' Stop';
            } else {
                $info->stopInfo = (count($flightDetails) - 1).' Stops';
            }

            foreach ($flightDetails as $flight) {
                foreach ($flight->flightInformation->location as $location) {
                    if ($location->locationId == end($airports)) {
                        continue;
                    }
                    $airports[] = $location->locationId;
                }
            }
        } else {
            // Direct Flights
            foreach ($flightDetails->flightInformation->location as $location) {
                if ($location->locationId == end($airports)) {
                    continue;
                }
                $airports[] = $location->locationId;
            }
        }
        $info->airports = $airports;

        return $info;
    }

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

            $info->departure['dateTime'] = date_create_from_format('dmyHi', $depdate.$deptime);
            $info->departure['airport'] = $flight->flightInformation->location[0]->locationId;
            $info->departure['terminal'] = $flight->flightInformation->location[0]->terminal ?? '';

            $arrdate = $flight->flightInformation->productDateTime->dateOfArrival;
            $arrtime = $flight->flightInformation->productDateTime->timeOfArrival;

            $info->arrival['dateTime'] = date_create_from_format('dmyHi', $arrdate.$arrtime);
            $info->arrival['airport'] = $flight->flightInformation->location[1]->locationId;
            $info->arrival['terminal'] = $flight->flightInformation->location[1]->terminal ?? '';

            if ($flight->flightInformation->attributeDetails->attributeType == 'EFT') {
                $info->flyingTime = $flight->flightInformation->attributeDetails->attributeDescription;
            }

            $info->flightNumber = $flight->flightInformation->flightOrtrainNumber;
            $info->aircraft = $flight->flightInformation->productDetail->equipmentType;
            $info->marketingCarrier = $flight->flightInformation->companyId->marketingCarrier;

            $info->class = $class[$flightDetailsKey];

            if ($flightDetailsKey != 0) {
                $beforeArrdate = $flightDetails[$flightDetailsKey - 1]->flightInformation->productDateTime->dateOfArrival;
                $beforeArrtime = $flightDetails[$flightDetailsKey - 1]->flightInformation->productDateTime->timeOfArrival;
                $beforeDateOfArrival = date_create_from_format('dmyHi', $beforeArrdate.$beforeArrtime);

                $beforeDate = $beforeDateOfArrival;
                $afterDate = $info->departure['dateTime'];
                $info->stopOverTime = $beforeDate->diff($afterDate);
            }

            $results->info[] = $info;
        }

        return $results;
    }

    public function getFlightPrposals($ref, $groupOfFlights)
    {
        $groupOfFlights = Data::dataToArray($groupOfFlights);

        $result = new \stdClass();
        foreach ($groupOfFlights as $segment) {
            if ($segment->propFlightGrDetail->flightProposal[0]->ref == $ref) {
                $result->flightDetails = $segment->flightDetails;
                foreach ($segment->propFlightGrDetail->flightProposal as $flightProposal) {
                    if (isset($flightProposal->unitQualifier) && $flightProposal->unitQualifier == 'MCX') {
                        $result->MajAirline = $flightProposal->ref;
                    }
                    if (isset($flightProposal->unitQualifier) && $flightProposal->unitQualifier == 'EFT') {
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
     * Get Currency Type.
     *
     * @param array $conversionRateDetail
     *
     * @return string $currency
     */
    public function getCurrencyType($conversionRateDetail)
    {
        $currency = '';
        if (is_array($conversionRateDetail)) {
            $currency = $conversionRateDetail[0]->currency;
        } else {
            $currency = $conversionRateDetail->currency;
        }

        return $currency;
    }

    /**
     * Optimize Amadeus Results for Flights With Return Type.
     *
     * @param array $amflightResults
     *
     * @return Result
     */
    public function optimizeResults($amflightResults)
    {
        //var_dump($amflightResults);
        $currency = $this->getCurrencyType($amflightResults->response->conversionRate->conversionRateDetail);

        $flightSegments = Data::dataToArray($amflightResults->response->flightIndex);
        $recommendations = $amflightResults->response->recommendation;

        $recommendationRef = 0;
        $results = [];

        foreach ($recommendations as $recommendation) {
            $recPriceInfo = Data::dataToArray($recommendation->recPriceInfo->monetaryDetail);

            foreach ($recPriceInfo as $recPriceInfoItem) {
                if (isset($recPriceInfoItem->amountType) && $recPriceInfoItem->amountType == 'CR') {
                    /* Conversion rate not guaranteed results */
                    $totalAmount = $recPriceInfoItem->amount;
                    $rateGuaranteed = false;
                    break;
                } else {
                    $totalAmount = $recPriceInfo[0]->amount;
                    $rateGuaranteed = true;
                }
            }

            /* Recommendaton References */
            $segmentFlightReferences = Data::dataToArray($recommendation->segmentFlightRef);

            foreach ($segmentFlightReferences as $segmentFlightRefKey => $segmentFlightRef) {
                /* Recommendations for a single pricing */

                $result = new \stdClass();

                /* Flight Proposals and Currency Conversions */
                $referencingDetails = Data::dataToArray($segmentFlightRef->referencingDetail);

                $result->segmentFlightRef = [];
                foreach ($referencingDetails as $referencingDetailKey => $referencingDetail) {
                    /* Get Only Sector refrernces from refQualifier = S */
                    if ($referencingDetail->refQualifier == 'S') {
                        $result->segmentFlightRef[] = $referencingDetail;

                        if ($referencingDetailKey == 0) {
                            $flightDetails = $this->getFlightPrposals($referencingDetail->refNumber, $flightSegments[0]->groupOfFlights);
                        }
                        if ($referencingDetailKey == 1) {
                            $ReturnflightDetails = $this->getFlightPrposals($referencingDetail->refNumber, $flightSegments[1]->groupOfFlights);
                        }
                    }
                }

                if (!isset($flightDetails)) {
                    break;
                }

                $recommendationRef += 1;

                /* returns array in mulitple passenger types*/
                $flightPrice = Data::dataToArray($recommendation->paxFareProduct);

                /* Get Flight booking Classes */
                $majCabin = [];
                $cabinProduct = [];

                $fareDetailsArr = Data::dataToArray($flightPrice[0]->fareDetails);
                foreach ($fareDetailsArr as $fareDetails) {
                    $majCabin[] = $this->getCabinDescription($fareDetails->majCabin->bookingClassDetails->designator);
                    $cabinProduct[] = $this->seatStatus($fareDetails->groupOfFares);
                }

                $majAirline = null;
                $codeShareDetails = Data::dataToArray($flightPrice[0]->paxFareDetail->codeShareDetails);
                foreach ($codeShareDetails as  $codeShareDetail) {
                    if (isset($codeShareDetail->transportStageQualifier) && $codeShareDetail->transportStageQualifier == 'V') {
                        $majAirline = $codeShareDetail->company;
                    }
                }

                /* Get Pax Fare Details from  recommentaion*/
                $paxFareList = [];
                foreach ($flightPrice as $flightPriceKey => $flightPriceitem) {
                    $type = $flightPriceitem->paxReference->ptc;
                    $noOfPassengers = count($flightPriceitem->paxReference->traveller);

                    $taxesAndFees = $flightPriceitem->paxFareDetail->totalTaxAmount;
                    $total = $flightPriceitem->paxFareDetail->totalFareAmount;
                    $baseFare = round($total - $taxesAndFees, 2);

                    if (isset($flightPriceitem->fare)) {
                        $fareRules = $flightPriceitem->fare ?? null;

                        if (!is_array($fareRules)) {
                            $fareRules = Data::dataToArray($fareRules);
                        }

                        $paxFareRules = [];
                        foreach ($fareRules as $fareRulekey => $fareRule) {
                            $informationType = $fareRule->pricingMessage->freeTextQualification->informationType;
                            $description = $fareRule->pricingMessage->description;
                            $monetaryDetail = $fareRule->monetaryInformation->monetaryDetail ?? null;

                            $paxFareRule = new Rules([
                               'informationType' => $informationType,
                               'description'     => $description,
                               'monetaryDetail'  => $monetaryDetail,
                            ]);
                            $paxFareRules[] = $paxFareRule;
                        }
                    }

                    $paxFare = new paxFare([
                          'type'           => $type,
                          'noOfPassengers' => $noOfPassengers,
                          'baseFare'       => $baseFare,
                          'taxesAndFees'   => $taxesAndFees,
                          'total'          => $total,
                          'paxFareRules'   => $paxFareRules ?? null,
                      ]);

                    $paxFareList[] = $paxFare;
                }

                $flightInfo = $this->optimizeInfo($flightDetails->flightDetails);
                $flightTiming = $this->getFlightDetails($flightDetails->flightDetails, $cabinProduct[0]->class);

                $segments = [];

                $segments[] = [
                                'ref'              => 1,
                                'flightDetails'    => $flightTiming->info,
                                'majCabin'         => $majCabin,
                                'majAirline'       => $flightDetails->MajAirline,
                                'stopInfo'         => $flightInfo->stopInfo,
                                'airports'         => $flightInfo->airports,
                                'seatAvailability' => $cabinProduct[0]->status,
                                'totalFlyingTime'  => $flightDetails->EFT,
                            ];

                if (isset($ReturnflightDetails)) {
                    $returnFlightInfo = $this->optimizeInfo($ReturnflightDetails->flightDetails);
                    $returnflightTiming = $this->getFlightDetails($ReturnflightDetails->flightDetails, $cabinProduct[1]->class);
                    $segments[] = [
                                        'ref'              => 2,
                                        'flightDetails'    => $returnflightTiming->info,
                                        'majCabin'         => $majCabin,
                                        'majAirline'       => $flightDetails->MajAirline,
                                        'stopInfo'         => $returnFlightInfo->stopInfo,
                                        'airports'         => $returnFlightInfo->airports,
                                        'seatAvailability' => $cabinProduct[1]->status,
                                        'totalFlyingTime'  => $ReturnflightDetails->EFT,
                                    ];
                }

                $Recommendation = new Recommendation([
                 'ref'              => $recommendationRef,
                 'segments'         => $segments,
                 'rateGuaranteed'   => $rateGuaranteed,
                 'majAirline'       => $majAirline,
                 'provider'         => self::PROVIDER,
                 'fareSummary'      => new fareSummary([
                      'currency' => $currency,
                      'pax'      => $paxFareList,
                      'total'    => $totalAmount,
                    ]),
                ]);

                $results[] = $Recommendation;
            }
        }

        return $results;
    }

    public function FareMasterPricerCalendar($opt)
    {
        $passengers = $this->getPassengersCount($opt->passengers);

        $itineraries = $this->getItinerarCount($opt->itineraries);

        $calendarSearchOpt = new FareMasterPricerCalendarOptions([
           'nrOfRequestedResults'    => $opt->nrOfRequestedResults,
           'nrOfRequestedPassengers' => $opt->nrOfRequestedPassengers,
           'passengers'              => $passengers,
           'itinerary'               => $itineraries,
            'currencyOverride'       => $opt->currencyOverride,
       ]);

        $fareMPC = $this->amadeusClient->fareMasterPricerCalendar($calendarSearchOpt);

        return ['provider' => self::PROVIDER,
       'result'            => $fareMPC, ];
    }

    public function FareMasterPricerTravelboardSearch($opt)
    {
        $passengers = $this->getPassengersCount($opt->passengers);
        $itineraries = $this->getItinerarCount($opt->itineraries);

        $opt = new FareMasterPricerTbSearch([
                'nrOfRequestedResults'    => $opt->nrOfRequestedResults,
                'nrOfRequestedPassengers' => $opt->nrOfRequestedPassengers,
                'passengers'              => $passengers,
                'itinerary'               => $itineraries,
                'flightOptions'           => [
                    FareMasterPricerTbSearch::FLIGHTOPT_PUBLISHED,
                    FareMasterPricerTbSearch::FLIGHTOPT_UNIFARES,
                    FareMasterPricerTbSearch::FLIGHTOPT_NO_SLICE_AND_DICE,
                    'CUC',
                ],
                 'currencyOverride' => $opt->currencyOverride,
            ]);

        $fareMPTS = $this->amadeusClient->fareMasterPricerTravelBoardSearch($opt);

        return ['provider' => self::PROVIDER,
       'result'            => $fareMPTS, ];
    }

    public function Fare_InformativePricingWithoutPNR()
    {
        $informativePricingResponse = $this->amadeusClient->fareInformativePricingWithoutPnr(
          new FareInformativePricingWithoutPnrOptions([
              'passengers' => [
                  new Passenger([
                      'tattoos' => [1],
                      'type'    => Passenger::TYPE_ADULT,
                  ]),
              ],
              'segments' => [
                  new Segment([
                      'departureDate'    => \DateTime::createFromFormat('Y-m-d H:i:s', '2018-02-20 01:00:00'),
                      'from'             => 'CMB',
                      'to'               => 'SIN',
                      'marketingCompany' => 'UL',
                      'flightNumber'     => '306',
                      'bookingClass'     => 'V',
                      'segmentTattoo'    => 1,
                      'groupNumber'      => 1,
                  ]),
              ],
          ])
      );

        return $informativePricingResponse;
    }

    public function Air_SellFromRecommendation($options)
    {
        $opt = new AirSellFromRecommendation($options);
        $sellResult = $this->amadeusClient->airSellFromRecommendation($opt->RecOption);

        return $sellResult;
    }

    public function PNR_AddMultiElements($travellerInfo, $company)
    {
        $PnrAddOpt = new PNR_AddMultiElements($travellerInfo, $company);
        $createdPnr = $this->amadeusClient->pnrCreatePnr($PnrAddOpt->opt);

        return $createdPnr;
    }

    public function PNR_AddMultiElementsEnd()
    {
        $pnrReply = $this->amadeusClient->pnrAddMultiElements(
            new PnrAddMultiElementsOptions([
                'actionCode' => [
                11, //ET: END AND RETRIEVE
                30, //30
                ],
            ])
        );

        return $pnrReply;
    }

    public function createTSTFromPricing()
    {
        $createTstResponse = $this->amadeusClient->ticketCreateTSTFromPricing(
        new TicketCreateTstFromPricingOptions([
            'pricings' => [
                new Pricing([
                    'tstNumber' => 1,
                ]),
                new Pricing([
                    'tstNumber' => 2,
                ]),
            ],
        ])
        );

        return $createTstResponse;
    }

    public function docIssuance()
    {
        $issueTicketResponse = $this->amadeusClient->docIssuanceIssueTicket(
          new DocIssuanceIssueTicketOptions([
              'options' => [
                  DocIssuanceIssueTicketOptions::OPTION_ETICKET,
              ],
          ])
      );

        return $issueTicketResponse;
    }

    public function Air_FlightInfo()
    {
        $flightInfo = $this->amadeusClient->airFlightInfo(
        new AirFlightInfoOptions([
            'airlineCode'       => 'OD',
            'flightNumber'      => '186',
            'departureDate'     => \DateTime::createFromFormat('Y-m-d', '2017-12-15'),
            'departureLocation' => 'CMB',
            'arrivalLocation'   => 'KUL',
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
                'departure'     => 'CMB',
                'arrival'       => 'KUL',
                'airline'       => 'OD',
                'flightNumber'  => '186',
            ]),
        ])
    );

        return $seatmapInfo;
    }

    public function PNR_Retrieve($pnr)
    {
        $pnrContent = $this->amadeusClient->pnrRetrieve(
             new PnrRetrieveOptions(['recordLocator' => $pnr])
      );

        return $pnrContent;
    }

    public function PNR_Retrieve_By_Office()
    {
        $pnrContent = $this->amadeusClient->pnrRetrieve(
             new PnrRetrieveOptions(['officeId' => 'CMBI228AR',
    'lastName'                                  => 'AROSHA JAYASANKA DE SILVA', ])
      );

        return $pnrContent;
    }

    public function PNR_Cancel($pnr)
    {
        $cancelReply = $this->amadeusClient->pnrCancel(
             new PnrCancelOptions([
              'recordLocator'   => $pnr,
              'cancelItinerary' => true,
              'actionCode'      => PnrCancelOptions::ACTION_END_TRANSACT,
        ])
      );

        return $cancelReply;
    }

    public function getLastRequest()
    {
        return $this->amadeusClient->getLastRequest();
    }

    public function getLastResponse()
    {
        return $this->amadeusClient->getLastResponse();
    }

    public function getLastRequestHeaders()
    {
        return $this->amadeusClient->getLastRequestHeaders();
    }

    public function getLastResponseHeaders()
    {
        return $this->amadeusClient->getLastResponseHeaders();
    }

    public function FarePricePnrWithBookingClassOptions($validatingCarrier = false)
    {
        $pricingResponse = $this->amadeusClient->farePricePnrWithBookingClass(
            new FarePricePnrWithBookingClassOptions([
                'overrideOptions' => [
                    FarePricePnrWithBookingClassOptions::OVERRIDE_FARETYPE_PUB,
                    FarePricePnrWithBookingClassOptions::OVERRIDE_FARETYPE_UNI,
                    FarePricePnrWithBookingClassOptions::OVERRIDE_RETURN_LOWEST,
                ],
            ])
        );

        return $pricingResponse;
    }
}

/*test*/
