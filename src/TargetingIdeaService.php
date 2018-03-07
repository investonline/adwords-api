<?php

namespace InvestOnlineAdWordsApi;

use Google\AdsApi\AdWords\v201802\cm\ApiException;
use Google\AdsApi\AdWords\v201802\cm\Location;
use Google\AdsApi\AdWords\v201802\cm\NetworkSetting;
use Google\AdsApi\AdWords\v201802\cm\Paging;
use Google\AdsApi\AdWords\v201802\cm\RateExceededError;
use Google\AdsApi\AdWords\v201802\o\LocationSearchParameter;
use Google\AdsApi\AdWords\v201802\o\MonthlySearchVolume;
use Google\AdsApi\AdWords\v201802\o\NetworkSearchParameter;
use Google\AdsApi\AdWords\v201802\o\RelatedToQuerySearchParameter;
use Google\AdsApi\AdWords\v201802\o\TargetingIdeaSelector;
use Google\AdsApi\Common\Util\MapEntries;
use InvestOnlineAdWordsApi\Exceptions\TooManyKeywordsException;

/**
 * Class TargetingIdeaService
 * @package InvestOnlineAdWordsApi
 */
final class TargetingIdeaService extends AdWordsService
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
    protected $serviceClass = \Google\AdsApi\AdWords\v201802\o\TargetingIdeaService::class;

    /**
     * @var \Google\AdsApi\AdWords\v201802\o\TargetingIdeaService $service
     */
    protected $service;

    /**
     * @param array $keywords
     * @param null|int $country
     * @return array
     * @throws ApiException
     */
    public function get(array $keywords, $country = null)
    {
        if (count($keywords) > self::KEYWORD_LIMIT) {
            throw new TooManyKeywordsException;
        }

        $selector = $this->buildSelector(
            $this->cleanKeywords($keywords),
            $country
        );

        return $this->retrieveResults($selector);
    }

    /**
     * @param TargetingIdeaSelector $selector
     * @return array
     * @throws ApiException
     */
    private function retrieveResults(TargetingIdeaSelector $selector)
    {
        try {

            $results = [];

            $page = $this->service->get($selector);

            if ($page->getEntries() === null) {
                $empty = [];

                foreach($selector->getSearchParameters()[0]->getQueries() as $keyword) {
                    $empty[$keyword] = $this->getEmptyResult();
                }

                return $empty;
            }

            foreach ($page->getEntries() as $entry) {
                $data = MapEntries::toAssociativeArray($entry->getData());
                $results[$data['KEYWORD_TEXT']->getValue()] = $this->mapResult($data);
            }

            return $results;

        } catch (ApiException $e) {
            $error = $e->getErrors()[0];

            if (!$error instanceof RateExceededError) {
                throw $e;
            }

            sleep($error->getRetryAfterSeconds());

            return $this->retrieveResults($selector);
        }
    }

    /**
     * @param array $keywords
     * @param $country
     * @return TargetingIdeaSelector
     */
    private function buildSelector(array $keywords, $country)
    {
        $searchParameters = [
            new RelatedToQuerySearchParameter(null, $keywords),
            new NetworkSearchParameter(null,
                new NetworkSetting(true, false, false, false)
            )
        ];

        if(!empty($country)) {
            $searchParameters[] = new LocationSearchParameter(null, [
                new Location($country)
            ]);
        }

        return new TargetingIdeaSelector($searchParameters,
            'KEYWORD',
            'STATS',
            ['SEARCH_VOLUME', 'KEYWORD_TEXT', 'AVERAGE_CPC', 'COMPETITION', 'TARGETED_MONTHLY_SEARCHES'],
            new Paging(0, self::PAGE_LIMIT)
        );
    }

    /**
     * @param array $data
     * @return array
     */
    private function mapResult(array $data)
    {
        $average = array_average(function(MonthlySearchVolume $volume) {
            return $volume->getCount();
        }, $data['TARGETED_MONTHLY_SEARCHES']->getValue() ?: []);

        return [
            'volume'        => $data['SEARCH_VOLUME']->getValue() ?: 0,
            'average'       => $average,
            'average_cpc'   => ($data['AVERAGE_CPC']->getValue() ? $data['AVERAGE_CPC']->getValue()->getMicroAmount() : 0) / 1000000,
            'competition'   => $data['COMPETITION']->getValue() ?: 0,
        ];
    }

    /**
     * @return array
     */
    private function getEmptyResult()
    {
        return [
            'volume'        => 0,
            'average'       => 0,
            'average_cpc'   => 0,
            'competition'   => 0,
        ];
    }

}
