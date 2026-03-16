<?php

namespace CreativeCatCo\GkCmsCore\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use CreativeCatCo\GkCmsCore\Models\Setting;
use CreativeCatCo\GkCmsCore\Models\Page as CmsPage;

class CssEditorPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';

    protected static ?string $navigationGroup = 'Design';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'CSS Editor';

    protected static ?string $slug = 'css-editor';

    protected static string $view = 'cms-core::filament.pages.css-editor';

    public ?string $global_css = '';
    public ?string $page_id = null;
    public ?string $page_css = '';
    public ?string $theme_variables = '';

    public function mount(): void
    {
        $this->global_css = Setting::get('global_css', '');
        $this->theme_variables = $this->getThemeVariablesPreview();
    }

    protected function getThemeVariablesPreview(): string
    {
        $primary = Setting::get('theme_primary_color', '#cfff2e');
        $secondary = Setting::get('theme_secondary_color', '#293726');
        $fontHeading = Setting::get('theme_font_heading', 'Inter');
        $fontBody = Setting::get('theme_font_body', 'Inter');

        return ":root {\n  --primary-color: {$primary};\n  --secondary-color: {$secondary};\n  --font-heading: '{$fontHeading}', sans-serif;\n  --font-body: '{$fontBody}', sans-serif;\n}";
    }

    public function saveGlobalCss(): void
    {
        Setting::set('global_css', $this->global_css ?? '', 'theme');

        Notification::make()
            ->title('Global CSS saved')
            ->success()
            ->send();
    }

    public function loadPageCss(): void
    {
        if (empty($this->page_id)) {
            $this->page_css = '';
            return;
        }

        $page = CmsPage::find($this->page_id);
        $this->page_css = $page ? ($page->custom_css ?? '') : '';
    }

    public function savePageCss(): void
    {
        if (empty($this->page_id)) {
            Notification::make()
                ->title('Please select a page first')
                ->warning()
                ->send();
            return;
        }

        $page = CmsPage::find($this->page_id);
        if ($page) {
            $page->custom_css = $this->page_css;
            $page->save();

            Notification::make()
                ->title("CSS saved for \"{$page->title}\"")
                ->success()
                ->send();
        }
    }
}
