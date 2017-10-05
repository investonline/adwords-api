<?php

namespace InvestOnlineAdWordsApi;

trait CleansKeywords
{
    /**
     * @param array $keywords
     * @return array
     */
    private function cleanKeywords(array $keywords)
    {
        $keywords = array_filter($keywords);                // Remove all null values

        $keywords = array_map(function($keyword) {          // Detect and change encoding
            $keyword = iconv(
                mb_detect_encoding($keyword, ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'ASCII']),
                'UTF-8',
                $keyword
            );

            $keyword = str_replace("\xc2\xa0", "", $keyword);   // Remove non-breaking spaces
            $keyword = trim($keyword);                          // Trim the keyword

            return $keyword;
        }, $keywords);

        $keywords = array_unique($keywords);                // Only allow unique keywords

        return array_values($keywords);                     // Return the array after resetting the indexes
    }
}