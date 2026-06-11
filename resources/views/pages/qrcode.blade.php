@extends('layouts.admin')

@section('title', 'EcoCollect - QR Code Generation')
@section('page-title', 'QR Code Generation')
@section('page-subtitle', 'Generates QR codes for students, bottles, locations.')

@section('content')
    <div class="alert-success" id="qrSuccessAlert">
        ✅ QR Code created successfully!
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
        <div class="card">
            <div class="card-body">
                <h4 style="font-size:15px;font-weight:600;margin-bottom:16px;">Generate QR Code</h4>
                <div class="form-group">
                    <label>QR Type</label>
                    <select style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                        <option selected>LRN</option>
                        <option>Student ID</option>
                        <option>Bottle Batch</option>
                        <option>Location</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Student</label>
                    <select style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                        <option>Kathleen E. Tabadero</option>
                        <option>Joy O. Tabadero</option>
                        <option>Jerence C. Tabadero</option>
                        <option>Patricia R. Tabadero</option>
                        <option selected>Denver P. Tabadero</option>
                        <option>Karen N. Tabadero</option>
                    </select>
                </div>
                <button class="btn btn-primary btn-block" id="generateQrBtn">Generate QR Code</button>
                <!-- TODO: Generate QR code from real student LRN -->
            </div>
        </div>

        <div class="qr-preview">
            <h4 style="font-size:15px;font-weight:600;margin-bottom:16px;">QR Code Preview</h4>
            <div class="qr-placeholder">
                <div class="qr-inner">
                    <div style="font-size:48px;margin-bottom:8px;">📱</div>
                    <div>QR Code Placeholder</div>
                    <div style="font-size:11px;color:#999;">Real QR will appear here</div>
                </div>
            </div>
            <div class="qr-number">123456789016</div>
            <div class="qr-actions">
                <button class="btn btn-primary btn-sm">⬇ Download QR Code</button>
                <button class="btn btn-outline btn-sm" onclick="alert('Print placeholder')">🖨 Print QR Code</button>
            </div>
        </div>
    </div>

    <!-- TODO: Integrate QR code library (e.g., qrcode.js) for real generation -->
@endsection
