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
              $this->AmadeusSoap->setup($data['amadeus']);
            }
        }
  }

  public function test()
  {
    return $this->AmadeusSoap;
  }

  public function signIn()
  {
     return $this->AmadeusSoap->securitySignIn();
  }

  public function signOut()
  {
     return $this->AmadeusSoap->securitySignOut();
  }



  public function FareMasterPricerCalendar($calendarSearchOpt)
  {
      $calendarResult =  $this->AmadeusSoap->FareMasterPricerCalendar($calendarSearchOpt);
      return $calendarResult;


  }
  public function FareMasterPricerCalendarSort($calendarSearchOpt)
  {
      $calendarResult =  $this->AmadeusSoap->FareMasterPricerCalendar($calendarSearchOpt);
      return $this->AmadeusSoap->calendarMin($calendarResult['result']);


  }

  public function FareMasterPricerTravelboardSearch($Opt)
  {
      $fmptResult = $this->AmadeusSoap->FareMasterPricerTravelboardSearch($Opt);
      return $fmptResult;


     // return  $this->AmadeusSoap->optimizeResults($fmptResult['result']);
  }

  public function fareBoardSearchOptimzed($fmptResult)
  {
      if ($fmptResult['result']->status == "OK") 
      {
        return  $this->AmadeusSoap->optimizeResults($fmptResult['result']);
      }
      else
      {
        return $fmptResult['result'];       
      }
  }



}


?>