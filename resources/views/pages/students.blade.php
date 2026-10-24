@extends('layouts.admin')

@section('title', 'EcoCollect - Students')
@section('page-title', 'Students')
@section('page-subtitle', 'Manage student information')

@section('content')
    @php
        $filterable = [
            'search' => 'Search',
            'grade_level' => 'Grade',
            'gender' => 'Gender',
        ];
        $appliedFilters = collect($filterable)
            ->map(function ($label, $key) {
                return ['key' => $key, 'label' => $label, 'value' => request($key)];
            })
            ->filter(fn ($filter) => $filter['value'] !== null && $filter['value'] !== '')
            ->values();
        $actionsColClass = Auth::user()->role === 'admin' ? 'stu-actions-3' : 'stu-actions-5';

        // Field → tab map for the Add/Edit Student form; used to group
        // validation errors into the tab that contains the failing field.
        $studentFormTabs = [
            'basic' => ['first_name', 'middle_name', 'last_name', 'lrn', 'grade_level', 'section', 'school_year', 'birth_date', 'gender'],
            'address' => ['house_street', 'barangay', 'municipality_city', 'province', 'region'],
            'family' => ['father_name', 'mother_maiden_name', 'guardian_name', 'guardian_relationship', 'contact_number'],
            'other' => ['ip_ethnic_group', 'religion', 'nationality', 'learning_modality', 'remarks'],
        ];
        $tabErrorMessages = [];
        foreach ($studentFormTabs as $tabKey => $fields) {
            $tabErrorMessages[$tabKey] = [];
            foreach ($fields as $field) {
                if ($errors->has($field)) {
                    $tabErrorMessages[$tabKey][] = $errors->first($field);
                }
            }
        }
        $openModal = session('open_modal');
        $openModalStudentId = session('open_modal_student_id');
    @endphp

    <div class="students-page-wrapper">

    @if(Auth::user()->role === 'admin')
    <div role="status" class="stu-viewonly-notice">
        Admin account is view-only. Student import and record changes are restricted to Teachers and Super Admin.
    </div>
    @endif

    <div class="filter-card stu-filter-card">
        <button type="button" class="filter-header" onclick="this.classList.toggle('collapsed');this.nextElementSibling.classList.toggle('collapsed')" aria-expanded="true">
            <x-icon name="funnel" :size="16" />
            Filters
            <x-icon name="chevron-down" :size="15" class="stu-filter-chevron" />
        </button>
        <div class="filter-body">
            <form method="GET" action="{{ route('admin.students') }}" id="filterForm" class="stu-toolbar-form">
                <div class="stu-field stu-field-search">
                    <label for="stuSearchInput">Search students</label>
                    <div class="stu-search">
                        <span class="stu-search-icon" aria-hidden="true"><x-icon name="search" :size="16" /></span>
                        <input type="text" id="stuSearchInput" name="search" autocomplete="off"
                               placeholder="Search by name, LRN, or student ID..."
                               value="{{ request('search') }}"
                               aria-label="Search students by name, LRN, or student ID">
                        <button type="button" class="stu-search-clear" id="stuSearchClear" aria-label="Clear search" {{ request('search') ? '' : 'hidden' }}>
                            <x-icon name="x" :size="13" />
                        </button>
                    </div>
                </div>

                @if(!auth()->user()->isTeacher())
                <div class="stu-field stu-field-select">
                    <label for="stuGradeSelect">Grade level</label>
                    <select id="stuGradeSelect" name="grade_level" aria-label="Filter by grade level">
                        <option value="">All grades</option>
                        @foreach($gradeLevels ?? [] as $gl)
                            <option value="{{ $gl }}" {{ request('grade_level') == $gl ? 'selected' : '' }}>{{ $gl }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="stu-field stu-field-select">
                    <label for="stuGenderSelect">Gender</label>
                    <select id="stuGenderSelect" name="gender" aria-label="Filter by gender">
                        <option value="">All genders</option>
                        <option value="Male" {{ request('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ request('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>

                @if(auth()->user()->isTeacher())
                <p class="stu-toolbar-note">Showing students from your assigned class.</p>
                @endif
            </form>

            @if($appliedFilters->isNotEmpty())
            <div class="stu-chips" role="status" aria-label="Active filters">
                <span class="stu-chips-label">Active filters</span>
                @foreach($appliedFilters as $filter)
                    @php
                        $remaining = request()->query();
                        unset($remaining[$filter['key']], $remaining['page']);
                    @endphp
                    <a class="stu-chip" href="{{ route('admin.students', $remaining) }}" title="Remove {{ strtolower($filter['label']) }} filter">
                        <span class="stu-chip-key">{{ $filter['label'] }}</span>
                        <span class="stu-chip-value">{{ $filter['value'] }}</span>
                        <span class="stu-chip-x" aria-hidden="true"><x-icon name="x" :size="12" /></span>
                    </a>
                @endforeach
                <a class="stu-chip stu-chip-clear" href="{{ route('admin.students') }}">Clear all</a>
            </div>
            @endif
        </div>
    </div>

        <div class="table-container" id="studentsTableContainer">
            @include('partials.students-table', [
                'students' => $students,
                'appliedFilters' => $appliedFilters,
                'actionsColClass' => $actionsColClass,
            ])
        </div>

    </div>

    @if(session('import_result'))
    @php $result = session('import_result'); $type = $result['type'] ?? 'success'; @endphp
    <div class="import-result-card import-result-{{ $type }}" id="importResult" role="alert">
        <div class="import-result-header">
            <span>📋 Import Result</span>
            <button type="button" onclick="document.getElementById('importResult').remove()" style="background:none;border:none;font-size:18px;cursor:pointer;color:#6b7280;" aria-label="Close">×</button>
        </div>
        <div class="import-result-summary">
            @foreach(explode(' | ', $result['message']) as $line)
            <div>{{ $line }}</div>
            @endforeach
        </div>
        @if(count($result['errors']))
        <div class="import-result-errors">
            <strong>Skipped rows:</strong>
            <ul>
                @foreach($result['errors'] as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
    @endif

    {{-- Student Overview Modal --}}
    <div class="modal-overlay" id="studentOverviewModal" role="dialog" aria-modal="true" aria-labelledby="studentOverviewTitle" aria-hidden="true">
        <div class="modal-content student-overview-modal">
            <div class="modal-header so-header">
                <div class="so-student">
                    <span class="so-avatar" id="soAvatar" aria-hidden="true">ST</span>
                    <div class="so-heading">
                        <h3 id="studentOverviewTitle">Student overview</h3>
                        <p class="subtitle" id="studentOverviewSubtitle">Bottles, points, achievements, and certificates in one view.</p>
                        <div class="so-meta" id="studentOverviewMeta" hidden></div>
                    </div>
                </div>
                <button type="button" class="so-close" id="soCloseBtn" data-modal-close aria-label="Close student overview">
                    <x-icon name="x" :size="18" />
                </button>
            </div>

            <div class="so-tabs" role="tablist" aria-label="Student overview sections">
                <button type="button" class="so-tab active" role="tab" id="soTabOverview" aria-selected="true" aria-controls="soPanel" data-so-tab="overview">
                    <x-icon name="circle-user-round" :size="16" /> Overview
                </button>
                <button type="button" class="so-tab" role="tab" id="soTabCollections" aria-selected="false" aria-controls="soPanel" data-so-tab="collections" tabindex="-1">
                    <x-icon name="recycle" :size="16" /> Bottle Collections
                </button>
                <button type="button" class="so-tab" role="tab" id="soTabAchievements" aria-selected="false" aria-controls="soPanel" data-so-tab="achievements" tabindex="-1">
                    <x-icon name="trophy" :size="16" /> Achievements
                </button>
                <button type="button" class="so-tab" role="tab" id="soTabCertificates" aria-selected="false" aria-controls="soPanel" data-so-tab="certificates" tabindex="-1">
                    <x-icon name="award" :size="16" /> Certificates
                </button>
                <button type="button" class="so-tab" role="tab" id="soTabFullDetails" aria-selected="false" aria-controls="soPanel" data-so-tab="full-details" tabindex="-1">
                    <x-icon name="file-text" :size="16" /> Full Details
                </button>
            </div>

            <div class="modal-body so-body" id="soPanel" role="tabpanel" aria-labelledby="soTabOverview" tabindex="0">
                <div class="so-state" role="status">
                    <span class="so-spinner" aria-hidden="true"></span>
                    <p>Loading student details…</p>
                </div>
            </div>

            <div class="modal-footer so-footer">
                <button type="button" class="btn btn-danger" id="soCloseFooter" data-modal-close>Close</button>
            </div>
        </div>
    </div>

    {{-- Icon sources for content rendered by the overview modal script --}}
    <template id="tplIco-recycle"><x-icon name="recycle" :size="18" /></template>
    <template id="tplIco-star"><x-icon name="star" :size="18" /></template>
    <template id="tplIco-trophy"><x-icon name="trophy" :size="18" /></template>
    <template id="tplIco-award"><x-icon name="award" :size="18" /></template>
    <template id="tplIco-inbox"><x-icon name="inbox" :size="30" /></template>
    <template id="tplIco-circle-check"><x-icon name="circle-check" :size="18" /></template>
    <template id="tplIco-clock"><x-icon name="clock" :size="18" /></template>
    <template id="tplIco-download"><x-icon name="download" :size="16" /></template>
    <template id="tplIco-printer"><x-icon name="printer" :size="16" /></template>
    <template id="tplIco-circle-alert"><x-icon name="circle-alert" :size="30" /></template>

    @if(Auth::user()->role !== 'admin')
    <!-- Import Students Modal -->
    <div class="modal-overlay" id="importStudentModal">
        <div class="modal-content" style="max-width:520px;">
            <div class="modal-header">
                <h3>Import Students</h3>
                <p class="subtitle">Upload an Excel file (.xlsx) with student data.</p>
            </div>
            <form method="POST" action="{{ route('admin.students.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Excel File <span style="color:var(--red);">*</span></label>
                        <input type="file" name="file" accept=".xlsx,.xls" required aria-label="Upload CSV file">
                    </div>
                    <div class="form-group">
                        <label>Grade Level (fallback if not detected in file)</label>
                        <select name="grade_level">
                            <option value="">-- Auto-detect or select --</option>
                            <option value="Kindergarten">Kindergarten</option>
                            <option value="Grade 1">Grade 1</option>
                            <option value="Grade 2">Grade 2</option>
                            <option value="Grade 3">Grade 3</option>
                            <option value="Grade 4">Grade 4</option>
                            <option value="Grade 5">Grade 5</option>
                            <option value="Grade 6">Grade 6</option>
                        </select>
                    </div>
                    <p class="import-note">
                        Supports <strong>SF1 (School Form 1)</strong> files and simple Excel templates.
                        For SF1 files, the system auto-detects headers, parses student names (Last,First,Middle),
                        and reads the grade level from the file. Select a grade level above as fallback.
                        <br><br>
                        For simple templates, supported columns: <strong>LRN</strong>, <strong>Student Name</strong>,
                        <strong>Grade Level</strong>, <strong>Gender</strong>, <strong>Student ID</strong>.
                        Gender accepts M/F/Male/Female. Duplicate LRN rows are skipped.
                    </p>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('admin.students.import.template') }}" class="btn btn-outline btn-template">📄 Download Template</a>
                    <button type="button" class="btn btn-danger" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-success">Import</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal-overlay" id="addStudentModal">
        <div class="modal-content student-form-modal">
            <div class="modal-header">
                <div>
                    <h3>Add student</h3>
                    <p class="subtitle">Fill in the following information</p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.students.store') }}" class="modal-form" data-student-form>
                @csrf
                <div class="stu-form-tabs" role="tablist" aria-label="Student form sections">
                    <button type="button" class="stu-form-tab active" role="tab" id="addTabBasic" data-form-tab="basic" aria-selected="true" aria-controls="addPanelBasic">
                        <x-icon name="circle-user-round" :size="15" /> Basic Information
                    </button>
                    <button type="button" class="stu-form-tab" role="tab" id="addTabAddress" data-form-tab="address" aria-selected="false" aria-controls="addPanelAddress" tabindex="-1">
                        <x-icon name="map-pin" :size="15" /> Address
                    </button>
                    <button type="button" class="stu-form-tab" role="tab" id="addTabFamily" data-form-tab="family" aria-selected="false" aria-controls="addPanelFamily" tabindex="-1">
                        <x-icon name="heart-handshake" :size="15" /> Family &amp; Guardian
                    </button>
                    <button type="button" class="stu-form-tab" role="tab" id="addTabOther" data-form-tab="other" aria-selected="false" aria-controls="addPanelOther" tabindex="-1">
                        <x-icon name="file-text" :size="15" /> Other Information
                    </button>
                </div>
                <div class="modal-body">
                    {{-- Basic Information --}}
                    <div class="stu-form-panel active" role="tabpanel" id="addPanelBasic" data-form-panel="basic" aria-labelledby="addTabBasic">
                        @if(count($tabErrorMessages['basic']))
                        <div class="stu-form-errors" role="alert">
                            <ul>@foreach($tabErrorMessages['basic'] as $tabError)<li>{{ $tabError }}</li>@endforeach</ul>
                        </div>
                        @endif
                        <div class="stu-form-grid">
                            <div class="form-group">
                                <label for="addFirstName">First Name <span style="color:var(--red);">*</span></label>
                                <input type="text" id="addFirstName" name="first_name" placeholder="Enter first name" value="{{ old('first_name') }}" required aria-label="First name">
                            </div>
                            <div class="form-group">
                                <label for="addMiddleName">Middle Name <span class="optional">(Optional)</span></label>
                                <input type="text" id="addMiddleName" name="middle_name" placeholder="Enter middle name" value="{{ old('middle_name') }}" aria-label="Middle name">
                            </div>
                            <div class="form-group">
                                <label for="addLastName">Last Name <span style="color:var(--red);">*</span></label>
                                <input type="text" id="addLastName" name="last_name" placeholder="Enter last name" value="{{ old('last_name') }}" required aria-label="Last name">
                            </div>
                            <div class="form-group">
                                <label for="addLrn">Student LRN <span style="color:var(--red);">*</span></label>
                                <input type="text" id="addLrn" name="lrn" placeholder="Enter student LRN" value="{{ old('lrn') }}" required aria-label="Student's LRN">
                            </div>
                            <div class="form-group">
                                <label for="addGradeLevel">Grade Level <span style="color:var(--red);">*</span></label>
                                <select id="addGradeLevel" name="grade_level" required aria-label="Grade level">
                                    <option value="">Select grade</option>
                                    @foreach(['Kindergarten','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6'] as $g)
                                        <option value="{{ $g }}" {{ old('grade_level') == $g ? 'selected' : '' }}>{{ $g }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="addSection">Section <span class="optional">(Optional)</span></label>
                                <input type="text" id="addSection" name="section" placeholder="e.g. Rizal" value="{{ old('section') }}" aria-label="Section" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="addSchoolYear">School Year <span class="optional">(Optional)</span></label>
                                <input type="text" id="addSchoolYear" name="school_year" placeholder="2026-2027" value="{{ old('school_year') }}" aria-label="School year" maxlength="20">
                            </div>
                            <div class="form-group">
                                <label for="addBirthDate">Birth Date <span class="optional">(Optional)</span></label>
                                <input type="date" id="addBirthDate" name="birth_date" value="{{ old('birth_date') }}" aria-label="Birth date">
                            </div>
                            <div class="form-group form-span-2">
                                <label>Gender <span style="color:var(--red);">*</span></label>
                                <div class="radio-group">
                                    <label><input type="radio" name="gender" value="Male" aria-label="Male" {{ old('gender') == 'Male' ? 'checked' : '' }}> Male</label>
                                    <label><input type="radio" name="gender" value="Female" aria-label="Female" {{ old('gender') == 'Female' ? 'checked' : '' }}> Female</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Address --}}
                    <div class="stu-form-panel" role="tabpanel" id="addPanelAddress" data-form-panel="address" aria-labelledby="addTabAddress" hidden>
                        @if(count($tabErrorMessages['address']))
                        <div class="stu-form-errors" role="alert">
                            <ul>@foreach($tabErrorMessages['address'] as $tabError)<li>{{ $tabError }}</li>@endforeach</ul>
                        </div>
                        @endif
                        <div class="stu-form-grid">
                            <div class="form-group form-span-2">
                                <label for="addHouseStreet">House / Street <span class="optional">(Optional)</span></label>
                                <input type="text" id="addHouseStreet" name="house_street" placeholder="House no. / street" value="{{ old('house_street') }}" aria-label="House or street" maxlength="255">
                            </div>
                            <div class="form-group">
                                <label for="addBarangay">Barangay <span class="optional">(Optional)</span></label>
                                <input type="text" id="addBarangay" name="barangay" placeholder="Barangay" value="{{ old('barangay') }}" aria-label="Barangay" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="addMunicipality">Municipality / City <span class="optional">(Optional)</span></label>
                                <input type="text" id="addMunicipality" name="municipality_city" placeholder="Municipality or city" value="{{ old('municipality_city') }}" aria-label="Municipality or city" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="addProvince">Province <span class="optional">(Optional)</span></label>
                                <input type="text" id="addProvince" name="province" placeholder="Province" value="{{ old('province') }}" aria-label="Province" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="addRegion">Region <span class="optional">(Optional)</span></label>
                                <input type="text" id="addRegion" name="region" placeholder="Region" value="{{ old('region') }}" aria-label="Region" maxlength="100">
                            </div>
                        </div>
                    </div>

                    {{-- Family & Guardian --}}
                    <div class="stu-form-panel" role="tabpanel" id="addPanelFamily" data-form-panel="family" aria-labelledby="addTabFamily" hidden>
                        @if(count($tabErrorMessages['family']))
                        <div class="stu-form-errors" role="alert">
                            <ul>@foreach($tabErrorMessages['family'] as $tabError)<li>{{ $tabError }}</li>@endforeach</ul>
                        </div>
                        @endif
                        <div class="stu-form-grid">
                            <div class="form-group">
                                <label for="addFatherName">Father's Name <span class="optional">(Optional)</span></label>
                                <input type="text" id="addFatherName" name="father_name" placeholder="Father's full name" value="{{ old('father_name') }}" aria-label="Father's name" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="addMotherMaiden">Mother's Maiden Name <span class="optional">(Optional)</span></label>
                                <input type="text" id="addMotherMaiden" name="mother_maiden_name" placeholder="Mother's maiden name" value="{{ old('mother_maiden_name') }}" aria-label="Mother's maiden name" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="addGuardianName">Guardian Name <span class="optional">(Optional)</span></label>
                                <input type="text" id="addGuardianName" name="guardian_name" placeholder="Guardian's full name" value="{{ old('guardian_name') }}" aria-label="Guardian name" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="addGuardianRelationship">Guardian Relationship <span class="optional">(Optional)</span></label>
                                <input type="text" id="addGuardianRelationship" name="guardian_relationship" placeholder="e.g. Mother" value="{{ old('guardian_relationship') }}" aria-label="Guardian relationship" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="addContactNumber">Contact Number <span class="optional">(Optional)</span></label>
                                <input type="text" id="addContactNumber" name="contact_number" placeholder="e.g. 09171234567" value="{{ old('contact_number') }}" aria-label="Contact number" maxlength="50">
                            </div>
                        </div>
                    </div>

                    {{-- Other Information --}}
                    <div class="stu-form-panel" role="tabpanel" id="addPanelOther" data-form-panel="other" aria-labelledby="addTabOther" hidden>
                        @if(count($tabErrorMessages['other']))
                        <div class="stu-form-errors" role="alert">
                            <ul>@foreach($tabErrorMessages['other'] as $tabError)<li>{{ $tabError }}</li>@endforeach</ul>
                        </div>
                        @endif
                        <div class="stu-form-grid">
                            <div class="form-group">
                                <label for="addEthnicGroup">Ethnic Group <span class="optional">(Optional)</span></label>
                                <input type="text" id="addEthnicGroup" name="ip_ethnic_group" placeholder="Ethnic group" value="{{ old('ip_ethnic_group') }}" aria-label="Ethnic group" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="addReligion">Religion <span class="optional">(Optional)</span></label>
                                <input type="text" id="addReligion" name="religion" placeholder="Religion" value="{{ old('religion') }}" aria-label="Religion" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="addNationality">Nationality / Citizenship <span class="optional">(Optional)</span></label>
                                <input type="text" id="addNationality" name="nationality" placeholder="e.g. Filipino" value="{{ old('nationality') }}" aria-label="Nationality or citizenship" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="addLearningModality">Learning Modality <span class="optional">(Optional)</span></label>
                                <input type="text" id="addLearningModality" name="learning_modality" placeholder="e.g. Face-to-face" value="{{ old('learning_modality') }}" aria-label="Learning modality" maxlength="100">
                            </div>
                            <div class="form-group form-span-2">
                                <label for="addRemarks">Remarks <span class="optional">(Optional)</span></label>
                                <textarea id="addRemarks" name="remarks" rows="3" placeholder="Additional notes about the student..." aria-label="Remarks">{{ old('remarks') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-success">Save Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal-overlay" id="editStudentModal">
        <div class="modal-content student-form-modal">
            <div class="modal-header">
                <div>
                    <h3>Edit student</h3>
                    <p class="subtitle">Update the student's information</p>
                </div>
            </div>
            <form method="POST" action="" id="editStudentForm" class="modal-form" data-student-form>
                @csrf @method('PUT')
                <div class="stu-form-tabs" role="tablist" aria-label="Student form sections">
                    <button type="button" class="stu-form-tab active" role="tab" id="editTabBasic" data-form-tab="basic" aria-selected="true" aria-controls="editPanelBasic">
                        <x-icon name="circle-user-round" :size="15" /> Basic Information
                    </button>
                    <button type="button" class="stu-form-tab" role="tab" id="editTabAddress" data-form-tab="address" aria-selected="false" aria-controls="editPanelAddress" tabindex="-1">
                        <x-icon name="map-pin" :size="15" /> Address
                    </button>
                    <button type="button" class="stu-form-tab" role="tab" id="editTabFamily" data-form-tab="family" aria-selected="false" aria-controls="editPanelFamily" tabindex="-1">
                        <x-icon name="heart-handshake" :size="15" /> Family &amp; Guardian
                    </button>
                    <button type="button" class="stu-form-tab" role="tab" id="editTabOther" data-form-tab="other" aria-selected="false" aria-controls="editPanelOther" tabindex="-1">
                        <x-icon name="file-text" :size="15" /> Other Information
                    </button>
                </div>
                <div class="modal-body">
                    {{-- Basic Information --}}
                    <div class="stu-form-panel active" role="tabpanel" id="editPanelBasic" data-form-panel="basic" aria-labelledby="editTabBasic">
                        @if(count($tabErrorMessages['basic']))
                        <div class="stu-form-errors" role="alert">
                            <ul>@foreach($tabErrorMessages['basic'] as $tabError)<li>{{ $tabError }}</li>@endforeach</ul>
                        </div>
                        @endif
                        <div class="stu-form-grid">
                            <div class="form-group">
                                <label for="editFirstName">First Name <span style="color:var(--red);">*</span></label>
                                <input type="text" id="editFirstName" name="first_name" aria-label="First name" placeholder="Enter first name" required>
                            </div>
                            <div class="form-group">
                                <label for="editMiddleName">Middle Name <span class="optional">(Optional)</span></label>
                                <input type="text" id="editMiddleName" name="middle_name" aria-label="Middle name" placeholder="Enter middle name">
                            </div>
                            <div class="form-group">
                                <label for="editLastName">Last Name <span style="color:var(--red);">*</span></label>
                                <input type="text" id="editLastName" name="last_name" aria-label="Last name" placeholder="Enter last name" required>
                            </div>
                            <div class="form-group">
                                <label for="editLrn">Student LRN <span style="color:var(--red);">*</span></label>
                                <input type="text" id="editLrn" name="lrn" aria-label="LRN" placeholder="Enter student LRN" required>
                            </div>
                            <div class="form-group">
                                <label for="editGradeLevel">Grade Level <span style="color:var(--red);">*</span></label>
                                <select name="grade_level" id="editGradeLevel" aria-label="Grade level" required>
                                    @foreach(['Kindergarten','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6'] as $g)
                                        <option value="{{ $g }}">{{ $g }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="editSection">Section <span class="optional">(Optional)</span></label>
                                <input type="text" id="editSection" name="section" aria-label="Section" placeholder="e.g. Rizal" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="editSchoolYear">School Year <span class="optional">(Optional)</span></label>
                                <input type="text" id="editSchoolYear" name="school_year" aria-label="School year" placeholder="2026-2027" maxlength="20">
                            </div>
                            <div class="form-group">
                                <label for="editBirthDate">Birth Date <span class="optional">(Optional)</span></label>
                                <input type="date" id="editBirthDate" name="birth_date" aria-label="Birth date">
                            </div>
                            <div class="form-group form-span-2">
                                <label>Gender <span style="color:var(--red);">*</span></label>
                                <div class="radio-group">
                                    <label><input type="radio" name="gender" value="Male" id="editGenderMale" aria-label="Male"> Male</label>
                                    <label><input type="radio" name="gender" value="Female" id="editGenderFemale" aria-label="Female"> Female</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Address --}}
                    <div class="stu-form-panel" role="tabpanel" id="editPanelAddress" data-form-panel="address" aria-labelledby="editTabAddress" hidden>
                        @if(count($tabErrorMessages['address']))
                        <div class="stu-form-errors" role="alert">
                            <ul>@foreach($tabErrorMessages['address'] as $tabError)<li>{{ $tabError }}</li>@endforeach</ul>
                        </div>
                        @endif
                        <div class="stu-form-grid">
                            <div class="form-group form-span-2">
                                <label for="editHouseStreet">House / Street <span class="optional">(Optional)</span></label>
                                <input type="text" id="editHouseStreet" name="house_street" aria-label="House or street" placeholder="House no. / street" maxlength="255">
                            </div>
                            <div class="form-group">
                                <label for="editBarangay">Barangay <span class="optional">(Optional)</span></label>
                                <input type="text" id="editBarangay" name="barangay" aria-label="Barangay" placeholder="Barangay" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="editMunicipality">Municipality / City <span class="optional">(Optional)</span></label>
                                <input type="text" id="editMunicipality" name="municipality_city" aria-label="Municipality or city" placeholder="Municipality or city" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="editProvince">Province <span class="optional">(Optional)</span></label>
                                <input type="text" id="editProvince" name="province" aria-label="Province" placeholder="Province" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="editRegion">Region <span class="optional">(Optional)</span></label>
                                <input type="text" id="editRegion" name="region" aria-label="Region" placeholder="Region" maxlength="100">
                            </div>
                        </div>
                    </div>

                    {{-- Family & Guardian --}}
                    <div class="stu-form-panel" role="tabpanel" id="editPanelFamily" data-form-panel="family" aria-labelledby="editTabFamily" hidden>
                        @if(count($tabErrorMessages['family']))
                        <div class="stu-form-errors" role="alert">
                            <ul>@foreach($tabErrorMessages['family'] as $tabError)<li>{{ $tabError }}</li>@endforeach</ul>
                        </div>
                        @endif
                        <div class="stu-form-grid">
                            <div class="form-group">
                                <label for="editFatherName">Father's Name <span class="optional">(Optional)</span></label>
                                <input type="text" id="editFatherName" name="father_name" aria-label="Father's name" placeholder="Father's full name" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="editMotherMaiden">Mother's Maiden Name <span class="optional">(Optional)</span></label>
                                <input type="text" id="editMotherMaiden" name="mother_maiden_name" aria-label="Mother's maiden name" placeholder="Mother's maiden name" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="editGuardianName">Guardian Name <span class="optional">(Optional)</span></label>
                                <input type="text" id="editGuardianName" name="guardian_name" aria-label="Guardian name" placeholder="Guardian's full name" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="editGuardianRelationship">Guardian Relationship <span class="optional">(Optional)</span></label>
                                <input type="text" id="editGuardianRelationship" name="guardian_relationship" aria-label="Guardian relationship" placeholder="e.g. Mother" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="editContactNumber">Contact Number <span class="optional">(Optional)</span></label>
                                <input type="text" id="editContactNumber" name="contact_number" aria-label="Contact number" placeholder="e.g. 09171234567" maxlength="50">
                            </div>
                        </div>
                    </div>

                    {{-- Other Information --}}
                    <div class="stu-form-panel" role="tabpanel" id="editPanelOther" data-form-panel="other" aria-labelledby="editTabOther" hidden>
                        @if(count($tabErrorMessages['other']))
                        <div class="stu-form-errors" role="alert">
                            <ul>@foreach($tabErrorMessages['other'] as $tabError)<li>{{ $tabError }}</li>@endforeach</ul>
                        </div>
                        @endif
                        <div class="stu-form-grid">
                            <div class="form-group">
                                <label for="editEthnicGroup">Ethnic Group <span class="optional">(Optional)</span></label>
                                <input type="text" id="editEthnicGroup" name="ip_ethnic_group" aria-label="Ethnic group" placeholder="Ethnic group" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="editReligion">Religion <span class="optional">(Optional)</span></label>
                                <input type="text" id="editReligion" name="religion" aria-label="Religion" placeholder="Religion" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="editNationality">Nationality / Citizenship <span class="optional">(Optional)</span></label>
                                <input type="text" id="editNationality" name="nationality" aria-label="Nationality or citizenship" placeholder="e.g. Filipino" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="editLearningModality">Learning Modality <span class="optional">(Optional)</span></label>
                                <input type="text" id="editLearningModality" name="learning_modality" aria-label="Learning modality" placeholder="e.g. Face-to-face" maxlength="100">
                            </div>
                            <div class="form-group form-span-2">
                                <label for="editRemarks">Remarks <span class="optional">(Optional)</span></label>
                                <textarea id="editRemarks" name="remarks" rows="3" aria-label="Remarks" placeholder="Additional notes about the student..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-success">Update Student</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
<script>
window.studentFormOld = @json(old() ?: new stdClass());
window.studentFormRestore = {
    modal: @json($openModal),
    studentId: @json($openModalStudentId),
    errors: @json(array_keys($errors->toArray()))
};

document.addEventListener('DOMContentLoaded', () => {
    initStudentToolbar();
    initStudentFormTabs();
    initStudentOverview();

    const editModal = document.getElementById('editStudentModal');
    document.querySelectorAll('[data-modal-target="editStudentModal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            populateEditStudentForm(btn);
            if (editModal) editModal.classList.add('show');
        });
    });

    restoreStudentFormAfterErrors();
});

// ---- Add/Edit Student form tabs ----
// Panels are only shown/hidden, never re-rendered, so values entered on one
// tab are kept when the user switches to another tab.
function initStudentFormTabs() {
    document.querySelectorAll('[data-student-form]').forEach(form => {
        const tabs = Array.prototype.slice.call(form.querySelectorAll('.stu-form-tab'));
        const panels = Array.prototype.slice.call(form.querySelectorAll('.stu-form-panel'));

        const activate = (key) => {
            tabs.forEach(tab => {
                const on = tab.dataset.formTab === key;
                tab.classList.toggle('active', on);
                tab.setAttribute('aria-selected', on ? 'true' : 'false');
                tab.tabIndex = on ? 0 : -1;
            });
            panels.forEach(panel => {
                const on = panel.dataset.formPanel === key;
                panel.classList.toggle('active', on);
                panel.hidden = !on;
            });
        };

        tabs.forEach(tab => {
            tab.addEventListener('click', () => activate(tab.dataset.formTab));
            tab.addEventListener('keydown', (e) => {
                if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
                e.preventDefault();
                const index = tabs.indexOf(tab);
                const next = e.key === 'ArrowRight'
                    ? (index + 1) % tabs.length
                    : (index - 1 + tabs.length) % tabs.length;
                tabs[next].focus();
                activate(tabs[next].dataset.formTab);
            });
        });
    });
}

// Populate the Edit Student modal from the row button's data attributes.
// $studentFormOld (set after a failed validation) wins over the stored values
// so the user does not lose what they typed.
function populateEditStudentForm(btn) {
    const oldData = window.studentFormOld || {};
    const set = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.value = (value === null || value === undefined) ? '' : value;
    };

    set('editFirstName', 'first_name' in oldData ? oldData.first_name : btn.dataset.first);
    set('editMiddleName', 'middle_name' in oldData ? oldData.middle_name : btn.dataset.middle);
    set('editLastName', 'last_name' in oldData ? oldData.last_name : btn.dataset.last);
    set('editLrn', 'lrn' in oldData ? oldData.lrn : btn.dataset.lrn);
    set('editGradeLevel', 'grade_level' in oldData ? oldData.grade_level : btn.dataset.grade);
    set('editSection', 'section' in oldData ? oldData.section : btn.dataset.section);
    set('editSchoolYear', 'school_year' in oldData ? oldData.school_year : btn.dataset.schoolYear);
    set('editBirthDate', 'birth_date' in oldData ? oldData.birth_date : btn.dataset.birth);
    set('editHouseStreet', 'house_street' in oldData ? oldData.house_street : btn.dataset.house);
    set('editBarangay', 'barangay' in oldData ? oldData.barangay : btn.dataset.barangay);
    set('editMunicipality', 'municipality_city' in oldData ? oldData.municipality_city : btn.dataset.municipality);
    set('editProvince', 'province' in oldData ? oldData.province : btn.dataset.province);
    set('editRegion', 'region' in oldData ? oldData.region : btn.dataset.region);
    set('editFatherName', 'father_name' in oldData ? oldData.father_name : btn.dataset.father);
    set('editMotherMaiden', 'mother_maiden_name' in oldData ? oldData.mother_maiden_name : btn.dataset.mother);
    set('editGuardianName', 'guardian_name' in oldData ? oldData.guardian_name : btn.dataset.guardian);
    set('editGuardianRelationship', 'guardian_relationship' in oldData ? oldData.guardian_relationship : btn.dataset.guardianRel);
    set('editContactNumber', 'contact_number' in oldData ? oldData.contact_number : btn.dataset.contact);
    set('editEthnicGroup', 'ip_ethnic_group' in oldData ? oldData.ip_ethnic_group : btn.dataset.ethnic);
    set('editReligion', 'religion' in oldData ? oldData.religion : btn.dataset.religion);
    set('editNationality', 'nationality' in oldData ? oldData.nationality : btn.dataset.nationality);
    set('editLearningModality', 'learning_modality' in oldData ? oldData.learning_modality : btn.dataset.modality);
    set('editRemarks', 'remarks' in oldData ? oldData.remarks : btn.dataset.remarks);

    const gender = 'gender' in oldData ? oldData.gender : btn.dataset.gender;
    const male = document.getElementById('editGenderMale');
    const female = document.getElementById('editGenderFemale');
    if (male) male.checked = gender === 'Male';
    if (female) female.checked = gender === 'Female';

    const form = document.getElementById('editStudentForm');
    if (form) form.action = '{{ url('/admin/students') }}/' + btn.dataset.id;

    // Reset to the first tab; activateStudentFormTab() re-opens the failing tab.
    activateStudentFormTab(form, 'basic');
}

function activateStudentFormTab(form, key) {
    if (!form) return;
    form.querySelectorAll('.stu-form-tab').forEach(tab => {
        const on = tab.dataset.formTab === key;
        tab.classList.toggle('active', on);
        tab.setAttribute('aria-selected', on ? 'true' : 'false');
        tab.tabIndex = on ? 0 : -1;
    });
    form.querySelectorAll('.stu-form-panel').forEach(panel => {
        const on = panel.dataset.formPanel === key;
        panel.classList.toggle('active', on);
        panel.hidden = !on;
    });
}

// Field → tab map (mirrors the server-side grouping used for error boxes).
const studentFormFieldTabs = {
    first_name: 'basic', middle_name: 'basic', last_name: 'basic', lrn: 'basic',
    grade_level: 'basic', section: 'basic', school_year: 'basic', birth_date: 'basic', gender: 'basic',
    house_street: 'address', barangay: 'address', municipality_city: 'address', province: 'address', region: 'address',
    father_name: 'family', mother_maiden_name: 'family', guardian_name: 'family',
    guardian_relationship: 'family', contact_number: 'family',
    ip_ethnic_group: 'other', religion: 'other', nationality: 'other',
    learning_modality: 'other', remarks: 'other',
};

// After a failed save the page reloads with errors + old input. Re-open the
// modal the user was working in and jump to the tab with the first error.
function restoreStudentFormAfterErrors() {
    const restore = window.studentFormRestore;
    if (!restore || !restore.modal) return;

    const firstErrorField = (restore.errors || []).find(field => studentFormFieldTabs[field]);
    const tabKey = firstErrorField ? studentFormFieldTabs[firstErrorField] : 'basic';

    if (restore.modal === 'add') {
        const addModal = document.getElementById('addStudentModal');
        if (!addModal) return;
        addModal.classList.add('show');
        document.body.classList.add('modal-open');
        activateStudentFormTab(addModal.querySelector('form[data-student-form]'), tabKey);
        return;
    }

    if (restore.modal === 'edit') {
        const trigger = document.querySelector(
            '[data-modal-target="editStudentModal"][data-id="' + restore.studentId + '"]'
        );
        if (!trigger) return;
        trigger.click();
        const form = document.getElementById('editStudentForm');
        activateStudentFormTab(form, tabKey);
    }
}

function initStudentToolbar() {
    const form = document.getElementById('filterForm');
    const input = document.getElementById('stuSearchInput');
    const clearBtn = document.getElementById('stuSearchClear');
    const tableContainer = document.getElementById('studentsTableContainer');
    if (!form || !input || !tableContainer) return;

    let timer = null;
    let lastValue = input.value.trim();
    let pendingController = null;
    let currentSequence = 0;

    const fetchStudentsTable = (page = null) => {
        if (pendingController) pendingController.abort();
        currentSequence++;
        const seq = currentSequence;
        const controller = new AbortController();
        pendingController = controller;

        const formData = new FormData(form);
        const params = new URLSearchParams();
        for (const [key, value] of formData.entries()) {
            if (value !== '') params.append(key, value);
        }
        if (page !== null) params.set('page', page);
        const url = '{{ route('admin.students.filter') }}' + '?' + params.toString();

        tableContainer.classList.add('loading');

        fetch(url, {
            headers: {
                'Accept': 'text/html',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            signal: controller.signal
        })
        .then((response) => {
            if (!response.ok) throw new Error('Filter failed');
            return response.text();
        })
        .then((html) => {
            if (seq !== currentSequence) return;
            tableContainer.innerHTML = html;
            tableContainer.classList.remove('loading');
        })
        .catch((err) => {
            if (seq !== currentSequence) return;
            tableContainer.classList.remove('loading');
            if (err.name !== 'AbortError') console.error('Fetch error:', err);
        })
        .finally(() => {
            pendingController = null;
        });
    };

    const syncClear = () => {
        if (clearBtn) clearBtn.hidden = input.value.length === 0;
    };

    syncClear();

    const gradeSelect = document.getElementById('stuGradeSelect');
    const genderSelect = document.getElementById('stuGenderSelect');

    [gradeSelect, genderSelect].forEach(select => {
        if (select) {
            select.addEventListener('change', () => {
                if (timer) clearTimeout(timer);
                fetchStudentsTable();
            });
        }
    });

    input.addEventListener('input', () => {
        syncClear();
        if (timer) clearTimeout(timer);
        const value = input.value.trim();
        if (value === lastValue) return;
        timer = setTimeout(() => {
            timer = null;
            lastValue = value;
            fetchStudentsTable();
        }, 300);
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (timer) { clearTimeout(timer); timer = null; }
            lastValue = input.value.trim();
            fetchStudentsTable();
        }
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            if (timer) { clearTimeout(timer); timer = null; }
            input.value = '';
            lastValue = '';
            syncClear();
            fetchStudentsTable();
        });
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        if (timer) { clearTimeout(timer); timer = null; }
        lastValue = input.value.trim();
        fetchStudentsTable();
    });

    tableContainer.addEventListener('click', (e) => {
        const pageLink = e.target.closest('a.page-btn[data-page]');
        if (!pageLink) return;
        e.preventDefault();
        const page = pageLink.getAttribute('data-page');
        fetchStudentsTable(page);
    });
}

function initStudentOverview() {
    const modal = document.getElementById('studentOverviewModal');
    if (!modal) return;

    const panel = document.getElementById('soPanel');
    const subtitle = document.getElementById('studentOverviewSubtitle');
    const avatar = document.getElementById('soAvatar');
    const meta = document.getElementById('studentOverviewMeta');
    const tabs = Array.prototype.slice.call(modal.querySelectorAll('.so-tab'));

    const cache = {};
    let activeKey = null;
    let activeUrl = null;
    let activeTab = 'overview';
    let data = null;
    let lastTrigger = null;
    let seq = 0;
    let controller = null;

    const esc = (value) => String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

    const icon = (name, size) => {
        const tpl = document.getElementById('tplIco-' + name);
        if (!tpl || !tpl.content.firstElementChild) return '';
        const node = tpl.content.firstElementChild.cloneNode(true);
        if (size) { node.setAttribute('width', size); node.setAttribute('height', size); }
        return new XMLSerializer().serializeToString(node);
    };

    const emptyState = (message) =>
        '<div class="so-state">' +
            '<span class="so-state-icon" aria-hidden="true">' + icon('inbox', 30) + '</span>' +
            '<p>' + esc(message) + '</p>' +
        '</div>';

    function setTab(tab) {
        activeTab = tab;
        tabs.forEach((t) => {
            const isActive = t.dataset.soTab === tab;
            t.classList.toggle('active', isActive);
            t.setAttribute('aria-selected', isActive ? 'true' : 'false');
            t.tabIndex = isActive ? 0 : -1;
            if (isActive) panel.setAttribute('aria-labelledby', t.id);
        });
    }

    function renderHeader() {
        if (!data) return;
        const s = data.student;
        avatar.textContent = s.initials || 'ST';
        subtitle.textContent = s.full_name + (s.lrn ? ' • LRN ' + s.lrn : '');

        const statusKey = String(s.status || '').toLowerCase();
        const statusClass = ['active', 'archived', 'inactive'].indexOf(statusKey) >= 0 ? ' status-' + statusKey : '';
        const chips = [];
        if (s.grade_level) chips.push('<span class="so-meta-chip">' + esc(s.grade_level) + '</span>');
        if (s.status) chips.push('<span class="status-badge' + statusClass + '">' + esc(s.status) + '</span>');
        if (s.student_id) chips.push('<span class="so-meta-chip">' + esc(s.student_id) + '</span>');
        meta.innerHTML = chips.join('');
        meta.hidden = chips.length === 0;
    }

    function resetHeader() {
        avatar.textContent = '…';
        subtitle.textContent = 'Loading student details…';
        meta.innerHTML = '';
        meta.hidden = true;
    }

    function renderLoading() {
        resetHeader();
        panel.innerHTML = '<div class="so-state" role="status">' +
            '<span class="so-spinner" aria-hidden="true"></span>' +
            '<p>Loading student details…</p></div>';
    }

    function renderError() {
        avatar.textContent = '…';
        subtitle.textContent = 'Unable to load student details';
        meta.innerHTML = '';
        meta.hidden = true;
        panel.innerHTML = '<div class="so-state so-state-error">' +
            '<span class="so-state-icon" aria-hidden="true">' + icon('circle-alert', 30) + '</span>' +
            '<p>Unable to load student details.</p>' +
            '<button type="button" class="btn btn-outline" id="soRetryBtn">Try again</button></div>';
        const retry = document.getElementById('soRetryBtn');
        if (retry) retry.addEventListener('click', () => {
            delete cache[activeKey];
            data = null;
            renderLoading();
            load();
        });
    }

    function load() {
        if (controller) controller.abort();
        const mySeq = ++seq;
        controller = typeof AbortController !== 'undefined' ? new AbortController() : null;

        fetch(activeUrl, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            signal: controller ? controller.signal : undefined
        })
            .then((res) => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then((json) => {
                if (mySeq !== seq) return;
                cache[activeKey] = json;
                data = json;
                renderHeader();
                render();
            })
            .catch((err) => {
                if (err && err.name === 'AbortError') return;
                if (mySeq !== seq) return;
                data = null;
                renderError();
            });
    }

    function open(trigger) {
        activeKey = trigger.dataset.studentKey;
        activeUrl = trigger.dataset.overviewUrl;
        activeTab = trigger.dataset.tab || 'overview';
        lastTrigger = trigger;
        data = cache[activeKey] || null;

        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        document.documentElement.style.overflow = 'hidden';

        setTab(activeTab);

        if (data) {
            renderHeader();
            render();
        } else {
            renderLoading();
            load();
        }

        const closeBtn = document.getElementById('soCloseBtn');
        if (closeBtn) closeBtn.focus();
    }

    function close() {
        seq += 1;
        if (controller) { controller.abort(); controller = null; }
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        document.documentElement.style.overflow = '';
        if (lastTrigger && document.contains(lastTrigger)) lastTrigger.focus();
        lastTrigger = null;
        activeKey = null;
        activeUrl = null;
        data = null;
    }

    function soCard(iconName, value, label, note, tone) {
        return '<div class="so-card">' +
            '<span class="so-card-icon so-tone-' + tone + '" aria-hidden="true">' + icon(iconName, 18) + '</span>' +
            '<span class="so-card-text">' +
                '<span class="so-card-value">' + esc(value) + '</span>' +
                '<span class="so-card-label">' + esc(label) + '</span>' +
                '<span class="so-card-note">' + esc(note) + '</span>' +
            '</span></div>';
    }

    function collectionTable(rows, withRecorder) {
        let html = '<div class="so-table-wrap"><table class="so-table">' +
            '<thead><tr><th>Date</th><th>Time</th><th>Bottles</th><th>Points</th><th>Status</th>' +
            (withRecorder ? '<th>Recorded by</th>' : '') +
            '</tr></thead><tbody>';
        rows.forEach((c) => {
            html += '<tr><td>' + esc(c.collection_date) + '</td>' +
                '<td>' + esc(c.collection_time || '—') + '</td>' +
                '<td><strong>' + esc(c.bottle_count) + '</strong></td>' +
                '<td class="so-points">' + esc(c.points_earned) + '</td>' +
                '<td><span class="status-badge status-active">' + esc(c.status || 'Recorded') + '</span></td>' +
                (withRecorder ? '<td>' + esc(c.recorded_by) + '</td>' : '') +
                '</tr>';
        });
        return html + '</tbody></table></div>';
    }

    function progressList(items) {
        if (!items.length) return emptyState('No active achievement quests yet.');
        return '<div class="so-ach-list">' + items.map((item) => {
            const metric = item.metric === 'bottles' ? 'bottles' : 'points';
            return '<div class="so-ach-item' + (item.completed ? ' so-ach-done' : '') + '">' +
                '<div class="so-ach-top">' +
                    '<span class="so-ach-title">' + esc(item.title) + '</span>' +
                    '<span class="so-ach-value">' + esc(item.current) + ' / ' + esc(item.required) + ' ' + metric + '</span>' +
                '</div>' +
                '<div class="so-progress" role="progressbar" aria-valuenow="' + esc(item.progress) + '" aria-valuemin="0" aria-valuemax="100" aria-label="' + esc(item.title) + ' progress">' +
                    '<span class="so-progress-bar" style="width:' + esc(item.progress) + '%"></span>' +
                '</div>' +
                '<div class="so-ach-bottom">' +
                    '<span>' + esc(item.description || '') + '</span>' +
                    (item.completed
                        ? '<span class="so-ach-tag">Completed</span>'
                        : '<span>' + esc(item.progress) + '%</span>') +
                '</div>' +
            '</div>';
        }).join('') + '</div>';
    }

    function activityList(items) {
        if (!items || !items.length) return emptyState('No recent activity yet.');
        const ico = { collection: 'recycle', achievement: 'trophy', certificate: 'award' };
        return '<ul class="so-activity">' + items.map((it) =>
            '<li class="so-activity-item">' +
                '<span class="so-activity-icon" aria-hidden="true">' + icon(ico[it.type] || 'inbox', 16) + '</span>' +
                '<span class="so-activity-text">' +
                    '<strong>' + esc(it.label) + '</strong>' +
                    (it.detail ? '<span>' + esc(it.detail) + '</span>' : '') +
                '</span>' +
                '<span class="so-activity-date">' + esc(it.date) + '</span>' +
            '</li>'
        ).join('') + '</ul>';
    }

    function renderOverview(d) {
        const s = d.student;
        const sum = d.summary;
        const info = [
            ['Full name', s.full_name],
            ['Student ID', s.student_id],
            ['LRN', s.lrn],
            ['Grade level', s.grade_level],
            ['Gender', s.gender],
            ['Status', s.status],
            ['QR code', s.qr_code],
            ['Assigned teacher', s.teacher_name],
            ['Guardian', s.guardian_name]
        ].filter((pair) => pair[1]);

        let html = '<div class="so-cards">' +
            soCard('recycle', sum.total_bottles, 'Total Bottles', 'From bottle collections', 'blue') +
            soCard('star', sum.total_points, 'IoT Points', 'Synced from IoT scan', 'green') +
            soCard('trophy', sum.achievements, 'Achievements', 'Badges earned', 'amber') +
            soCard('award', sum.certificates, 'Certificates', 'Awards issued', 'purple') +
        '</div>';

        html += '<section class="so-block">' +
            '<div class="so-block-head"><h4 class="so-section-title">Student details</h4>' +
            '<button type="button" class="so-inline-link" data-so-goto="full-details">Full details</button></div>' +
            '<div class="so-info-grid">' +
            info.map((pair) =>
                '<div class="so-info-item">' +
                    '<span class="so-info-label">' + esc(pair[0]) + '</span>' +
                    '<span class="so-info-value">' + esc(pair[1]) + '</span>' +
                '</div>'
            ).join('') +
            '</div></section>';

        html += '<section class="so-block">' +
            '<div class="so-block-head">' +
                '<h4 class="so-section-title">Recent activity</h4>' +
                (d.collections.length ? '<button type="button" class="so-inline-link" data-so-goto="collections">View all collections</button>' : '') +
            '</div>' +
            activityList(d.recent_activity) +
        '</section>';

        html += '<section class="so-block">' +
            '<div class="so-block-head">' +
                '<h4 class="so-section-title">Achievement progress</h4>' +
                ((d.achievements.progress.length || d.achievements.earned.length)
                    ? '<button type="button" class="so-inline-link" data-so-goto="achievements">View details</button>' : '') +
            '</div>' +
            progressList(d.achievements.progress) +
        '</section>';

        return html;
    }

    function renderCollections(d) {
        if (!d.collections.length) return emptyState('No bottle collections recorded yet.');

        const total = (d.meta && d.meta.collections_total) || 0;
        const shown = (d.meta && d.meta.collections_returned) || d.collections.length;

        let html = collectionTable(d.collections, true);
        html += '<p class="so-note">' + (total > shown
            ? 'Showing the latest ' + shown + ' of ' + total + ' recorded collections.'
            : 'Showing all ' + shown + ' recorded collections.') + '</p>';
        html += '<p class="so-note">Bottle totals come from Scan Bottle records; points are calculated by the IoT system.</p>';
        return html;
    }

    function earnedList(earned) {
        if (!earned.length) return emptyState('No achievements earned yet.');

        return '<div class="so-earned-list">' + earned.map((a) => {
            const usesBottles = a.required_bottles > 0;
            const reqValue = usesBottles ? a.required_bottles : a.points_required;
            const reqType = usesBottles ? 'bottles' : 'points';
            const hasRequirement = a.required_bottles > 0 || a.points_required > 0;

            return '<div class="so-earned-item">' +
                '<span class="so-earned-icon" aria-hidden="true">' + icon('circle-check', 18) + '</span>' +
                '<div class="so-earned-body">' +
                    '<strong>' + esc(a.title) + '</strong>' +
                    (a.badge_name ? '<span class="so-earned-badge">' + esc(a.badge_name) + '</span>' : '') +
                    (hasRequirement ? '<span class="so-earned-req">' + esc(reqType) + ' • ' + esc(reqValue) + ' required</span>' : '') +
                '</div>' +
                '<div class="so-earned-right">' +
                    '<span class="status-badge status-active">Earned</span>' +
                    '<span class="so-earned-date">' + esc(a.awarded_date) + '</span>' +
                '</div>' +
            '</div>';
        }).join('') + '</div>';
    }

    function renderAchievements(d) {
        const earned = d.achievements.earned;
        const progress = d.achievements.progress;

        let html = '<section class="so-block">' +
            '<div class="so-block-head">' +
                '<h4 class="so-section-title">Earned badges</h4>' +
                '<span class="so-count">' + earned.length + '</span>' +
            '</div>' +
            earnedList(earned) +
        '</section>';

        html += '<section class="so-block">' +
            '<div class="so-block-head"><h4 class="so-section-title">Quest progress</h4></div>' +
            progressList(progress) +
        '</section>';

        return html;
    }

    function renderFullDetails(d) {
        const s = d.student;
        const sum = d.summary;
        const ap = d.achievement_progress || {};

        const section = (title, inner) =>
            '<section class="so-block">' +
                '<div class="so-block-head"><h4 class="so-section-title">' + esc(title) + '</h4></div>' +
                inner +
            '</section>';

        const grid = (pairs) => {
            const list = pairs.filter((pair) => pair[1] !== null && pair[1] !== undefined && pair[1] !== '');
            if (!list.length) return emptyState('No details recorded yet.');
            return '<div class="so-info-grid">' + list.map((pair) =>
                '<div class="so-info-item">' +
                    '<span class="so-info-label">' + esc(pair[0]) + '</span>' +
                    '<span class="so-info-value">' + esc(pair[1]) + '</span>' +
                '</div>'
            ).join('') + '</div>';
        };

        // Full profile sections always show every field; empty values render
        // as "N/A" instead of hiding the row. Valid values such as 0 are kept.
        const gridAll = (pairs) =>
            '<div class="so-info-grid">' + pairs.map((pair) => {
                const value = pair[1];
                const display = (value === null || value === undefined || value === '') ? 'N/A' : value;
                return '<div class="so-info-item">' +
                    '<span class="so-info-label">' + esc(pair[0]) + '</span>' +
                    '<span class="so-info-value">' + esc(display) + '</span>' +
                '</div>';
            }).join('') + '</div>';

        let html = section('Basic information', gridAll([
            ['Full name', s.full_name],
            ['Student ID', s.student_id],
            ['LRN', s.lrn],
            ['Grade Level', s.grade_level],
            ['Section', s.section],
            ['School Year', s.school_year],
            ['Gender', s.gender],
            ['Birth Date', s.birth_date],
            ['Age', s.age],
            ['Status', s.status],
            ['QR Code', s.qr_code],
            ['Assigned teacher', s.teacher_name],
            ['Mother tongue', s.mother_tongue]
        ]));

        html += section('Address', gridAll([
            ['House / Street', s.house_street],
            ['Barangay', s.barangay],
            ['Municipality / City', s.municipality_city],
            ['Province', s.province],
            ['Region', s.region]
        ]));

        html += section('Family & guardian', gridAll([
            ["Father's Name", s.father_name],
            ["Mother's Maiden Name", s.mother_maiden_name],
            ['Guardian Name', s.guardian_name],
            ['Guardian Relationship', s.guardian_relationship],
            ['Contact Number', s.contact_number]
        ]));

        html += section('Other information', gridAll([
            ['Ethnic Group', s.ip_ethnic_group],
            ['Religion', s.religion],
            ['Nationality / Citizenship', s.nationality],
            ['Learning Modality', s.learning_modality],
            ['Remarks', s.remarks]
        ]));

        html += section('Collection summary', '<div class="so-cards so-cards-2">' +
            soCard('recycle', sum.total_bottles, 'Total Bottles', 'Sum of Scan Bottle records', 'blue') +
            soCard('inbox', sum.collections_count, 'Collection Records', 'Records logged', 'amber') +
            soCard('star', sum.total_points, 'IoT Points', 'Stored total', 'green') +
            soCard('circle-check', sum.points_earned, 'Points Earned', 'Sum across collections', 'purple') +
        '</div>');

        const goal = ap.next_goal;
        let progressBlock = grid([
            ['Current bottles', ap.current_bottles],
            ['Next achievement goal', goal ? goal.title : (ap.quests_total ? 'All active quests completed' : null)],
            ['Required', goal ? goal.required + ' ' + goal.metric : null],
            ['Remaining', goal ? goal.remaining + ' ' + goal.metric : null],
            ['Progress', goal ? goal.progress + '%' : null]
        ]);
        if (goal) {
            progressBlock += '<div class="so-progress so-progress-lg" role="progressbar" aria-valuenow="' + esc(goal.progress) + '" aria-valuemin="0" aria-valuemax="100" aria-label="Next achievement progress">' +
                '<span class="so-progress-bar" style="width:' + esc(goal.progress) + '%"></span></div>';
        } else if (!ap.quests_total) {
            progressBlock += emptyState('No active achievement quests yet.');
        }
        html += section('Achievement progress', progressBlock);

        html += section('Achievement records',
            '<div class="so-subhead">Earned badges (' + d.achievements.earned.length + ')</div>' +
            earnedList(d.achievements.earned) +
            '<div class="so-subhead">Quest progress</div>' +
            progressList(d.achievements.progress));

        html += section('Certificate records',
            d.certificates.length ? certList(d.certificates) : emptyState('No certificates issued to this student yet.'));

        return html;
    }

    function certList(certs) {
        return '<div class="so-cert-list">' + certs.map((c) => {
            const statusKey = String(c.status || '').toLowerCase();
            const statusClass = ['active', 'archived', 'inactive'].indexOf(statusKey) >= 0 ? ' status-' + statusKey : '';
            const meta = [c.type, c.school_year, c.awarded_date ? 'Awarded ' + c.awarded_date : '']
                .filter(Boolean).map(esc).join(' • ');

            return '<div class="so-cert-card">' +
                '<div class="so-cert-body">' +
                    '<div class="so-cert-head">' +
                        '<strong>' + esc(c.title || 'Certificate') + '</strong>' +
                        (c.status ? '<span class="status-badge' + statusClass + '">' + esc(c.status) + '</span>' : '') +
                    '</div>' +
                    '<span class="so-cert-meta">' + meta + '</span>' +
                    (c.description ? '<p class="so-cert-desc">' + esc(c.description) + '</p>' : '') +
                '</div>' +
                '<div class="so-cert-actions">' +
                    (c.view_url
                        ? '<a class="action-icon-btn view" href="' + esc(c.view_url) + '" title="Download certificate" aria-label="Download certificate" target="_blank" rel="noopener">' + icon('download', 16) + '</a>'
                        : '') +
                    (c.print_url
                        ? '<a class="action-icon-btn awards" href="' + esc(c.print_url) + '" title="Print certificate" aria-label="Print certificate" target="_blank" rel="noopener">' + icon('printer', 16) + '</a>'
                        : '') +
                '</div>' +
            '</div>';
        }).join('') + '</div>';
    }

    function renderCertificates(d) {
        if (!d.certificates.length) return emptyState('No certificates issued to this student yet.');
        return certList(d.certificates);
    }

    function render() {
        if (!data) return;
        if (activeTab === 'collections') panel.innerHTML = renderCollections(data);
        else if (activeTab === 'achievements') panel.innerHTML = renderAchievements(data);
        else if (activeTab === 'certificates') panel.innerHTML = renderCertificates(data);
        else if (activeTab === 'full-details') panel.innerHTML = renderFullDetails(data);
        else panel.innerHTML = renderOverview(data);
    }

    const tableContainer = document.getElementById('studentsTableContainer');
    if (tableContainer) {
        tableContainer.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-overview-open]');
            if (btn) open(btn);
        });
    }

    document.getElementById('soCloseBtn').addEventListener('click', close);
    document.getElementById('soCloseFooter').addEventListener('click', close);

    modal.addEventListener('click', (e) => {
        if (e.target === modal) close();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('show')) close();
    });

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => {
            setTab(tab.dataset.soTab);
            render();
        });
        tab.addEventListener('keydown', (e) => {
            if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
            e.preventDefault();
            const next = e.key === 'ArrowRight'
                ? (index + 1) % tabs.length
                : (index - 1 + tabs.length) % tabs.length;
            tabs[next].focus();
            setTab(tabs[next].dataset.soTab);
            render();
        });
    });

    panel.addEventListener('click', (e) => {
        const goto = e.target.closest('[data-so-goto]');
        if (!goto) return;
        setTab(goto.dataset.soGoto);
        render();
    });
}
</script>
@endpush
