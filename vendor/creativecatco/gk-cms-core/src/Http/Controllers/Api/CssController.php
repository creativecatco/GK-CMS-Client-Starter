<?php

namespace CreativeCatCo\GkCmsCore\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use CreativeCatCo\GkCmsCore\Models\Setting;
use CreativeCatCo\GkCmsCore\Models\Page;

class CssController extends Controller
{
    public function get(Request $request)
    {
        $globalCss = Setting::get('global_css', '');
        $pageCss = '';

        if ($request->has('page')) {
            $page = Page::where('slug', $request->page)->first();
            if ($page) {
                $pageCss = $page->custom_css ?? '';
            }
        }

        return response()->json([
            'global_css' => $globalCss,
            'page_css' => $pageCss,
        ]);
    }

    public function save(Request $request)
    {
        $request->validate([
            'global_css' => 'nullable|string',
            'page_css' => 'nullable|string',
            'page_slug' => 'nullable|string',
        ]);

        if ($request->has('global_css')) {
            Setting::set('global_css', $request->global_css ?? '', 'theme');
        }

        if ($request->page_slug && $request->has('page_css')) {
            $page = Page::where('slug', $request->page_slug)->first();
            if ($page) {
                $page->custom_css = $request->page_css;
                $page->save();
            }
        }

        return response()->json(['success' => true]);
    }
}
