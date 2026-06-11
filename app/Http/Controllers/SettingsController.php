<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\NotificationSetting;
use App\Models\AdminActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::firstOrCreate(['id' => 1]);
        $notificationSettings = NotificationSetting::firstOrCreate(
            ['user_id' => Auth::id()],
            ['bottle_collection_reports' => true, 'system_alerts' => true, 'email_notifications' => true]
        );
        $user = Auth::user();

        return view('pages.settings', compact('settings', 'notificationSettings', 'user'));
    }

    public function updateGeneral(Request $request)
    {
        $validated = $request->validate([
            'admin_name' => 'required|string|max:100',
            'school_organization' => 'required|string|max:150',
            'address' => 'nullable|string',
        ]);

        SystemSetting::updateOrCreate(['id' => 1], $validated);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Updated Settings',
            'description' => 'Updated general system settings.',
            'module' => 'Settings',
        ]);

        return redirect()->route('admin.settings')->with('success', 'General settings saved successfully!');
    }

    public function updateNotifications(Request $request)
    {
        $validated = $request->validate([
            'bottle_collection_reports' => 'nullable|boolean',
            'system_alerts' => 'nullable|boolean',
        ]);

        NotificationSetting::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'bottle_collection_reports' => $request->boolean('bottle_collection_reports'),
                'system_alerts' => $request->boolean('system_alerts'),
            ]
        );

        return redirect()->route('admin.settings')->with('success', 'Notification settings saved successfully!');
    }

    public function updateSecurity(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8|confirmed',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Updated Security',
            'description' => 'Updated account security settings.',
            'module' => 'Settings',
        ]);

        return redirect()->route('admin.settings')->with('success', 'Security settings updated successfully!');
    }
}
