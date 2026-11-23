<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Finding extends Model
{
    //
    protected $fillable = ['report_id', 'description', 'status', 'severity', 'image_path',];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }
}
