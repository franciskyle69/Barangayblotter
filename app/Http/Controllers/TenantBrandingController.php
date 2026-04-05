<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class TenantBrandingController extends Controller
{
    public function edit(): Response
    {
        $tenant = app('current_tenant');

        return Inertia::render('Tenant/Branding', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'sidebar_label' => $tenant->sidebar_label ?: $tenant->slug ?: $tenant->name,
                'logo_url' => $tenant->logo_url,
                'logo_choice' => $this->resolveLogoChoice($tenant->getRawOriginal('logo_path')),
                'theme_preset' => $tenant->theme_preset,
                'theme_primary_color' => $tenant->theme_primary_color,
                'theme_bg_color' => $tenant->theme_bg_color,
                'theme_sidebar_color' => $tenant->theme_sidebar_color,
                'login_background_url' => $tenant->login_background_url,
                'login_background_opacity' => (float) ($tenant->login_background_opacity ?? 0.45),
                'login_background_blur' => (int) ($tenant->login_background_blur ?? 0),
            ],
            'logoOptions' => $this->logoOptions(),
            'themeOptions' => $this->themeOptions(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = app('current_tenant');
        $connection = config('tenancy.central_connection', 'central');

        if (!$this->brandingSchemaReady($connection)) {
            return back()->withErrors([
                'theme_preset' => 'Tenant branding theme fields are not available yet. Run the latest migrations before saving branding changes.',
            ]);
        }

        $validated = $request->validate([
            'sidebar_label' => ['nullable', 'string', 'max:100'],
            'logo_choice' => ['required', 'string', 'in:default,blue,green,amber,custom'],
            'logo_file' => ['nullable', 'image', 'max:2048'],
            'theme_preset' => ['required', 'string', 'in:default,ocean,forest,amber,dusk,custom'],
            'theme_primary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_bg_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_sidebar_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'login_background_file' => ['nullable', 'image', 'max:4096'],
            'remove_login_background' => ['nullable', 'boolean'],
            'login_background_opacity' => ['required', 'numeric', 'between:0,0.9'],
            'login_background_blur' => ['required', 'integer', 'between:0,20'],
        ]);

        $before = [
            'sidebar_label' => $tenant->sidebar_label,
            'logo_path' => $tenant->logo_path,
        ];

        $currentLogoPath = $tenant->getRawOriginal('logo_path');
        $currentBackgroundPath = $tenant->getRawOriginal('login_background_path');

        if ($validated['logo_choice'] !== 'custom') {
            if ($currentLogoPath && !str_starts_with($currentLogoPath, 'images/')) {
                Storage::disk('public')->delete($currentLogoPath);
            }

            $tenant->logo_path = $this->logoChoiceToPath($validated['logo_choice']);
        } elseif ($request->hasFile('logo_file')) {
            if ($currentLogoPath && !str_starts_with($currentLogoPath, 'images/')) {
                Storage::disk('public')->delete($currentLogoPath);
            }

            $tenant->logo_path = $request->file('logo_file')->store('tenant-branding/' . $tenant->id, 'public');
        } elseif ($currentLogoPath && !str_starts_with($currentLogoPath, 'images/')) {
            $tenant->logo_path = $currentLogoPath;
        } else {
            return back()->withErrors(['logo_file' => 'Please upload a custom logo file.']);
        }

        $themePreset = $validated['theme_preset'];
        $themeDefaults = $this->themePresetDefaults($themePreset);

        $tenant->theme_preset = $themePreset;
        $tenant->theme_primary_color = $themePreset === 'custom'
            ? ($validated['theme_primary_color'] ?? $themeDefaults['primary'])
            : $themeDefaults['primary'];
        $tenant->theme_bg_color = $themePreset === 'custom'
            ? ($validated['theme_bg_color'] ?? $themeDefaults['background'])
            : $themeDefaults['background'];
        $tenant->theme_sidebar_color = $this->deriveSidebarColor($tenant->theme_primary_color);

        if ($request->boolean('remove_login_background')) {
            if ($currentBackgroundPath && !str_starts_with($currentBackgroundPath, 'images/')) {
                Storage::disk('public')->delete($currentBackgroundPath);
            }

            $tenant->login_background_path = null;
        } elseif ($request->hasFile('login_background_file')) {
            if ($currentBackgroundPath && !str_starts_with($currentBackgroundPath, 'images/')) {
                Storage::disk('public')->delete($currentBackgroundPath);
            }

            $tenant->login_background_path = $request->file('login_background_file')->store('tenant-login-backgrounds/' . $tenant->id, 'public');
        }

        $tenant->login_background_opacity = round((float) $validated['login_background_opacity'], 2);
        $tenant->login_background_blur = (int) $validated['login_background_blur'];

        $tenant->sidebar_label = trim((string) ($validated['sidebar_label'] ?? '')) ?: $tenant->sidebar_label ?: $tenant->slug ?: $tenant->name;
        $tenant->save();

        return back()->with('success', 'Tenant branding updated successfully.');
    }

    private function logoOptions(): array
    {
        return [
            ['value' => 'default', 'label' => 'Default App Logo', 'preview' => '/images/logo.png'],
            ['value' => 'blue', 'label' => 'Blue Seal', 'preview' => '/images/logo-blue.svg'],
            ['value' => 'green', 'label' => 'Green Seal', 'preview' => '/images/logo-green.svg'],
            ['value' => 'amber', 'label' => 'Amber Seal', 'preview' => '/images/logo-amber.svg'],
            ['value' => 'custom', 'label' => 'Upload Custom Logo', 'preview' => '/images/logo.png'],
        ];
    }

    private function themeOptions(): array
    {
        return [
            ['value' => 'default', 'label' => 'Default Indigo'],
            ['value' => 'ocean', 'label' => 'Ocean Blue'],
            ['value' => 'forest', 'label' => 'Forest Green'],
            ['value' => 'amber', 'label' => 'Warm Amber'],
            ['value' => 'dusk', 'label' => 'Dusk Slate'],
            ['value' => 'custom', 'label' => 'Custom Colors'],
        ];
    }

    private function themePresetDefaults(string $preset): array
    {
        return match ($preset) {
            'ocean' => [
                'primary' => '#0ea5e9',
                'background' => '#eff6ff',
            ],
            'forest' => [
                'primary' => '#16a34a',
                'background' => '#f0fdf4',
            ],
            'amber' => [
                'primary' => '#f59e0b',
                'background' => '#fffbeb',
            ],
            'dusk' => [
                'primary' => '#8b5cf6',
                'background' => '#f8fafc',
            ],
            default => [
                'primary' => '#635bff',
                'background' => '#f8fafc',
            ],
        };
    }

    private function deriveSidebarColor(string $primaryColor): string
    {
        $hex = ltrim($primaryColor, '#');

        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return '#121621';
        }

        $red = hexdec(substr($hex, 0, 2));
        $green = hexdec(substr($hex, 2, 2));
        $blue = hexdec(substr($hex, 4, 2));

        $mixWithBlack = static function (int $channel): int {
            return max(0, (int) round($channel * 0.36));
        };

        return sprintf(
            '#%02x%02x%02x',
            $mixWithBlack($red),
            $mixWithBlack($green),
            $mixWithBlack($blue)
        );
    }

    private function logoChoiceToPath(string $choice): ?string
    {
        return match ($choice) {
            'blue' => 'images/logo-blue.svg',
            'green' => 'images/logo-green.svg',
            'amber' => 'images/logo-amber.svg',
            default => null,
        };
    }

    private function resolveLogoChoice(?string $logoPath): string
    {
        return match ($logoPath) {
            'images/logo-blue.svg' => 'blue',
            'images/logo-green.svg' => 'green',
            'images/logo-amber.svg' => 'amber',
            null, '' => 'default',
            default => 'custom',
        };
    }

    private function brandingSchemaReady(string $connection): bool
    {
        $requiredColumns = [
            'theme_preset',
            'theme_primary_color',
            'theme_bg_color',
            'theme_sidebar_color',
            'login_background_path',
            'login_background_opacity',
            'login_background_blur',
        ];

        foreach ($requiredColumns as $column) {
            if (!Schema::connection($connection)->hasColumn('tenants', $column)) {
                return false;
            }
        }

        return true;
    }
}
