<?php

namespace appletechlabs\flight\Providers\AmadeusSoapProvider;

use Amadeus\Client\RequestOptions\Air\SellFromRecommendation\Itinerary;
use Amadeus\Client\RequestOptions\Air\SellFromRecommendation\Segment;
use Amadeus\Client\RequestOptions\AirSellFromRecommendationOptions;

class AirSellFromRecommendation
{
    public $RecOption;

    public function __construct($itinerary)
    {
        $airSellRecOptions = [];
        foreach ($itinerary as $itinerarykey => $itineraryItem) {
            $newItinerary = [];

            $newItinerary['from'] = $itineraryItem['from'];
            $newItinerary['to'] = $itineraryItem['to'];

            $newSegments = [];

            foreach ($itineraryItem['segments'] as $segment) {
                $newSegment = new Segment([
                  'departureDate'  => $segment['departureDate'],
                  'from'           => $segment['from'],
                  'to'             => $segment['to'],
                  'companyCode'    => $segment['companyCode'],
                  'flightNumber'   => $segment['flightNumber'],
                  'bookingClass'   => $segment['bookingClass'],
                  'nrOfPassengers' => $segment['nrOfPassengers'],
                  'statusCode'     => Segment::STATUS_SELL_SEGMENT,
                ]);

                $newSegments[] = $newSegment;
            }

            $newItinerary['segments'] = $newSegments;
            $airSellRecOptions['itinerary'][] = new Itinerary($newItinerary);
        }
        $this->RecOption = new AirSellFromRecommendationOptions($airSellRecOptions);
    }
}
