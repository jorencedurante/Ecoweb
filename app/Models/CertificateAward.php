<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateAward extends Model
{
    protected $table = 'certificate_awards';

    protected $fillable = [
        'student_id',
        'certificate_type',
        'certificate_title',
        'award_title',
        'award_description',
        'school_principal_name',
        'program_coordinator_name',
        'awarded_by',
        'awarded_date',
        'template_file_path',
        'generated_file_path',
        'pdf_file_path',
        'status',
        'issued_by',
    ];

    protected $casts = [
        'awarded_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
