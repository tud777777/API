<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coauthor extends Model
{
    protected $fillable = [

    ];
    public function user()
    {
        return $this->hasMany(User::class, 'id', 'user_id');
    }
    public function file()
    {
        return $this->hasMany(File::class, 'id', 'file_id');
    }
}
