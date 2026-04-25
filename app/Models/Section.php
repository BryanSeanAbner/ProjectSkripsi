<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $table      = 'section';
    protected $primaryKey = 'section_id';
    public    $keyType    = 'string';
    public    $timestamps = false;

    protected $fillable = ['section_id', 'section_name', 'slug'];
}
