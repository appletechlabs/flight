<?php

namespace appletechlabs\flight\Search;


class CalendarSearch
{
    public $nrOfRequestedResults;
    public $nrOfRequestedPassengers;
    public $passengers;
    public $itineraries;
    public $currencyOverride;

    /**
     * CalendarSearch constructor.
     * @param array $data
     */
    function __construct($data = [])
    {
        $this->loadFromArray($data);
    }

    /**
     * @param array $data
     */
    protected function loadFromArray(array $data)
    {
        if (count($data) > 0) {
            $this->nrOfRequestedResults = $data['nrOfRequestedResults'];
            $this->nrOfRequestedPassengers = $data['nrOfRequestedPassengers'];
            $this->passengers = $data['passengers'];
            $this->itineraries = $data['itineraries'];
            $this->currencyOverride = $data['currencyOverride'];

        }
    }


}

