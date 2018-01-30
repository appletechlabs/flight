<?php

namespace appletechlabs\flight\Recommendations;

/**
 * Class paxFare
 * @package appletechlabs\flight\Recommendations
 */
class paxFare
{
    public $type;
    public $noOfPassengers;
    public $baseFare;
    public $taxesAndFees;
    public $total;
    public $paxFareRules;

    /**
     * paxFare constructor.
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
            $this->type = $data['type'];
            $this->noOfPassengers = $data['noOfPassengers'];
            $this->baseFare = $data['baseFare'];
            $this->taxesAndFees = $data['taxesAndFees'];
            $this->total = $data['total'];
            $this->paxFareRules = $data['paxFareRules'];
        }

    }
}

