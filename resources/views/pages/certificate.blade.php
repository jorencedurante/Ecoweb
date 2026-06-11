@extends('layouts.admin')

@section('title', 'EcoCollect - Certificate Award')
@section('page-title', 'Certificate Award')
@section('page-subtitle', 'Manage certificate')

@section('content')
    <div style="display:flex;justify-content:flex-end;margin-bottom:20px;">
        <button class="btn btn-primary">+ Add Award</button>
    </div>

    <div class="certificate-card">
        <div class="cert-border">
            <div class="cert-logo">EC</div>
            <h2>EXCELLENCE IN WASTE<br>COLLECTION AWARD</h2>
            <p class="cert-subtitle">Presented to</p>
            <div class="cert-student">Kathleen E. Tabadero</div>
            <p class="cert-desc">
                For demonstrating outstanding commitment to environmental sustainability
                through active participation in the school waste collection program.
            </p>
            <!-- TODO: Replace with dynamic certificate data from database -->
            <div style="display:flex;justify-content:space-between;margin-top:30px;padding:0 20px;font-size:12px;color:var(--text-medium);">
                <div>___________________<br>School Principal</div>
                <div>___________________<br>Program Coordinator</div>
            </div>
        </div>
        <button class="btn btn-outline cert-print-btn" onclick="alert('Print placeholder - connect to PDF generation later')">🖨</button>
    </div>
@endsection
