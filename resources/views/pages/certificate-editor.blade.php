@extends('layouts.admin')

@section('title', 'EcoCollect - Design Certificate')
@section('page-title', 'Design Certificate')


@section('content')
<a href="{{ route('admin.certificate') }}" class="back-link" style="margin-bottom:16px;display:inline-block;">← Back to Certificate Award</a>

<form id="certificateForm" method="POST"
      action="{{ isset($award) ? route('admin.certificate.update-canvas', $award->id) : route('admin.certificate.save-canvas') }}"
      enctype="multipart/form-data">
    @csrf
    @if(isset($award)) @method('PUT') @endif

    <div class="card" style="margin-bottom:20px;">
        <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:15px;font-weight:600;">{{ isset($award) ? 'Edit Certificate' : 'Design Certificate' }}</span>
            <span style="font-size:12px;color:var(--text-light);">Upload a template, add text, and customize</span>
        </div>
        <div class="card-body" style="padding:20px;">
            <div class="form-group">
                <label>Upload Certificate Template <span style="color:var(--red);">*</span></label>
                <input type="file" name="template_file" id="template_file"
                       accept=".jpg,.jpeg,.png" {{ isset($award) ? '' : 'required' }}
                       style="width:100%;padding:7px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;"
                       aria-label="Upload template file">
                @if(isset($award) && $award->template_file)
                <div style="font-size:11px;color:var(--text-light);margin-top:4px;">Leave empty to keep existing template: <strong>{{ basename($award->template_file) }}</strong></div>
                @else
                <div style="font-size:11px;color:var(--text-light);margin-top:4px;">Accepted: JPG, JPEG, PNG (max 10MB)</div>
                @endif
            </div>

            <div class="form-group" style="margin-top:12px;">
                <label>Certificate Title (optional)</label>
                <input type="text" name="award_title" placeholder="e.g. Certificate of Recognition"
                       value="{{ old('award_title', isset($award) ? $award->award_title : '') }}"
                       style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;"
                       aria-label="Certificate title">
            </div>

            <div class="form-group" style="margin-top:12px;">
                <label>Description (optional)</label>
                <textarea name="award_description" placeholder="Brief description of this certificate..."
                          style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;resize:vertical;min-height:60px;"
                          aria-label="Description">{{ old('award_description', isset($award) ? $award->award_description : '') }}</textarea>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
            <span style="font-size:15px;font-weight:600;">Certificate Design Area</span>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <button type="button" id="addTextBtn" class="btn btn-primary btn-sm">+ Add Text</button>
                <div style="border-left:1px solid var(--border);height:20px;"></div>
                <label style="font-size:12px;font-weight:500;">Size:</label>
                <input type="number" id="fontSizeControl" value="36" min="8" max="200" aria-label="Font size"
                       style="width:60px;padding:4px 6px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:12px;outline:none;">
                <label style="font-size:12px;font-weight:500;">Color:</label>
                <input type="color" id="fontColorControl" value="#000000" aria-label="Font color"
                       style="width:32px;height:28px;padding:1px;border:1px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;background:none;">
                <button type="button" id="boldToggle" class="btn btn-sm"
                        style="font-weight:700;border:1px solid var(--border);padding:4px 10px;font-size:12px;"
                        aria-label="Toggle bold"><strong>B</strong></button>
                <button type="button" id="deleteTextBtn" class="btn btn-sm"
                        style="background:var(--red);color:#fff;padding:4px 10px;font-size:12px;">Delete</button>
            </div>
        </div>
        <div class="card-body" style="padding:20px;">
            <div id="certificateCanvas" class="certificate-canvas {{ isset($award) && $award->template_file ? 'has-template' : '' }}">
                <img id="certificateBg" class="certificate-bg" src="{{ isset($award) && $award->template_file ? asset('storage/' . $award->template_file) : '' }}" alt="Certificate Template" style="{{ isset($award) && $award->template_file ? 'display:block;' : 'display:none;' }}">
                <div id="emptyTemplateMessage" class="empty-template-message" style="{{ isset($award) && $award->template_file ? 'display:none;' : '' }}">
                    Upload a certificate template to start designing.
                </div>
            </div>
            <input type="hidden" name="canvas_data" id="canvas_data" value="">
        </div>
    </div>

    <div style="display:flex;gap:12px;margin-bottom:24px;">
        <button type="submit" class="btn btn-success" style="padding:10px 32px;">Save Certificate</button>
        <button type="button" id="previewPrintBtn" class="btn btn-sm" style="background:#6B7280;color:#fff;padding:10px 24px;">Print Preview</button>
    </div>
</form>
@endsection

@push('scripts')
<style>
.certificate-canvas {
    position: relative;
    width: 100%;
    max-width: 1123px;
    aspect-ratio: 297 / 210;
    margin: 20px auto;
    background: #ffffff;
    border: 2px dashed #d1d5db;
    overflow: hidden;
}
.certificate-canvas.has-template {
    border: 1px solid #d1d5db;
}
.certificate-bg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
    z-index: 1;
    pointer-events: none;
}
.empty-template-message {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    font-weight: 600;
    z-index: 0;
}
.certificate-canvas.has-template .empty-template-message {
    display: none;
}
.certificate-text-box {
    position: absolute;
    z-index: 5;
    transform: translate(-50%, -50%);
    cursor: move;
    padding: 4px 8px;
    min-width: 80px;
    text-align: center;
    white-space: nowrap;
    outline: none;
    border: 1px solid transparent;
    line-height: 1.2;
    word-break: break-word;
    user-select: none;
}
.certificate-text-box:hover {
    border-color: rgba(0,174,239,0.3);
}
.certificate-text-box.selected {
    outline: 2px dashed #22c55e;
    background: rgba(255,255,255,0.25);
}
.btn-sm {
    padding: 6px 14px !important;
    font-size: 12px !important;
}
</style>
<script>
let selectedTextBox = null;
let isDragging = false;
let dragElement = null;

document.addEventListener('DOMContentLoaded', function () {
    @if(isset($award) && $award->template_file)
    @php
        $rawData = $award->canvas_data;
        $canvasArray = is_array($rawData) ? $rawData : (is_string($rawData) ? json_decode($rawData, true) : []);
    @endphp
    const existingCanvasData = @json($canvasArray ?: []);
    if (Array.isArray(existingCanvasData) && existingCanvasData.length > 0) {
        setTimeout(function () {
            existingCanvasData.forEach(function (box) {
                addTextBox(box.text || 'Text', {
                    x: box.x,
                    y: box.y,
                    fontSize: box.fontSize,
                    color: box.color,
                    fontWeight: box.fontWeight,
                    textAlign: box.textAlign || 'center'
                });
            });
        }, 100);
    }
    @endif
});

// --- TEMPLATE FILE UPLOAD & PREVIEW ---
document.getElementById('template_file')?.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

    const img = document.getElementById('certificateBg');
    const canvas = document.getElementById('certificateCanvas');
    const emptyMessage = document.getElementById('emptyTemplateMessage');

    const reader = new FileReader();
    reader.onload = function (e) {
        img.src = e.target.result;
        img.style.display = 'block';
        canvas.classList.add('has-template');
        if (emptyMessage) {
            emptyMessage.style.display = 'none';
        }
    };
    reader.readAsDataURL(file);
});

// --- ADD TEXT ---
document.getElementById('addTextBtn').addEventListener('click', function () {
    addTextBox('Enter text', { x: 50, y: 50, fontSize: 36, color: '#000000', fontWeight: 'normal' });
});

function addTextBox(text = 'Text', options = {}) {
    const canvas = document.getElementById('certificateCanvas');
    const box = document.createElement('div');
    box.className = 'certificate-text-box';
    box.contentEditable = true;
    box.innerText = text;
    box.style.left = (options.x || 50) + '%';
    box.style.top = (options.y || 50) + '%';
    box.style.fontSize = (options.fontSize || 36) + 'px';
    box.style.color = options.color || '#000000';
    box.style.fontWeight = options.fontWeight || 'normal';
    box.style.textAlign = options.textAlign || 'center';
    canvas.appendChild(box);
    selectTextBox(box);
    makeDraggable(box);
    box.addEventListener('click', function (e) {
        e.stopPropagation();
        selectTextBox(box);
    });
    box.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
        }
    });
}

// --- SELECT TEXT BOX ---
function selectTextBox(box) {
    document.querySelectorAll('.certificate-text-box').forEach(function (el) {
        el.classList.remove('selected');
    });
    box.classList.add('selected');
    selectedTextBox = box;

    const size = parseInt(box.style.fontSize) || 36;
    document.getElementById('fontSizeControl').value = size;
    document.getElementById('fontColorControl').value = rgbToHex(box.style.color) || '#000000';
}

function rgbToHex(rgb) {
    if (!rgb || rgb.startsWith('#')) return rgb;
    const match = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
    if (!match) return '#000000';
    return '#' + [match[1], match[2], match[3]].map(function (c) {
        return ('0' + parseInt(c).toString(16)).slice(-2);
    }).join('');
}

// --- DRAGGING ---
function makeDraggable(element) {
    element.addEventListener('mousedown', function (e) {
        if (e.target.closest('.certificate-text-box') !== element) return;
        e.preventDefault();
        selectTextBox(element);
        isDragging = true;
        dragElement = element;
    });
}

document.addEventListener('mousemove', function (e) {
    if (!isDragging || !dragElement) return;

    const canvas = document.getElementById('certificateCanvas');
    const rect = canvas.getBoundingClientRect();

    let x = ((e.clientX - rect.left) / rect.width) * 100;
    let y = ((e.clientY - rect.top) / rect.height) * 100;

    x = Math.max(0, Math.min(100, x));
    y = Math.max(0, Math.min(100, y));

    dragElement.style.left = x + '%';
    dragElement.style.top = y + '%';
});

document.addEventListener('mouseup', function () {
    isDragging = false;
    dragElement = null;
});

// Click on canvas background deselects
document.getElementById('certificateCanvas').addEventListener('click', function (e) {
    if (!e.target.closest('.certificate-text-box')) {
        document.querySelectorAll('.certificate-text-box.selected').forEach(function (el) {
            el.classList.remove('selected');
        });
        selectedTextBox = null;
    }
});

// --- FONT CONTROLS ---
document.getElementById('fontSizeControl').addEventListener('input', function () {
    if (selectedTextBox) {
        selectedTextBox.style.fontSize = this.value + 'px';
    }
});

document.getElementById('fontColorControl').addEventListener('input', function () {
    if (selectedTextBox) {
        selectedTextBox.style.color = this.value;
    }
});

document.getElementById('boldToggle').addEventListener('click', function () {
    if (selectedTextBox) {
        selectedTextBox.style.fontWeight =
            selectedTextBox.style.fontWeight === 'bold' || selectedTextBox.style.fontWeight === '700' ? 'normal' : 'bold';
    }
});

document.getElementById('deleteTextBtn').addEventListener('click', function () {
    if (selectedTextBox) {
        selectedTextBox.remove();
        selectedTextBox = null;
    } else {
        alert('Click on a text box to select it first.');
    }
});

// --- FORM SUBMIT: COLLECT CANVAS DATA ---
document.getElementById('certificateForm').addEventListener('submit', function (event) {
    const canvas = document.getElementById('certificateCanvas');
    const boxes = canvas.querySelectorAll('.certificate-text-box');

    const data = Array.from(boxes).map(function (box) {
        return {
            text: box.innerText.trim(),
            x: parseFloat(box.style.left) || 50,
            y: parseFloat(box.style.top) || 50,
            fontSize: parseInt(box.style.fontSize) || 36,
            color: box.style.color || '#000000',
            fontWeight: box.style.fontWeight || 'normal',
            textAlign: box.style.textAlign || 'center'
        };
    });

    document.getElementById('canvas_data').value = JSON.stringify(data);

    if (data.length === 0) {
        if (!confirm('No text added. Save certificate without text?')) {
            event.preventDefault();
            return;
        }
    }
});

// --- PRINT PREVIEW ---
document.getElementById('previewPrintBtn').addEventListener('click', function () {
    const canvas = document.getElementById('certificateCanvas');
    const boxes = canvas.querySelectorAll('.certificate-text-box');

    if (!document.getElementById('certificateBg').src && !document.getElementById('template_file').files[0]) {
        alert('Please upload a template first.');
        return;
    }

    const data = Array.from(boxes).map(function (box) {
        return {
            text: box.innerText.trim(),
            x: parseFloat(box.style.left) || 50,
            y: parseFloat(box.style.top) || 50,
            fontSize: parseInt(box.style.fontSize) || 36,
            color: box.style.color || '#000000',
            fontWeight: box.style.fontWeight || 'normal',
            textAlign: box.style.textAlign || 'center'
        };
    });

    const templateUrl = document.getElementById('certificateBg').src;
    const printWindow = window.open('', '_blank');

    let textHtml = '';
    data.forEach(function (box) {
        textHtml += '<div class="print-text-box" style="left:' + box.x + '%;top:' + box.y + '%;font-size:' + box.fontSize + 'px;color:' + box.color + ';font-weight:' + box.fontWeight + ';text-align:' + (box.textAlign || 'center') + ';">' + box.text + '</div>';
    });

    printWindow.document.write('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Print Preview</title><style>*{margin:0;padding:0;box-sizing:border-box;}@page{size:A4 landscape;margin:0;}body{margin:0;background:#fff;display:flex;justify-content:center;align-items:center;min-height:100vh;}.certificate-canvas{position:relative;width:297mm;height:210mm;overflow:hidden;}.certificate-canvas img{position:absolute;inset:0;width:100%;height:100%;object-fit:contain;}.print-text-box{position:absolute;transform:translate(-50%,-50%);white-space:nowrap;line-height:1.2;word-break:break-word;}@media print{body{margin:0;}.certificate-canvas{box-shadow:none;}}</style></head><body><div class="certificate-canvas"><img src="' + templateUrl + '" alt="Certificate template">' + textHtml + '</div><script>window.onload=function(){setTimeout(function(){window.print();},500);};<\/script></body></html>');
    printWindow.document.close();
});
</script>
@endpush
