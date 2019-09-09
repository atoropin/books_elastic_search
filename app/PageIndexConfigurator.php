<?php

namespace App;

use ScoutElastic\IndexConfigurator;
use ScoutElastic\Migratable;

class PageIndexConfigurator extends IndexConfigurator
{
    use Migratable;

    protected $name = 'page_index';

    protected $settings = [
        "analysis" => [
            "analyzer" => [
                "english_exact" => [
                    "tokenizer" => "standard",
                    "filter" => [
                        "lowercase"
                    ]
                ]
            ]
        ]
    ];
}