<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CertificateAward extends Model
{
    protected $table = 'certificate_awards';

    protected $fillable = [
        'student_id', 'teacher_id', 'award_title', 'award_description',
        'certificate_file', 'template_file', 'canvas_data', 'awarded_date',
        'generated_file_path', 'pdf_file_path', 'status', 'issued_by', 'signed_by',
    ];

    protected $casts = [
        'awarded_date' => 'date',
        'canvas_data' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Locate the FINAL completed certificate file, checking real existence
     * on the correct disk. Canonical order: PDF, generated composite image,
     * then legacy uploaded certificate_file. Template paths are NEVER
     * returned here — they are original blank backgrounds, not finished
     * certificates.
     */
    public function finalCertificateFile(): ?array
    {
        $candidates = [
            ['disk' => 'local', 'path' => $this->pdf_file_path],
            ['disk' => 'local', 'path' => $this->generated_file_path],
            ['disk' => 'public', 'path' => $this->certificate_file],
        ];

        foreach ($candidates as $candidate) {
            if ($candidate['path'] && Storage::disk($candidate['disk'])->exists($candidate['path'])) {
                return $candidate;
            }
        }

        return null;
    }

    public function hasFinalCertificateFile(): bool
    {
        return $this->finalCertificateFile() !== null;
    }

    /**
     * Locate the best INLINE display file (viewer/preview <img>): prefer the
     * composite generated image, then the PDF, then the legacy uploaded
     * certificate_file. Never a template.
     */
    public function displayCertificateFile(): ?array
    {
        $candidates = [
            ['disk' => 'local', 'path' => $this->generated_file_path],
            ['disk' => 'local', 'path' => $this->pdf_file_path],
            ['disk' => 'public', 'path' => $this->certificate_file],
        ];

        foreach ($candidates as $candidate) {
            if ($candidate['path'] && Storage::disk($candidate['disk'])->exists($candidate['path'])) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Locate the original template background image on whichever disk it
     * was stored on (legacy saveCanvas used the public disk; the award flow
     * uses the private local disk). Used only for client-side reconstruction
     * of legacy canvas_data awards, never as a finished certificate.
     */
    public function templateImagePath(): ?array
    {
        if (!$this->template_file) {
            return null;
        }

        foreach (['public', 'local'] as $disk) {
            if (Storage::disk($disk)->exists($this->template_file)) {
                return ['disk' => $disk, 'path' => $this->template_file];
            }
        }

        return null;
    }

    /**
     * Whether the completed certificate can be rendered for viewing:
     * a real final file exists, or saved canvas_data plus an available
     * template allow the legacy client-side reconstruction.
     */
    public function canRenderCertificate(): bool
    {
        if ($this->hasFinalCertificateFile()) {
            return true;
        }

        return !empty($this->canvas_data) && $this->templateImagePath() !== null;
    }
}
