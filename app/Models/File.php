<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $fillable = [
        'file_id',
        'file_name'
    ];

    public function coauthor_file()
    {
        return $this->hasMany(Coauthor::class, 'file_id', 'id');
    }
}
