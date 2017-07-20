<?php

namespace InvestOnlineAdWordsApi;

use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSessionBuilder;
use Google\AdsApi\Common\OAuth2TokenBuilder;
use InvestOnlineAdWordsApi\Exceptions\ConfigFileDoesNotExistException;
use InvestOnlineAdWordsApi\Exceptions\MissingServiceClassException;

/**
 * Class AdWordsService
 * @package InvestOnlineAdWordsApi
 */
abstract class AdWordsService
{

    /**
     * Name of the service class to initialize from the Google Ads API Library
     * @var string $serviceClass
     */
    protected $serviceClass;

    /**
     * @var $service
     */
    protected $service;

    /**
     * AdWordsService constructor.
     * @param string $configFile
     */
    public function __construct($configFile)
    {
        if (!class_exists($this->serviceClass)) {
            throw new MissingServiceClassException;
        }

        if (!file_exists($configFile)) {
            throw new ConfigFileDoesNotExistException;
        }

        $session = (new AdWordsSessionBuilder())
            ->fromFile($configFile)
            ->withOAuth2Credential(
                (new OAuth2TokenBuilder())
                    ->fromFile($configFile)
                    ->build()
            )
            ->build();

        $this->service = (new AdwordsServices())->get($session, $this->serviceClass);
    }

}
