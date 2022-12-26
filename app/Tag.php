<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    public    $timestamps = false;
    protected $fillable   = ['name', 'slug'];

    // public function setSlugAttribute($slug)
    // {
    //     if (!empty($slug)) {
    //         $this->attribute['slug'] = Str::slug($slug);
    //     }
    // }
}
