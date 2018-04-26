<?php

namespace appletechlabs\flight\Providers\AmadeusSoapProvider\PNR_AddMultiElements;

/**
 * Create DOCS string according to the format P-LKA-N0000000-LKA-DDMAYYY-M-DDOCTYY-SURENAME-FIRSTNAME
 * Should contain P-[3 Letter Country Code]-[Passport Number]-[3 Letter Country Code]-[Bírth day]-[Gender]-[Passport Issued Date]-[SURENAME]-[FIRSTNAME].
 */
class Passport
{
    private $ppString;

    /**
     * Construct Passport.
     *
     * @param Params $params
     */
    public function __construct(array $params)
    {
        $ppStrings[] = 'P';
        $ppStrings[] = $params['countryCode'];
        $ppStrings[] = $params['passportNo'];
        $ppStrings[] = $params['countryCode'];
        $ppStrings[] = $params['birthDate'];
        $ppStrings[] = $params['gender'];
        $ppStrings[] = $params['expireDate'];
        $ppStrings[] = $params['surename'];
        $ppStrings[] = $params['firstName'];
        $this->ppString = implode('-', $ppStrings);
    }

    /**
     * Return converted passport string.
     */
    public function getPPString()
    {
        return $this->ppString;
    }
}
