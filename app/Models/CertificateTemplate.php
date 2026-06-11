<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateTemplate extends Model
{
    protected $table = 'certificate_templates';

    protected $fillable = [
        'template_name', 'description', 'file_path', 'file_type', 'status', 'uploaded_by'
    ];

    public function awards(): HasMany
    {
        return $this->hasMany(CertificateAward::class, 'template_id');
    }
}
