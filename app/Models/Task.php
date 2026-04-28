<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'title',
        'description',
        'statut',
        'user_id'
    ];

    public function user()
    {
        $this->belongsTo(User::class);
    }
}
