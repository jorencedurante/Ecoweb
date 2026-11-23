<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\BottleCollection;
use App\Models\User;
use App\Models\CertificateAward;
use App\Models\StudentClaim;
use App\Models\ClaimItem;
use App\Services\CollaborationService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $studentsQuery = Student::where('status', '!=', 'Archived');
        $bottlesQuery = BottleCollection::query();
        $awardsQuery = CertificateAward::query();
        $claimsQuery = StudentClaim::query();

        $teacherId = Auth::user()->scopeTeacherId();

        if ($teacherId !== null) {
            $studentsQuery->whereHas('enrollments', function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId)->where('status', 'active');
            });
            $bottlesQuery->whereHas('student.enrollments', function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId)->where('status', 'active');
            })->whereHas('student', function ($q) {
                $q->where('status', '!=', 'Archived');
            });
            $awardsQuery->whereHas('student.enrollments', function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId)->where('status', 'active');
            })->whereHas('student', function ($q) {
                $q->where('status', '!=', 'Archived');
            });
            $claimsQuery->whereHas('student.enrollments', function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId)->where('status', 'active');
            })->whereHas('student', function ($q) {
                $q->where('status', '!=', 'Archived');
            });
        }

        $totalStudents = (clone $studentsQuery)->count();
        $totalBottles = (clone $bottlesQuery)->sum('bottle_count');
        $totalTeachers = Auth::user()->isAdminLevel()
            ? User::where('role', 'teacher')->where('status', 'active')->count()
            : 0;
        $totalAdmins = Auth::user()->isSuperAdmin()
            ? User::where('role', 'admin')->where('status', 'active')->count()
            : 0;
        $totalAwards = (clone $awardsQuery)->count();
        $totalClaimedItems = (clone $claimsQuery)->count();
        $totalPointsRedeemed = (clone $claimsQuery)->sum('points_deducted');
        $availableClaimItems = ClaimItem::where('status', 'Available')->count();
        $femaleCount = (clone $studentsQuery)->where('gender', 'Female')->count();
        $maleCount = (clone $studentsQuery)->where('gender', 'Male')->count();

        // Weekly collection data (last 7 days)
        $weeklyBaseQuery = BottleCollection::selectRaw('DAYNAME(collection_date) as day, SUM(bottle_count) as total')
            ->where('collection_date', '>=', now()->subDays(6));
        if ($teacherId !== null) {
            $weeklyBaseQuery->whereHas('student.enrollments', function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId)->where('status', 'active');
            })->whereHas('student', function ($q) {
                $q->where('status', '!=', 'Archived');
            });
        }
        $weeklyData = $weeklyBaseQuery
            ->groupBy('day', 'collection_date')
            ->orderBy('collection_date')
            ->get()
            ->keyBy('day');

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $collectionData = [];
        foreach ($days as $day) {
            $collectionData[$day] = $weeklyData[$day]->total ?? 0;
        }

        $user = Auth::user();
        $subTeacherInfo = null;
        $collaborators = [];

        if ($user->isSubTeacher()) {
            $assignment = $user->assignmentData();
            $subTeacherInfo = [
                'name' => $user->name,
                'role' => User::roleLabel($user->role),
                'assigned' => $assignment !== null,
                'teacher_name' => $assignment
                    ? (User::find($assignment['teacher_id'])->name ?? '—')
                    : null,
                'access_level' => $assignment
                    ? (CollaborationService::accessLevels()[$assignment['access_level']] ?? $assignment['access_level'])
                    : null,
                'permissions' => $assignment
                    ? CollaborationService::permissionsFor($assignment['access_level'], $assignment['permissions'])
                    : [],
            ];
        }

        if ($user->isTeacher()) {
            $collaborators = $user->subTeachers()
                ->get()
                ->filter(function ($subTeacher) {
                    return $subTeacher->hasEffectiveAssignment();
                })
                ->map(function ($subTeacher) {
                    $level = $subTeacher->sub_teacher_access_level ?: 'collaborator';

                    return [
                        'name' => $subTeacher->name,
                        'access_level' => CollaborationService::accessLevels()[$level] ?? ucfirst($level),
                    ];
                })
                ->values()
                ->all();
        }

        return view('pages.dashboard', compact(
            'totalStudents', 'totalBottles', 'totalTeachers', 'totalAdmins', 'totalAwards',
            'totalClaimedItems', 'totalPointsRedeemed', 'availableClaimItems',
            'femaleCount', 'maleCount', 'collectionData', 'subTeacherInfo', 'collaborators'
        ));
    }
}
