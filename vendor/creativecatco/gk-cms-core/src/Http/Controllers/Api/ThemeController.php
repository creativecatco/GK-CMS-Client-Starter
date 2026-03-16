<?php

namespace CreativeCatCo\GkCmsCore\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use CreativeCatCo\GkCmsCore\Models\Setting;

class ThemeController extends Controller
{
    public function save(Request $request)
    {
        $themeSettings = [
            'theme_primary_color',
            'theme_secondary_color',
            'theme_header_bg',
            'theme_footer_bg',
            'theme_font_heading',
            'theme_font_body',
            'show_tagline_header',
        ];

        foreach ($themeSettings as $key) {
            if ($request->has($key)) {
                Setting::set($key, $request->input($key), 'theme');
            }
        }

        // Handle logo: can be a file upload or a path string
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store(
                config('cms.media_upload_path', 'cms/media') . '/branding',
                'public'
            );
            Setting::set('logo', $path, 'general');
        } elseif ($request->has('logo')) {
            Setting::set('logo', $request->input('logo'), 'general');
        }

        // Flush cache so changes take effect immediately
        Setting::flushCache();

        return response()->json(['success' => true]);
    }

    public function get()
    {
        $logo = Setting::get('logo', '');
        $logoUrl = '';
        if ($logo) {
            if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
                $logoUrl = $logo;
            } else {
                $logoUrl = asset('storage/' . $logo);
            }
        }

        return response()->json([
            'theme_primary_color' => Setting::get('theme_primary_color', '#cfff2e'),
            'theme_secondary_color' => Setting::get('theme_secondary_color', '#293726'),
            'theme_header_bg' => Setting::get('theme_header_bg', '#15171e'),
            'theme_footer_bg' => Setting::get('theme_footer_bg', '#15171e'),
            'theme_font_heading' => Setting::get('theme_font_heading', 'Inter'),
            'theme_font_body' => Setting::get('theme_font_body', 'Inter'),
            'logo' => $logo,
            'logo_url' => $logoUrl,
            'show_tagline_header' => (bool) Setting::get('show_tagline_header', true),
        ]);
    }
}
