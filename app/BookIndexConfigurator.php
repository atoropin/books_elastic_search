<?php

namespace App;

use ScoutElastic\IndexConfigurator;
use ScoutElastic\Migratable;

class BookIndexConfigurator extends IndexConfigurator
{
    use Migratable;

    protected $name = 'book_index';

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