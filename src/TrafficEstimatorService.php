<?php

namespace InvestOnlineAdWordsApi;
use Google\AdsApi\AdWords\v201809\cm\ApiException;
use Google\AdsApi\AdWords\v201809\cm\Keyword;
use Google\AdsApi\AdWords\v201809\cm\Location;
use Google\AdsApi\AdWords\v201809\cm\Money;
use Google\AdsApi\AdWords\v201809\cm\RateExceededError;
use Google\AdsApi\AdWords\v201809\o\AdGroupEstimateRequest;
use Google\AdsApi\AdWords\v201809\o\CampaignEstimateRequest;
use Google\AdsApi\AdWords\v201809\o\KeywordEstimate;
use Google\AdsApi\AdWords\v201809\o\KeywordEstimateRequest;
use Google\AdsApi\AdWords\v201809\o\TrafficEstimatorResult;
use Google\AdsApi\AdWords\v201809\o\TrafficEstimatorSelector;
use InvestOnlineAdWordsApi\Exceptions\KeywordEstimateCountDoesNotMatchException;
use InvestOnlineAdWordsApi\Exceptions\NoAdGroupEstimatesException;
use InvestOnlineAdWordsApi\Exceptions\NoCampaignEstimatesException;
use InvestOnlineAdWordsApi\Exceptions\NoKeywordEstimatesException;

/**
 * Class TrafficEstimatorService
 * @package InvestOnlineAdWordsApi
 */
final class TrafficEstimatorService extends AdWordsService
{

    use CleansKeywords;

    /**
     * The page limit to send to the API
     */
    const PAGE_LIMIT = 500;

    /**
     * The maximum amount of keywords that the service wrapper should accept
     */
    const KEYWORD_LIMIT = 500;

    /**
     * The AdWords Api Client Service class
     * @var string $serviceClass
     */
    protected $serviceClass = \Google\AdsApi\AdWords\v201809\o\TrafficEstimatorService::class;

    /**
     * @var \Google\AdsApi\AdWords\v201809\o\TrafficEstimatorService $service
     */
    protected $service;

    /**
     * @param array $keywords
     * @param null $country
     * @return array
     * @throws ApiException
     */
    public function get(array $keywords, $country = null)
    {
        $keywords = $this->cleanKeywords($keywords);

        $keywordEstimateRequests = $this->mapToKeywordEstimateRequests($keywords);

        $criteria = [];

        if ($country !== null) {
            $criteria[] = new Location($country);
        }

        try {

            $result = $this->service->get(new TrafficEstimatorSelector([
                new CampaignEstimateRequest(null, null, [
                    new AdGroupEstimateRequest(null, null, $keywordEstimateRequests, new Money(null, 100 * 1000000))
                ], $criteria)
            ], false));

            $keywordEstimates = $this->getKeywordEstimatesFromResult($result);

            if (count($keywordEstimates) !== count($keywords)) {
                throw new KeywordEstimateCountDoesNotMatchException;
            }

            return array_combine($keywords, array_map(function (KeywordEstimate $keywordEstimate) {
                return (int)($keywordEstimate->getMax()->getImpressionsPerDay() * 30);
            }, $keywordEstimates));

        } catch (ApiException $e) {
            $error = $e->getErrors()[0];

            if (!$error instanceof RateExceededError) {
                throw $e;
            }

            sleep($error->getRetryAfterSeconds());

            return $this->get($keywords, $country);
        }
    }

    /**
     * @param TrafficEstimatorResult $result
     * @return KeywordEstimate[]
     */
    private function getKeywordEstimatesFromResult(TrafficEstimatorResult $result)
    {
        $campaignEstimates = $result->getCampaignEstimates();

        if (count($campaignEstimates) < 1) {
            throw new NoCampaignEstimatesException;
        }

        $adGroupEstimates = $campaignEstimates[0]->getAdGroupEstimates();

        if (count($adGroupEstimates) < 1) {
            throw new NoAdGroupEstimatesException;
        }

        $keywordEstimates = $adGroupEstimates[0]->getKeywordEstimates();

        if (count($keywordEstimates) < 1) {
            throw new NoKeywordEstimatesException;
        }

        return $keywordEstimates;
    }

    /**
     * @param array $keywords
     * @return array
     */
    private function mapToKeywordEstimateRequests(array $keywords)
    {
        return array_map(function($keyword) {
            return new KeywordEstimateRequest(null, new Keyword(null, null, null, $keyword, 'EXACT'));
        }, $keywords);
    }

}
