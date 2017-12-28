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

      if ($calendarResult['result']->status == "OK") 
      {
        return $this->AmadeusSoap->calendarMin($calendarResult['result']);
      }
      else
      {
        return $calendarResult['result'];       
      }
      
  }

  public function FareMasterPricerTravelboardSearch($Opt)
  {
      $fmptResult = $this->AmadeusSoap->FareMasterPricerTravelboardSearch($Opt);
      return $fmptResult;
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

  public function fareBoardAndCalendarSearch($Opt,$calendarSearchOpt)
  {
    $calendarResult =  $this->FareMasterPricerCalendarSort($calendarSearchOpt);
    $fmptResult = $this->FareMasterPricerTravelboardSearch($Opt);
    $result = [];

    $result['calendarSearch'] = $calendarResult;
    $result['fareBoardSearch'] = $fmptResult;

    return $result;
  }

  public function fareBoardAndCalendarSearchOptimzed($Opt,$calendarSearchOpt)
  {
    $rawResult = $this->fareBoardAndCalendarSearch($Opt,$calendarSearchOpt);

    $rawcalendarResult =  $rawResult['calendarSearch'];
    $rawfmptResult = $rawResult['calendarSearch'];
    $result = [];

    $result['calendarSearch'] = $rawcalendarResult;
    $result['fareBoardSearch'] =  $this->fareBoardSearchOptimzed($fmptResult);

    return $result;
  }



}


?>