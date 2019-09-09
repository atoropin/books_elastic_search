<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use ScoutElastic\Searchable;

class Page extends Model
{
    use Searchable;

    protected $indexConfigurator = PageIndexConfigurator::class;

    protected $searchRules = [
        PageSearchRule::class,
    ];

    protected $mapping = [
        'properties' => [
            'id' => [
                'type' => 'integer',
                'index' => 'not_analyzed'
            ],
            'book_id' => [
                'type' => 'integer',
                'index' => 'not_analyzed'
            ],
            'book_title' => [
                'type' => 'string',
                'index' => 'not_analyzed'
            ],
            'book_pub_date' => [
                'type' => 'date',
                'index' => 'not_analyzed'
            ],
            'book_author_id' => [
                'type' => 'integer',
                'index' => 'not_analyzed'
            ],
            'book_author_name' => [
                'type' => 'string',
                'index' => 'not_analyzed'
            ],
            'number' => [
                'type' => 'integer',
                'index' => 'not_analyzed'
            ],
            'text' => [
                'type' => 'string',
                'analyzer' => 'english'
            ],
            'exact_text' => [
                'type' => 'string',
                'analyzer' => 'english_exact'
            ],
            'is_public' => [
                'type' => 'integer',
                'index' => 'not_analyzed'
            ]
        ]
    ];

    protected $table = 'pages';

    protected $fillable = [
        'book_id',
        'number',
        'text',
        'is_public'
    ];

    public $timestamps = true;

    public function toSearchableArray()
    {
        if (!$this->book) {
            return [];
        }

        return [
            'id' => $this->id,
            'book_id' => $this->book->id ? $this->book->id : null,
            'title' => $this->book->title ? $this->book->title : null,
            'author_id' => $this->book->author_id ? $this->book->author_id : null,
            'author_name' => $this->book->author ? $this->book->author->name : null,
            'pub_date' => $this->book->pub_date ? $this->book->pub_date : null,
            'number' => $this->number,
            'text' => $this->text ? $this->text : null,
            'exact_text' => $this->text ? $this->text : null,
            'is_public' => $this->is_public
        ];
    }

    public function book()
    {
        return $this->belongsTo('App\Book');
    }
}
