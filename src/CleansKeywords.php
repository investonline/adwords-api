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
        $keywords = array_filter($keywords);        // Remove all null values
        $keywords = array_map('trim', $keywords);   // Trim the keywords
        $keywords = array_unique($keywords);        // Only allow unique keywords

        return array_values($keywords);             // Return the array after resetting the indexes
    }
}