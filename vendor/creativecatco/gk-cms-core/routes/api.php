<?php

use Illuminate\Support\Facades\Route;
use CreativeCatCo\GkCmsCore\Http\Controllers\Api\PageFieldsController;
use CreativeCatCo\GkCmsCore\Http\Controllers\Api\ImageUploadController;
use CreativeCatCo\GkCmsCore\Http\Controllers\Api\VideoUploadController;
use CreativeCatCo\GkCmsCore\Http\Controllers\Api\CssController;
use CreativeCatCo\GkCmsCore\Http\Controllers\Api\ThemeController;
use CreativeCatCo\GkCmsCore\Http\Controllers\Api\UpdateController;
use CreativeCatCo\GkCmsCore\Http\Controllers\Api\AiChatController;

/*
|--------------------------------------------------------------------------
| CMS Core API Routes
|--------------------------------------------------------------------------
|
| These routes provide the API for the inline editor and AI chatbot
| to read and write page fields, templates, and upload images.
|
| Authentication: These routes use the 'web' middleware with auth check.
| For API token auth (AI chatbot), add a bearer token middleware.
|
*/

Route::prefix('api/cms')->middleware([
    'web',
    \CreativeCatCo\GkCmsCore\Http\Middleware\SecurityHeaders::class,
    \CreativeCatCo\GkCmsCore\Http\Middleware\RateLimitApi::class,
])->group(function () {

    // ─── PUBLIC (read-only) ───
    Route::get('pages', [PageFieldsController::class, 'listPages'])
        ->name('cms.api.pages.list');

    Route::get('pages/{slug}/fields', [PageFieldsController::class, 'index'])
        ->name('cms.api.pages.fields.index');

    Route::get('pages/{slug}/fields/{key}', [PageFieldsController::class, 'show'])
        ->name('cms.api.pages.fields.show');

    // ─── AUTHENTICATED (write) ───
    Route::middleware([
        'auth',
        \CreativeCatCo\GkCmsCore\Http\Middleware\SanitizeInput::class,
        \CreativeCatCo\GkCmsCore\Http\Middleware\ValidateFileUpload::class,
    ])->group(function () {

        // Update fields (merge)
        Route::patch('pages/{slug}/fields', [PageFieldsController::class, 'update'])
            ->name('cms.api.pages.fields.update');

        // Replace all fields
        Route::put('pages/{slug}/fields', [PageFieldsController::class, 'replace'])
            ->name('cms.api.pages.fields.replace');

        // Update custom template
        Route::put('pages/{slug}/template', [PageFieldsController::class, 'updateTemplate'])
            ->name('cms.api.pages.template.update');

        // Update field definitions
        Route::put('pages/{slug}/field-definitions', [PageFieldsController::class, 'updateFieldDefinitions'])
            ->name('cms.api.pages.field-definitions.update');

        // Create a new page
        Route::post('pages', [PageFieldsController::class, 'createPage'])
            ->name('cms.api.pages.create');

        // Image upload
        Route::post('upload-image', [ImageUploadController::class, 'store'])
            ->name('cms.api.upload-image');

        // Video upload
        Route::post('upload-video', [VideoUploadController::class, 'store'])
            ->name('cms.api.upload-video');

        // CSS editor
        Route::get('css', [CssController::class, 'get'])
            ->name('cms.api.css.get');
        Route::post('css', [CssController::class, 'save'])
            ->name('cms.api.css.save');

        // Theme settings
        Route::get('theme', [ThemeController::class, 'get'])
            ->name('cms.api.theme.get');
        Route::post('theme', [ThemeController::class, 'save'])
            ->name('cms.api.theme.save');
    });
});

/*
|--------------------------------------------------------------------------
| Admin Update Route
|--------------------------------------------------------------------------
|
| This route handles one-click CMS updates from the admin panel.
| It runs composer update, migrations, and cache clears via AJAX.
|
*/

Route::prefix('admin/api')->middleware(['web', 'auth'])->group(function () {
    // CMS Updates
    Route::get('cms-update-preflight', [UpdateController::class, 'preflight'])
        ->name('cms.admin.update.preflight');
    Route::post('cms-update', [UpdateController::class, 'apply'])
        ->name('cms.admin.update.apply');
    Route::get('cms-update-status', [UpdateController::class, 'status'])
        ->name('cms.admin.update.status');

    // AI Chat (SSE streaming)
    Route::post('ai-chat', [AiChatController::class, 'chat'])
        ->name('cms.admin.ai.chat');

    // AI File Upload (extract text from documents for context)
    Route::post('ai-upload', [AiChatController::class, 'uploadFile'])
        ->name('cms.admin.ai.upload');

    // AI Google Doc Import
    Route::post('ai-import-gdoc', [AiChatController::class, 'importGoogleDoc'])
        ->name('cms.admin.ai.import-gdoc');

    // AI Conversations
    Route::get('ai-conversations', [AiChatController::class, 'listConversations'])
        ->name('cms.admin.ai.conversations');
    Route::get('ai-conversations/{id}', [AiChatController::class, 'getConversation'])
        ->name('cms.admin.ai.conversation');
    Route::delete('ai-conversations/{id}', [AiChatController::class, 'deleteConversation'])
        ->name('cms.admin.ai.conversation.delete');

    // AI Action Undo
    Route::post('ai-actions/{id}/undo', [AiChatController::class, 'undoAction'])
        ->name('cms.admin.ai.action.undo');
});

/*
|--------------------------------------------------------------------------
| API Token Authentication (for AI Chatbot)
|--------------------------------------------------------------------------
|
| For the AI chatbot to access write endpoints without a browser session,
| add a bearer token middleware. Example usage in the host app:
|
| 1. Set CMS_API_TOKEN in .env
| 2. The CMS checks for Authorization: Bearer <token> header
|
*/

Route::prefix('api/cms')->middleware(['web'])->group(function () {
    Route::middleware([\CreativeCatCo\GkCmsCore\Http\Middleware\ApiTokenAuth::class])->group(function () {

        Route::patch('v1/pages/{slug}/fields', [PageFieldsController::class, 'update'])
            ->name('cms.api.v1.pages.fields.update');

        Route::put('v1/pages/{slug}/fields', [PageFieldsController::class, 'replace'])
            ->name('cms.api.v1.pages.fields.replace');

        Route::put('v1/pages/{slug}/template', [PageFieldsController::class, 'updateTemplate'])
            ->name('cms.api.v1.pages.template.update');

        Route::post('v1/pages', [PageFieldsController::class, 'createPage'])
            ->name('cms.api.v1.pages.create');

        Route::post('v1/upload-image', [ImageUploadController::class, 'store'])
            ->name('cms.api.v1.upload-image');

        Route::post('v1/upload-video', [VideoUploadController::class, 'store'])
            ->name('cms.api.v1.upload-video');
    });
});
