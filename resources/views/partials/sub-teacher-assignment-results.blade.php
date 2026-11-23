<table id="subTeacherAssignmentsTable">
    <thead>
        <tr>
            <th scope="col">SUB TEACHER</th>
            <th scope="col">ASSIGNED TEACHER</th>
            <th scope="col">ACCESS LEVEL</th>
            <th scope="col">STATUS</th>
            <th scope="col" class="sta-actions-header">ACTIONS</th>
        </tr>
    </thead>
    <tbody>
    @forelse($subTeachers as $subTeacher)
        @php
            $teacher = $subTeacher->assignedTeacher;
            $teacherName = $teacher ? $teacher->name : 'Unassigned';
            $level = $subTeacher->sub_teacher_access_level ?: 'collaborator';
            $accessLabel = \App\Services\CollaborationService::accessLevels()[$level] ?? ucfirst($level);
            $status = $subTeacher->assignmentStatus();
            $hasAssignment = !empty($subTeacher->assigned_teacher_id);
        @endphp
        <tr>
            <td><strong>{{ $subTeacher->name }}</strong><br><span style="font-size:12px;color:#6b7280;">{{ $subTeacher->email }}</span></td>
            <td>{{ $teacherName }}</td>
            <td>{{ $hasAssignment ? $accessLabel : 'No Access' }}</td>
            <td><span class="sta-status sta-status-{{ strtolower($status) }}">{{ $status }}</span></td>
            <td>
                <div class="sta-actions">
                    @if($hasAssignment)
                        <button type="button" class="sta-icon-btn sta-icon-view sta-view" data-id="{{ $subTeacher->id }}" title="View Assignment" aria-label="View Assignment">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                        <button type="button" class="sta-icon-btn sta-icon-edit sta-edit" data-id="{{ $subTeacher->id }}" data-name="{{ $subTeacher->name }}" data-teacher-id="{{ $subTeacher->assigned_teacher_id }}" data-access-level="{{ $level }}" data-custom='@json($subTeacher->sub_teacher_permissions ?? [])' title="Edit Assignment" aria-label="Edit Assignment">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
                        </button>
                        <button type="button" class="sta-icon-btn sta-icon-unassign sta-unassign" data-id="{{ $subTeacher->id }}" data-name="{{ $subTeacher->name }}" title="Unassign Sub Teacher" aria-label="Unassign Sub Teacher">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="17" y1="11" x2="23" y2="11"></line></svg>
                        </button>
                    @else
                        <button type="button" class="btn btn-sm btn-success sta-edit" data-mode="create" data-id="{{ $subTeacher->id }}" data-name="{{ $subTeacher->name }}" data-teacher-id="" data-access-level="collaborator" data-custom='[]' title="Assign Sub Teacher" aria-label="Assign Sub Teacher">Assign</button>
                    @endif
                </div>
            </td>
        </tr>
    @empty
        <tr><td colspan="5" style="text-align:center;padding:28px;color:#6b7280;">No Sub Teacher accounts found.</td></tr>
    @endforelse
</tbody>
@if($subTeachers->hasPages())
    @php
        $current = $subTeachers->currentPage();
        $last = $subTeachers->lastPage();
        $window = 5;
        $end = min($last, max($current + 2, $window));
        $start = max(1, min($current - 2, $end - $window + 1));
        $end = min($last, $start + $window - 1);
    @endphp
    <tfoot>
        <tr>
            <td colspan="5">
                <div class="pagination sta-pagination">
                    <span class="page-info">Showing {{ $subTeachers->firstItem() ?? 0 }} to {{ $subTeachers->lastItem() ?? 0 }} of {{ $subTeachers->total() }} entries</span>
                    <div class="page-btns">
                        <button type="button" class="page-btn sta-page" data-page="{{ $current - 1 }}" {{ $current <= 1 ? 'disabled' : '' }}>‹ Prev</button>
                        @if($start > 1)
                            <button type="button" class="page-btn sta-page" data-page="1">1</button>
                            @if($start > 2)<span class="page-ellipsis">…</span>@endif
                        @endif
                        @for($i = $start; $i <= $end; $i++)
                            <button type="button" class="page-btn sta-page {{ $i === $current ? 'active' : '' }}" data-page="{{ $i }}">{{ $i }}</button>
                        @endfor
                        @if($end < $last)
                            @if($end < $last - 1)<span class="page-ellipsis">…</span>@endif
                            <button type="button" class="page-btn sta-page" data-page="{{ $last }}">{{ $last }}</button>
                        @endif
                        <button type="button" class="page-btn sta-page" data-page="{{ $current + 1 }}" {{ $current >= $last ? 'disabled' : '' }}>Next ›</button>
                    </div>
                </div>
            </td>
        </tr>
    </tfoot>
@endif
</table>
