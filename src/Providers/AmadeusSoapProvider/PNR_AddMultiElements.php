<?php

namespace appletechlabs\flight\Providers\AmadeusSoapProvider;

use Amadeus\Client\RequestOptions\Pnr\Element\Contact;
use Amadeus\Client\RequestOptions\Pnr\Element\FormOfPayment;
use Amadeus\Client\RequestOptions\Pnr\Element\ServiceRequest;
use Amadeus\Client\RequestOptions\Pnr\Element\Ticketing;
use Amadeus\Client\RequestOptions\Pnr\Itinerary;
use Amadeus\Client\RequestOptions\Pnr\Reference;
use Amadeus\Client\RequestOptions\Pnr\Segment\Air;
use Amadeus\Client\RequestOptions\Pnr\Traveller;
use Amadeus\Client\RequestOptions\PnrCreatePnrOptions;

class PNR_AddMultiElements
{
    public $opt;

    public function __construct($contactInfo,$company)
    {
        $optArray = [];

        $this->opt = new PnrCreatePnrOptions();
        $this->opt->actionCode = 0; //0 Do not yet save the PNR and keep in context.
        foreach ($contactInfo as $info) {
            $this->opt->travellers[] = new Traveller([
                'number'        => $info['number'],
                'firstName'     => $info['firstName'],
                'lastName'      => $info['lastName'],
                'dateOfBirth'   => $info['dateOfBirth'],
                'travellerType' => $info['type'],
            ]);
        }

        // foreach ($itinerary as $itinerarykey => $itineraryItem) {
        //     $newItinerary = [];

        //     $newItinerary['from'] = $itineraryItem['from'];
        //     $newItinerary['to'] = $itineraryItem['to'];

        //     $newSegments = [];

        //     foreach ($itineraryItem['segments'] as $segment) {
        //         $newSegments[] = new Air([
        //           'date'         => $segment['date'],
        //           'origin'       => $segment['origin'],
        //           'destination'  => $segment['destination'],
        //           'flightNumber' => $segment['flightNumber'],
        //           'bookingClass' => $segment['bookingClass'],
        //           'company'      => $segment['company'],
        //         ]);

        //     }

        //     $newItinerary['segments'] = $newSegments;
        //     $this->opt->itineraries[] = new Itinerary($newItinerary);
        // }

        $this->opt->elements[] = new Ticketing([
            'ticketMode' => Ticketing::TICKETMODE_OK,
        ]);

        $this->opt->elements[] = new Contact([
            'type'  => Contact::TYPE_PHONE_MOBILE,
            'value' => $contactInfo[0]['contactNo'],
        ]);

        $this->opt->elements[] = new FormOfPayment([
            'type' => FormOfPayment::TYPE_CASH,
        ]);

        $this->opt->elements[] = new ServiceRequest([
            'type' => 'DOCS',
            'status' => ServiceRequest::STATUS_HOLD_CONFIRMED,
            'company' => 'UL',
            'quantity' => 1,
            'freeText' => [
                'P-LKA-N3582413-LKA-20MAY90-M-03OCT23-SILVA-AROSHA'
            ],
            'references' => [
                new Reference([
                    'type' => Reference::TYPE_PASSENGER_TATTOO,
                    'id' => 2
                ])
            ]
        ]);
        // $this->opt->elements[] = new ServiceRequest([
        //     'type' => 'DOCS',
        //     'status' => ServiceRequest::STATUS_HOLD_CONFIRMED,
        //     'company' => 'UL',
        //     'quantity' => 1,
        //     'freeText' => [
        //         'P-LKR-012345678-LKR-30JUN73-M-14APR22-SURNAME-FIRSTNAME'
        //     ],
        //     'references' => [
        //         new Reference([
        //             'type' => Reference::TYPE_PASSENGER_TATTOO,
        //             'id' => 2
        //         ])
        //     ]
        // ]);
        //The required Received From (RF) element will automatically be added by the library if you didn't provide one.

        return $this->opt;
    }
}
