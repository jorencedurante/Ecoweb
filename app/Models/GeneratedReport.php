<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedReport extends Model
{
    protected $table = 'generated_reports';

    const UPDATED_AT = null;

    protected $fillable = [
        'report_type', 'report_title', 'date_from', 'date_to', 'generated_by', 'file_path'
    ];

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
