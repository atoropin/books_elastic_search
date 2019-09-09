<?php

namespace App;

use ScoutElastic\SearchRule;

class BookSearchRule extends SearchRule
{
    /**
     * @inheritdoc
     */
    public function buildHighlightPayload()
    {
        //
    }

    /**
     * @inheritdoc
     */
    public function buildQueryPayload()
    {
        $query = $this->builder->query;

        $payloadArray = [
            'must' => [
                'term' => [
                    'is_public' => 1
                ]
            ]];

        preg_match('/^[\'"\x{00AB}\x{201C}](.*)[\'"\x{00BB}\x{201D}]$/u', $query, $matches);
        if (!empty($matches)) {
            $query = $matches[1];
            $payloadArray['should'] = [
                [
                    'match_phrase' => [
                        'exact_title' => [
                            'query' => $query,
                            'boost' => 2
                        ],
                    ]
                ]
            ];
        } else {
            $payloadArray['should'] = [
                [
                    'match_phrase' => [
                        'title' => [
                            'query' => $query,
                            'boost' => 2
                        ],
                    ]
                ]
            ];
        }

        $payloadArray['minimum_should_match'] = 1;

        return $payloadArray;
    }
}
