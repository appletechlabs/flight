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
        return $calendarResult;       
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
        return  $this->AmadeusSoap->optimizeResults2($fmptResult['result']);
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
    $rawfmptResult = $rawResult['fareBoardSearch'];

    if ($rawfmptResult['result']->status !== "OK") 
    {
        return $rawfmptResult['result'];
    }
    elseif ($rawcalendarResult['result']->status !== "OK" ) {
        return $rawcalendarResult['result'];
    }
    else
    {
        $result = [];

        $result['calendarSearch'] = $rawcalendarResult;
        $result['fareBoardSearch'] =  $this->fareBoardSearchOptimzed($rawfmptResult);

        return $result;      
    }

    
  }



}


?>