<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    protected $table = 'authors';

    protected $fillable = [
        'name'
    ];

    public $timestamps = true;

    public function books()
    {
        return $this->hasMany('App\Book');
    }

    /**
     * Only authors has at least one book
     * @param null $filterQuery to get by name starting at
     * @return mixed
     */
    public function getAuthorsArrayByFirstLetter($filterQuery = null)
    {
        return $filterQuery ?
            self::select('id', 'name')
                ->has('books')
                ->where('name', 'like', $filterQuery . '%')
                ->orderBy('name', 'asc')
                ->get()
                ->toArray() :
            self::select('id', 'name')
                ->has('books')
                ->orderBy('name', 'asc')
                ->get()
                ->toArray();
    }
}
