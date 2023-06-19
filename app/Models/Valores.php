<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Valores extends Model
{
    use HasUuids;

    protected $table = 'valores';
    protected $fillable = ['page','title','subtitle','informationTable','name','amount'];
}
