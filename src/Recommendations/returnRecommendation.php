<?php

namespace appletechlabs\flight\Recommendations;
use appletechlabs\flight\Recommendations\segments;

class returnRecommendation
{
    public $ref;
    public $segments;
    public $fareSummary;
    public $majAirline;
    public $rateGuaranteed;
    public $provider;

    public function __construct($data = [])
    {
        $this->loadFromArray($data);
    }

    protected function loadFromArray(array $data)
    {
        if (count($data) > 0) {
            $this->ref = $data['ref'];
            foreach ($data['segments'] as $segment) {
                $this->segments[] = new segments($segment);
            }

            $this->fareSummary = $data['fareSummary'];
            $this->majAirline = $data['majAirline'];
            $this->rateGuaranteed = $data['rateGuaranteed'];
            $this->provider = $data['provider'];
        }
    }
}
