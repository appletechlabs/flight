<?php

namespace appletechlabs\flight;

use appletechlabs\flight\Providers\AmadeusSoapProvider;
use appletechlabs\flight\Providers\AmadeusSoapProvider\AirSellFromRecommendation;


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
  public function FareMasterPricerCalendarSort($calendarResult)
  {

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
        return  $this->AmadeusSoap->optimizeResults($fmptResult['result']);
      }
      else
      {
        return $fmptResult['result'];       
      }
  }

  public function fareBoardAndCalendarSearch($Opt,$calendarSearchOpt)
  {
    $calendarResult =  $this->FareMasterPricerCalendar($calendarSearchOpt);
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

    $result = [];
    $result['result'] = new \stdClass();

    if ($rawfmptResult['result']->status !== "OK" || $rawcalendarResult['result']->status !== "OK" ) 
    {       
      $result['result']->status = 'ERR';
      if ($rawfmptResult['result']->status !== "OK") {
        $result['result']->errResponse =  $rawfmptResult['result'];
      }

      if ($rawcalendarResult['result']->status !== "OK") {
        $result['result']->errResponse =  $rawcalendarResult['result'];
      }
    }
    else
    {
      $result['result']->status = 'OK';
      $result['calendarSearch'] = $this->FareMasterPricerCalendarSort($rawcalendarResult);
      $result['fareBoardSearch'] =  $this->fareBoardSearchOptimzed($rawfmptResult);      
    }
    return $result;
  }

  public function SellFromRecommendation($options)
  {

    $airSellRec = $this->AmadeusSoap->Air_SellFromRecommendation($options);
    return $airSellRec;
     
  }


}



?>