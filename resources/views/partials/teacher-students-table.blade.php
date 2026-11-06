@php
    $tid = $teacher?->id ?? 'unassigned';
    $tname = $teacher?->name ?? 'Unassigned Students';
@endphp
<div class="teacher-students-table" id="teacher-students-{{ $tid }}">
    @if($students)
    <div class="teacher-table-controls" style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;margin-bottom:16px;padding:20px;">
        <div class="search-group" style="display:flex;flex-direction:column;gap:4px;flex:1;min-width:250px;">
            <label style="font-size:12px;font-weight:600;color:#374151;">Search Student</label>
            <input type="text" id="teacherSearch-{{ $tid }}" name="search" value="{{ old('search', $search ?? '') }}" placeholder="Search student name or LRN..." aria-label="Search students" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;background:#fff;">
        </div>
        <div class="sort-group" style="display:flex;flex-direction:column;gap:4px;min-width:180px;">
            <label style="font-size:12px;font-weight:600;color:#374151;">Sort By</label>
            <select id="teacherSort-{{ $tid }}" name="sort" aria-label="Sort students" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;background:#fff;">
                <option value="alpha" {{ ($sort ?? 'alpha') === 'alpha' ? 'selected' : '' }}>Alphabetical (A–Z)</option>
                <option value="most" {{ ($sort ?? 'alpha') === 'most' ? 'selected' : '' }}>Most Collected</option>
                <option value="least" {{ ($sort ?? 'alpha') === 'least' ? 'selected' : '' }}>Least Collected</option>
            </select>
        </div>
        @if($teacher)
        <a href="{{ route('reports.student-activities.print', ['teacher_id' => $teacher->id, 'grade_level' => $gradeLevel ?? '', 'date_from' => $dateFrom ?? '', 'date_to' => $dateTo ?? '']) }}" target="_blank" class="btn btn-filter" style="font-size:12px;padding:10px 16px;text-decoration:none;white-space:nowrap;align-self:center;">🖨️ Print Report</a>
        @endif
    </div>

    <div class="table-responsive">
    <table>
        <colgroup>
            <col class="stu-col-rank">
            <col class="stu-col-name">
            <col class="stu-col-lrn">
            <col class="stu-col-grade">
            <col class="stu-col-bottles">
            <col class="stu-col-points">
            <col class="stu-col-claims">
            <col class="stu-col-activity">
        </colgroup>
        <thead>
            <tr>
                <th scope="col" style="width:40px;">#</th>
                <th scope="col">Student Name</th>
                <th scope="col">LRN</th>
                <th scope="col">Grade Level</th>
                <th scope="col">Total Bottles</th>
                <th scope="col">Total Points</th>
                <th scope="col">Total Claims</th>
                <th scope="col">Latest Activity</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $i => $s)
@php
    $enr = $s->enrollments->first(function ($e) use ($teacher) {
        return $teacher && $e->teacher_id === $teacher->id && $e->status === 'active';
    });
                $lastActivity = collect([$s->latest_collection_date, $s->latest_claim_date])->filter()->max();
            @endphp
            <tr>
                <td style="padding:10px 14px;">{{ (($students->currentPage() - 1) * 10) + $i + 1 }}</td>
                <td style="padding:10px 14px;font-weight:600;color:#111827;">
                        <a href="#" class="student-claims-link" data-student-id="{{ $s->id }}" data-student-name="{{ $s->full_name }}" data-student-lrn="{{ $s->lrn }}" style="color:#111827;text-decoration:none;font-weight:600;cursor:pointer;">
                            {{ $s->full_name }}
                        </a>
                    </td>
                <td style="padding:10px 14px;">{{ $s->lrn }}</td>
                <td style="padding:10px 14px;">{{ $s->grade_level }}</td>
                <td style="padding:10px 14px;color:#059669;font-weight:600;">{{ number_format($s->total_bottles ?? 0) }}</td>
                <td style="padding:10px 14px;">{{ number_format($s->total_points ?? 0) }}</td>
                <td style="padding:10px 14px;color:#7c3aed;font-weight:600;">{{ $s->total_claims ?? 0 }}</td>
                <td style="padding:10px 14px;color:#6b7280;font-size:12px;">{{ $lastActivity ? \Carbon\Carbon::parse($lastActivity)->format('M d, Y') : '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center;padding:30px;color:var(--text-light);">No students found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>

    @if($students->hasPages())
    <div class="pagination" style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;margin-top:8px;border-top:1px solid #e2e8f0;">
        <span class="page-info" style="font-size:13px;color:#6b7280;">
            Showing {{ (($students->currentPage() - 1) * 10) + 1 }} to {{ $students->lastItem() ?? 0 }} of {{ $students->total() }} students
        </span>
        <div class="page-btns" style="display:flex;gap:4px;">
            @if ($students->currentPage() > 1)
                <a href="#" class="page-btn" style="padding:6px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;color:#059669;background:#d1fae5;text-decoration:none;" data-page="{{ $students->currentPage() - 1 }}">Prev</a>
            @endif
            @for ($p = max(1, $students->currentPage() - 2); $p <= min($students->lastPage(), $students->currentPage() + 2); $p++)
                <a href="#" class="page-btn {{ $students->currentPage() == $p ? 'active' : '' }}" style="padding:6px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;color:{{ $students->currentPage() == $p ? '#059669' : '#374151' }};background:{{ $students->currentPage() == $p ? '#d1fae5' : '#fff' }};text-decoration:none;" data-page="{{ $p }}">{{ $p }}</a>
            @endfor
            @if ($students->hasMorePages())
                <a href="#" class="page-btn" style="padding:6px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;color:#059669;background:#d1fae5;text-decoration:none;" data-page="{{ $students->currentPage() + 1 }}">Next</a>
            @endif
        </div>
    </div>
    @endif
    @else
    <div style="padding:24px;text-align:center;color:#6b7280;">
        Click on the teacher card above to load students.
    </div>
    @endif
</div>
