<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $fillable = [
        'file_id',
        'file_name'
    ];

    public function user()
    {
        return $this->hasMany(User::class, 'id', 'user_id');
    }
    public function coauthor_file()
    {
        return $this->belongsToMany(User::class, 'coauthor', 'file_id', 'user_id');
    }

}
