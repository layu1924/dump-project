<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = ['project_id', 'tester'];

    // Relasi ke Model Project
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // Relasi ke Model Finding
    public function findings()
    {
        return $this->hasMany(Finding::class);
    }
}