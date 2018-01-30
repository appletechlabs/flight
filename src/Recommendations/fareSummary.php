<?php

namespace appletechlabs\flight\Recommendations;

/**
 * Class fareSummary.
 */
class fareSummary
{
    public $currency;
    public $total;
    public $pax;

    /**
     * fareSummary constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->loadFromArray($data);
    }

    /**
     * @param array $data
     */
    protected function loadFromArray(array $data)
    {
        if (count($data) > 0) {
            $this->currency = $data['currency'];
            $this->pax = $data['pax'];
            $this->total = $data['total'] ?? '';
        }
    }
}
