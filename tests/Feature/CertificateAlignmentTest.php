<?php

namespace Tests\Feature;

use Tests\TestCase;

class CertificateAlignmentTest extends TestCase
{
    public function test_manual_alignment_controls_are_removed(): void
    {
        $html = (string) $this->actingAs($this->createSuperAdmin())
            ->get(route('admin.certificate'))->assertOk()->getContent();

        foreach ([
            'autoAlignBtn', 'Auto Align Names',
            'saveAlignmentBtn', 'Save Alignment',
            'resetNamePositionsBtn', 'Reset Name Positions',
            'alignmentCandidates', 'applyCandidateLinesBtn',
            'Detected lines', 'Keep current position',
            'cursor: move', 'touch-action: none',
        ] as $needle) {
            $this->assertStringNotContainsString($needle, $html, $needle);
        }

        $this->assertStringNotContainsString('setPointerCapture', $html);
        $this->assertStringNotContainsString('tabindex="0" role="button"', $html);
    }

    public function test_automatic_ocr_alignment_and_saved_template_reuse_are_wired(): void
    {
        $source = (string) file_get_contents(resource_path('views/pages/certificate.blade.php'));

        foreach ([
            'js/ocrad.js',
            'function runAutomaticAlignment()',
            'function ocrTextBands(img)',
            "anchorBand(texts, ['given', 'awarded', 'presented', 'recipient'])",
            "anchorBand(texts, ['adviser', 'advisor', 'teacher'])",
            "anchorBand(texts, ['principal', 'oic', 'officer', 'director'])",
            'function rememberAlignment()',
            'function restoreSavedAlignment()',
            'alignmentVerified = false;',
            'setOutputControlsEnabled(false);',
            "We couldn't identify all name positions on this certificate.",
            'centerX: g.centerX',
            'isFinite(line.centerX)',
            'imageReady = false;',
            'if (pendingHash) {',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source, $needle);
        }

        $this->assertStringNotContainsString('window.alert(', $source);
    }

    public function test_certificate_page_keeps_only_the_expected_workflow_controls(): void
    {
        $html = (string) $this->actingAs($this->createSuperAdmin())
            ->get(route('admin.certificate'))->assertOk()->getContent();

        foreach ([
            'Upload Certificate Template', 'Search and Select Student',
            'Select Adviser', 'School OIC / Principal', 'Font Settings',
            'certificate-preview-wrapper', 'Print Preview', 'Award to Student',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }
}
