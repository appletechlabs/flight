<?php

namespace appletechlabs\flight\Recommendations;

/**
 * Class Rules
 * @package appletechlabs\flight\Recommendations
 */
class Rules
{
    public $informationType;
    public $description;
    /* Could be a array*/
    public $monetaryDetail;
    /*amountType, amount, currency */


    /**
     * Rules constructor.
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
            $this->informationType = $data['informationType'];
            $this->description = $data['description'];
            $this->monetaryDetail = $data['monetaryDetail'];
        }
    }

// 3	Ticket by Fare Basis
// 4	Manual Manipulation of Taxes
// 40	LAST TKT DTE - SEE ADV PURCHASE
// 41	LAST TKT DTE - DATE OF ORIGIN
// 42	NO TKT RSTNS THRU SEE ADV PURCHASE
// 43	LAST TKT DTE - FARE DISC THIS DATE
// 44	LAST TKT "," - SEE SALES RSTNS
// 45	LAST TKT DATE
// 46	CHECK RULE FOR LAST TKT DATE
// 5	Not Fared At passenger Type Requested
// 70	TICKETS ARE NON-REFUNDABLE
// 71	TKTS ARE NON-REFUNDABLE AFTER DEPARTURE
// 72	TKTS ARE NON-REFUNDABLE BEFORE DEPARTURE
// 73	PENALTY APPLIES
// 74	PERCENT PENALTY APPLIES
// 75	PENALTY APPLIES - CHECK RULES
// 76	SUBJECT TO CANCELLATION
// 78	SURCHARGE APPLIES FOR PAPER TICKET
// 79	FARE VALID FOR E-TICKET ONLY
// 80	E-TICKET NOT PERMITTED
// SP	SPLIT PNR - DIFFERENT BOOKING CODES REQUIRED FOR LOWEST FARE
}

