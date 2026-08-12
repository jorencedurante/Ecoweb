<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateTemplate extends Model
{
    protected $table = 'certificate_templates';

    protected $fillable = [
        'template_name', 'description', 'file_path', 'file_type',
        'status', 'uploaded_by', 'visibility', 'template_type', 'created_by',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function awards(): HasMany
    {
        return $this->hasMany(CertificateAward::class, 'template_id');
    }
}
