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
        'show_logo',
        'show_certificate_title',
        'show_student_name',
        'show_award_description',
        'show_award_date',
        'show_principal_name',
        'show_program_coordinator_name',
    ];

    protected $casts = [
        'awarded_date' => 'date',
        'show_logo' => 'boolean',
        'show_certificate_title' => 'boolean',
        'show_student_name' => 'boolean',
        'show_award_description' => 'boolean',
        'show_award_date' => 'boolean',
        'show_principal_name' => 'boolean',
        'show_program_coordinator_name' => 'boolean',
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
