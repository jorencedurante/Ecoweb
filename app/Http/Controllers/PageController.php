<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function login()
    {
        return view('pages.login');
    }

    public function dashboard()
    {
        return view('pages.dashboard');
    }

    public function students()
    {
        return view('pages.students');
    }

    public function bottleCollection()
    {
        return view('pages.bottle-collection');
    }

    public function certificate()
    {
        return view('pages.certificate');
    }

    public function reports()
    {
        return view('pages.reports');
    }

    public function studentReport()
    {
        return view('pages.student-report');
    }

    public function bottleReport()
    {
        return view('pages.bottle-report');
    }

    public function teachers()
    {
        return view('pages.teachers');
    }

    public function settings()
    {
        return view('pages.settings');
    }

    public function qrcode()
    {
        return view('pages.qrcode');
    }

    public function studentsFiltered()
    {
        return view('pages.students-filtered');
    }

    public function adminActivities()
    {
        return view('pages.admin-activities');
    }

    /**
     * Mock student data
     * TODO: Replace with Student model database queries
     */
    private function getMockStudents()
    {
        return [
            [
                'id' => 'STU001', 'lrn' => '123456789012', 'firstName' => 'Kathleen',
                'middleName' => 'E.', 'lastName' => 'Tabadero', 'fullName' => 'Kathleen E. Tabadero',
                'gradeLevel' => 'Grade 6', 'gender' => 'Female', 'qrCode' => 'Q001',
                'totalPoints' => 43, 'bottlesCollected' => 40, 'status' => 'Active'
            ],
            [
                'id' => 'STU002', 'lrn' => '123456789013', 'firstName' => 'Joy',
                'middleName' => 'O.', 'lastName' => 'Tabadero', 'fullName' => 'Joy O. Tabadero',
                'gradeLevel' => 'Grade 5', 'gender' => 'Female', 'qrCode' => 'Q002',
                'totalPoints' => 38, 'bottlesCollected' => 35, 'status' => 'Active'
            ],
            [
                'id' => 'STU003', 'lrn' => '123456789014', 'firstName' => 'Jerence',
                'middleName' => 'C.', 'lastName' => 'Tabadero', 'fullName' => 'Jerence C. Tabadero',
                'gradeLevel' => 'Grade 4', 'gender' => 'Male', 'qrCode' => 'Q003',
                'totalPoints' => 50, 'bottlesCollected' => 48, 'status' => 'Active'
            ],
            [
                'id' => 'STU004', 'lrn' => '123456789015', 'firstName' => 'Patricia',
                'middleName' => 'R.', 'lastName' => 'Tabadero', 'fullName' => 'Patricia R. Tabadero',
                'gradeLevel' => 'Grade 3', 'gender' => 'Female', 'qrCode' => 'Q004',
                'totalPoints' => 32, 'bottlesCollected' => 30, 'status' => 'Active'
            ],
            [
                'id' => 'STU005', 'lrn' => '123456789016', 'firstName' => 'Denver',
                'middleName' => 'P.', 'lastName' => 'Tabadero', 'fullName' => 'Denver P. Tabadero',
                'gradeLevel' => 'Grade 2', 'gender' => 'Male', 'qrCode' => 'Q005',
                'totalPoints' => 45, 'bottlesCollected' => 42, 'status' => 'Active'
            ],
            [
                'id' => 'STU006', 'lrn' => '123456789017', 'firstName' => 'Karen',
                'middleName' => 'N.', 'lastName' => 'Tabadero', 'fullName' => 'Karen N. Tabadero',
                'gradeLevel' => 'Grade 6', 'gender' => 'Female', 'qrCode' => 'Q006',
                'totalPoints' => 40, 'bottlesCollected' => 38, 'status' => 'Active'
            ],
        ];
    }

    /**
     * Mock achievements data
     * TODO: Connect achievements to Achievement model
     */
    private function getMockAchievements()
    {
        return [
            [
                'title' => 'Top Collector of the Week',
                'description' => 'Awarded for collecting the most bottles in a single week.',
                'date' => '2025-01-06', 'badge' => '🥇', 'type' => 'weekly'
            ],
            [
                'title' => '100 Bottles Collected',
                'description' => 'Milestone award for reaching 100 total bottle collections.',
                'date' => '2024-12-15', 'badge' => '🏆', 'type' => 'milestone'
            ],
            [
                'title' => 'Consistent Recycler',
                'description' => 'Awarded for consistent daily bottle collection participation.',
                'date' => '2025-01-01', 'badge' => '♻️', 'type' => 'consistency'
            ],
            [
                'title' => 'Eco Champion',
                'description' => 'Highest honor for outstanding environmental leadership.',
                'date' => '2024-11-20', 'badge' => '🌟', 'type' => 'prestige'
            ],
        ];
    }

    /**
     * Mock awards data
     * TODO: Connect awards to Award or Certificate model
     */
    private function getMockAwards()
    {
        return [
            [
                'title' => 'Excellence in Waste Collection Award',
                'date' => '2025-01-10',
                'type' => 'Certificate of Excellence',
                'description' => 'Awarded for demonstrating outstanding commitment to environmental sustainability through active participation in the school waste collection program.'
            ],
            [
                'title' => 'Monthly Top Collector Award',
                'date' => '2024-12-01',
                'type' => 'Certificate of Achievement',
                'description' => 'Awarded to the student with the highest bottle collection count for the month of December.'
            ],
            [
                'title' => 'Participation Certificate',
                'date' => '2025-01-15',
                'type' => 'Certificate of Participation',
                'description' => 'Awarded for active participation in the EcoCollect school waste management program.'
            ],
        ];
    }

    public function studentInfo($id)
    {
        // TODO: Replace mock student data with database data
        $students = $this->getMockStudents();
        $student = collect($students)->firstWhere('id', $id);

        if (!$student) {
            abort(404, 'Student not found');
        }

        return view('pages.student-info', compact('student'));
    }

    public function studentAchievements($id)
    {
        // TODO: Replace mock achievements with Achievement model data
        $students = $this->getMockStudents();
        $student = collect($students)->firstWhere('id', $id);

        if (!$student) {
            abort(404, 'Student not found');
        }

        $achievements = $this->getMockAchievements();
        return view('pages.student-achievements', compact('student', 'achievements'));
    }

    public function studentAwards($id)
    {
        // TODO: Connect awards to Award or Certificate model
        $students = $this->getMockStudents();
        $student = collect($students)->firstWhere('id', $id);

        if (!$student) {
            abort(404, 'Student not found');
        }

        $awards = $this->getMockAwards();
        return view('pages.student-awards', compact('student', 'awards'));
    }
}
