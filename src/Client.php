<?php

namespace appletechlabs\flight;

use appletechlabs\flight\Providers\AmadeusSoapProvider;


/**
* 
*/
class Client 
{

  public $AmadeusSoap;
  
  public function __construct($data = [])
  {
      $this->loadFromArray($data);
  }

  protected function loadFromArray(array $data)
  {
      if (count($data) > 0) {
            if (isset($data['amadeus'])) {
              $this->AmadeusSoap = new AmadeusSoapProvider();
              $this->AmadeusSoap->officeId = $data['amadeus']['officeId'];
              $this->AmadeusSoap->userId = $data['amadeus']['userId'];
              $this->AmadeusSoap->passwordData = $data['amadeus']['passwordData'];
              $this->AmadeusSoap->wsdl = $data['amadeus']['wsdl'];
              $this->AmadeusSoap->passwordLength = $data['amadeus']['passwordLength'];
              $this->AmadeusSoap->receivedFrom = $data['amadeus']['receivedFrom'];
            }
        }
  }

  public function test()
  {
    return $this->AmadeusSoap;
  }

  public function setup()
  {
    $this->AmadeusSoap->setup();
  }

  public function FareMasterPricerCalendar($calendarSearchOpt)
  {
      return $this->AmadeusSoap->FareMasterPricerCalendar($calendarSearchOpt);
  }


}


?>