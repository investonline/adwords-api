<?php

namespace InvestOnlineAdWordsApi;

/**
 * Class TrafficEstimatorService
 * @package InvestOnlineAdWordsApi
 */
final class TrafficEstimatorService extends AdWordsService
{

    /**
     * The AdWords Api Client Service class
     * @var string $serviceClass
     */
    protected $serviceClass = \Google\AdsApi\AdWords\v201705\o\TrafficEstimatorService::class;

    public function get(array $keywords, $country)
    {



    }

}
