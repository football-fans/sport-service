<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    use HasFactory;

    protected $fillable = ['*'];
    public function league()
    {
        return $this->belongsTo(League::class);
    }
    public function teams() {
        return $this->belongsToMany(Team::class);
    }
}
