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
}
