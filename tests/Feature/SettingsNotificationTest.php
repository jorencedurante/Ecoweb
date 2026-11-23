<?php

namespace Tests\Feature;

use Tests\TestCase;

class SettingsNotificationTest extends TestCase
{
    public function test_settings_save_persists_and_flashes_one_eco_collect_success_message(): void
    {
        $principal = $this->createSuperAdmin();

        $response = $this->actingAs($principal)->post(route('admin.settings.general'), [
            'admin_name' => 'Persisted Principal',
            'school_organization' => 'Persisted School',
            'address' => 'Persisted Address',
        ]);

        $response->assertRedirect(route('admin.system-settings'))
            ->assertSessionHas('success', 'Settings saved successfully.');

        $this->assertDatabaseHas('system_settings', [
            'admin_name' => 'Persisted Principal',
            'school_organization' => 'Persisted School',
            'address' => 'Persisted Address',
        ]);

        // The settings page renders the saved values after a refresh.
        $page = $this->actingAs($principal)->get(route('admin.system-settings'));
        $page->assertOk()
            ->assertSee('Persisted Principal')
            ->assertSee('Persisted School')
            ->assertSee('Persisted Address');

        // The shared alerts partial turns the single flash into one EcoToast.
        $this->assertStringContainsString('ecFlashMessages', (string) $page->getContent());
        $this->assertSame(1, substr_count((string) $page->getContent(), 'Settings saved successfully.'));
    }

    public function test_native_settings_placeholder_alert_is_removed(): void
    {
        $source = (string) file_get_contents(public_path('js/app.js'));

        $this->assertStringNotContainsString('Settings saved! (Placeholder - no database connected)', $source);
        $this->assertStringNotContainsString('TODO: Connect form submission to backend', $source);
        $this->assertStringNotContainsString('alert(', $source);
    }

    public function test_invalid_settings_values_are_rejected_with_field_errors(): void
    {
        $principal = $this->createSuperAdmin();

        $response = $this->actingAs($principal)->from(route('admin.system-settings'))
            ->post(route('admin.settings.general'), [
                'admin_name' => '',
                'school_organization' => '',
                'address' => 'Somewhere',
            ]);

        $response->assertRedirect(route('admin.system-settings'))
            ->assertSessionHasErrors(['admin_name', 'school_organization'])
            ->assertSessionMissing('success');

        $this->assertDatabaseMissing('system_settings', ['admin_name' => '']);
    }

    public function test_non_principal_roles_have_view_only_settings_and_cannot_update(): void
    {
        $roles = [
            $this->createAdmin(),
            $this->createTeacher(),
            $this->createAccount(['role' => 'sub_teacher', 'status' => 'active']),
        ];

        foreach ($roles as $user) {
            $this->actingAs($user)->get(route('admin.system-settings'))
                ->assertOk()
                ->assertSee('View only. Only Principal can edit these settings.')
                ->assertSee('readonly')
                ->assertDontSee('id="settingsSaveBtn"', false);

            $this->actingAs($user)->post(route('admin.settings.general'), [
                'admin_name' => 'Hacked',
                'school_organization' => 'Hacked',
            ])->assertForbidden();
        }

        $this->assertDatabaseMissing('system_settings', ['admin_name' => 'Hacked']);
    }
}
