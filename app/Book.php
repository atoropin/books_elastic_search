<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use ScoutElastic\Searchable;

class Book extends Model
{
    use Searchable;

    protected $indexConfigurator = BookIndexConfigurator::class;

    protected $searchRules = [
        BookSearchRule::class,
    ];

    protected $mapping = [
        'properties' => [
            'id' => [
                'type' => 'integer',
                'index' => 'not_analyzed'
            ],
            'title' => [
                'type' => 'string',
                'analyzer' => 'english'
            ],
            'exact_title' => [
                'type' => 'string',
                'analyzer' => 'english_exact'
            ],
            'author_id' => [
                'type' => 'integer',
                'index' => 'not_analyzed'
            ],
            'author_name' => [
                'type' => 'string',
                'analyzer' => 'english'
            ],
            'pub_date' => [
                'type' => 'date',
                'index' => 'not_analyzed'
            ],
            'is_public' => [
                'type' => 'integer',
                'index' => 'not_analyzed'
            ]
        ]
    ];

    protected $table = 'books';

    protected $fillable = [
        'title',
        'author_id',
        'pub_date',
        'is_public'
    ];

    public $timestamps = true;

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'exact_title' => $this->title,
            'author_id' => $this->author_id ? $this->author_id : null,
            'author_name' => $this->author ? $this->author->name : null,
            'pub_date' => $this->pub_date,
            'is_public' => $this->is_public
        ];
    }

    public function author()
    {
        return $this->belongsTo('App\Author');
    }

    public function pages()
    {
        return $this->hasMany('App\Page')
            ->orderBy('number');
    }

    public function page()
    {
        return $this->hasOne('App\Page');
    }

    public function getExtremeDatesArray()
    {
        $_booksMinYear = date('Y', strtotime(self::where('is_public', 1)->min('pub_date')));
        $_booksMaxYear = date('Y', strtotime(self::where('is_public', 1)->max('pub_date')));

        return [
            ['id' => 'min', 'name' => $_booksMinYear],
            ['id' => 'max', 'name' => $_booksMaxYear]
        ];
    }
}
