{{--
    Inline Editor Component v3
    
    Supports: text, textarea, richtext, image, gallery, button, button_group,
              section_bg, color, video, icon, repeater
    
    New in v3:
    - Universal light toolbar (bold, italic, link) on text/textarea fields
    - Inline color picker field type
    - Video/embed field type with upload and platform detection
    - Icon picker field type with searchable icon library
    - Repeater field type with add/remove and sub-field support
    
    Only loaded for authenticated admin/editor users.
--}}

@auth
    @if(auth()->user()->role === 'admin' || auth()->user()->role === 'editor')
        <style>
            /* ─── Editor Bar ─── */
            .gk-inline-editor-bar {
                position: fixed; top: 0; left: 0; right: 0; z-index: 9999;
                background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
                color: #f8fafc; padding: 8px 20px;
                display: flex; align-items: center; justify-content: space-between;
                font-family: 'Inter', system-ui, sans-serif; font-size: 13px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2); transition: transform 0.3s ease;
            }
            .gk-inline-editor-bar.gk-hidden { transform: translateY(-100%); }
            .gk-inline-editor-bar .gk-bar-left { display: flex; align-items: center; gap: 12px; }
            .gk-inline-editor-bar .gk-bar-right { display: flex; align-items: center; gap: 8px; }
            .gk-inline-editor-bar .gk-badge {
                background: #3b82f6; color: white; padding: 2px 10px;
                border-radius: 9999px; font-weight: 600; font-size: 11px;
                text-transform: uppercase; letter-spacing: 0.5px;
            }
            .gk-inline-editor-bar button {
                padding: 5px 14px; border-radius: 6px; border: none;
                cursor: pointer; font-size: 12px; font-weight: 500; transition: all 0.15s ease;
            }
            .gk-btn-save { background: #22c55e; color: white; }
            .gk-btn-save:hover { background: #16a34a; }
            .gk-btn-save:disabled { background: #6b7280; cursor: not-allowed; }
            .gk-btn-cancel { background: #ef4444; color: white; }
            .gk-btn-cancel:hover { background: #dc2626; }
            .gk-btn-toggle { background: transparent; color: #94a3b8; border: 1px solid #475569; }
            .gk-btn-toggle:hover { color: white; border-color: #64748b; }
            .gk-btn-toggle.active { background: #3b82f6; color: white; border-color: #3b82f6; }
            .gk-btn-admin {
                background: transparent; color: #94a3b8; border: 1px solid #475569;
                text-decoration: none; display: inline-flex; align-items: center;
                gap: 4px; padding: 5px 14px; border-radius: 6px; font-size: 12px;
            }
            .gk-btn-admin:hover { color: white; border-color: #64748b; }

            /* ─── Editable Field Highlights ─── */
            [data-field].gk-editable-active {
                outline: 2px dashed #3b82f6; outline-offset: 4px;
                cursor: pointer; position: relative; transition: outline-color 0.15s ease;
            }
            [data-field].gk-editable-active:hover { outline-color: #2563eb; outline-style: solid; }
            [data-field].gk-editable-active::before {
                content: attr(data-field-label);
                position: absolute; top: -24px; left: 0;
                background: #3b82f6; color: white; padding: 2px 8px;
                border-radius: 4px; font-size: 11px; font-weight: 500;
                font-family: 'Inter', system-ui, sans-serif;
                white-space: nowrap; z-index: 100; pointer-events: none;
                opacity: 0; transition: opacity 0.15s ease;
            }
            [data-field].gk-editable-active:hover::before { opacity: 1; }
            [data-field].gk-editing { outline: 2px solid #22c55e; outline-offset: 4px; min-height: 1em; }
            [data-field].gk-field-changed { outline-color: #f59e0b; }

            /* ─── Section Background Highlight ─── */
            [data-section-bg].gk-editable-active {
                outline: 2px dashed #8b5cf6; outline-offset: -2px; position: relative;
            }
            [data-section-bg].gk-editable-active::after { content: none; }

            /* ─── Section Background Edit Badge ─── */
            .gk-bg-badge {
                position: absolute; top: 8px; right: 8px;
                background: rgba(139,92,246,0.95); color: white;
                border: 2px solid white; border-radius: 8px; padding: 6px 12px;
                font-size: 12px; font-weight: 600;
                font-family: 'Inter', system-ui, sans-serif;
                cursor: pointer; z-index: 100;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                display: flex; align-items: center; gap: 4px;
                transition: background 0.15s ease, transform 0.15s ease;
                user-select: none; white-space: nowrap;
            }
            .gk-bg-badge:hover { background: rgba(109,62,216,1); transform: scale(1.05); }

            /* ─── Image Overlay (hover) ─── */
            .gk-image-overlay {
                position: absolute; inset: 0;
                background: rgba(0,0,0,0.5);
                display: flex; align-items: center; justify-content: center;
                opacity: 0; transition: opacity 0.15s ease;
                cursor: pointer; border-radius: inherit; z-index: 60; pointer-events: none;
            }
            .gk-image-wrapper:hover .gk-image-overlay { opacity: 1; }
            .gk-image-overlay span {
                background: white; color: #1e293b; padding: 6px 16px;
                border-radius: 6px; font-size: 13px; font-weight: 600;
            }

            /* ─── Image Edit Badge ─── */
            .gk-image-badge {
                position: absolute; top: 8px; right: 8px;
                background: rgba(59,130,246,0.95); color: white;
                border: 2px solid white; border-radius: 8px; padding: 6px 12px;
                font-size: 12px; font-weight: 600;
                font-family: 'Inter', system-ui, sans-serif;
                cursor: pointer; z-index: 70;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                display: flex; align-items: center; gap: 4px;
                transition: background 0.15s ease, transform 0.15s ease;
                user-select: none;
            }
            .gk-image-badge:hover { background: rgba(37,99,235,1); transform: scale(1.05); }
            .gk-image-wrapper { cursor: pointer; }

            /* ─── Gallery Edit Controls ─── */
            .gk-gallery-item-wrapper { position: relative; display: inline-block; }
            .gk-gallery-item-badge {
                position: absolute; top: 4px; right: 4px;
                background: rgba(59,130,246,0.95); color: white;
                border: 2px solid white; border-radius: 6px; padding: 4px 8px;
                font-size: 11px; font-weight: 600;
                font-family: 'Inter', system-ui, sans-serif;
                cursor: pointer; z-index: 70;
                box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                display: flex; align-items: center; gap: 3px;
                transition: background 0.15s ease; user-select: none;
            }
            .gk-gallery-item-badge:hover { background: rgba(37,99,235,1); }
            .gk-gallery-remove-badge {
                position: absolute; top: 4px; left: 4px;
                background: rgba(239,68,68,0.95); color: white;
                border: 2px solid white; border-radius: 6px; padding: 4px 8px;
                font-size: 11px; font-weight: 600;
                font-family: 'Inter', system-ui, sans-serif;
                cursor: pointer; z-index: 70;
                box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                transition: background 0.15s ease; user-select: none;
            }
            .gk-gallery-remove-badge:hover { background: rgba(220,38,38,1); }
            .gk-gallery-add-btn {
                display: flex; align-items: center; justify-content: center; gap: 6px;
                padding: 12px 20px; border: 2px dashed #94a3b8; border-radius: 8px;
                background: transparent; color: #94a3b8; cursor: pointer;
                font-size: 13px; font-weight: 600;
                font-family: 'Inter', system-ui, sans-serif;
                transition: border-color 0.15s, color 0.15s; min-height: 100px;
            }
            .gk-gallery-add-btn:hover { border-color: #3b82f6; color: #3b82f6; }

            /* ─── Button Edit Overlay ─── */
            [data-field-type="button"].gk-editable-active,
            [data-field-type="button_group"] [data-button-index].gk-editable-active {
                outline: 2px dashed #f59e0b; outline-offset: 2px;
            }

            /* ─── Light Toolbar (text/textarea) ─── */
            .gk-light-toolbar {
                position: fixed; z-index: 10002;
                background: #1e293b; border-radius: 8px;
                box-shadow: 0 4px 16px rgba(0,0,0,0.3);
                display: flex; align-items: center; gap: 2px; padding: 4px 6px;
                animation: gk-popover-in 0.12s ease;
            }
            .gk-light-toolbar button {
                background: transparent; border: none; color: #cbd5e1;
                cursor: pointer; padding: 4px 8px; border-radius: 4px;
                font-size: 13px; font-weight: 700; line-height: 1;
                font-family: 'Inter', system-ui, sans-serif;
                transition: all 0.1s ease; display: flex; align-items: center; justify-content: center;
                min-width: 28px; min-height: 28px;
            }
            .gk-light-toolbar button:hover { background: #334155; color: white; }
            .gk-light-toolbar button.active { background: #3b82f6; color: white; }
            .gk-light-toolbar .gk-toolbar-sep {
                width: 1px; height: 20px; background: #475569; margin: 0 4px;
            }

            /* ─── Link Input (inline in toolbar) ─── */
            .gk-link-input-wrap {
                display: flex; align-items: center; gap: 4px; padding: 0 4px;
            }
            .gk-link-input-wrap input {
                background: #334155; border: 1px solid #475569; color: white;
                padding: 3px 8px; border-radius: 4px; font-size: 12px;
                width: 200px; outline: none;
            }
            .gk-link-input-wrap input:focus { border-color: #3b82f6; }
            .gk-link-input-wrap button {
                background: #22c55e; color: white; border: none; padding: 3px 10px;
                border-radius: 4px; font-size: 11px; cursor: pointer; font-weight: 600;
            }

            /* ─── Color Picker Field ─── */
            .gk-color-swatch-badge {
                position: absolute; top: -4px; right: -4px;
                width: 24px; height: 24px; border-radius: 50%;
                border: 3px solid white; cursor: pointer; z-index: 70;
                box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                transition: transform 0.15s ease;
            }
            .gk-color-swatch-badge:hover { transform: scale(1.2); }
            .gk-color-badge {
                position: absolute; top: 4px; right: 4px;
                background: rgba(59,130,246,0.95); color: white;
                border: 2px solid white; border-radius: 6px; padding: 4px 8px;
                font-size: 11px; font-weight: 600;
                font-family: 'Inter', system-ui, sans-serif;
                cursor: pointer; z-index: 70;
                box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                display: flex; align-items: center; gap: 3px;
                transition: background 0.15s ease; user-select: none;
            }
            .gk-color-badge:hover { background: rgba(37,99,235,1); }
            .gk-color-presets { display: flex; flex-wrap: wrap; gap: 6px; }
            .gk-color-preset {
                width: 28px; height: 28px; border-radius: 6px; cursor: pointer;
                border: 2px solid transparent; transition: all 0.15s;
            }
            .gk-color-preset:hover { transform: scale(1.15); }
            .gk-color-preset.selected { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.3); }

            /* ─── Video Field ─── */
            .gk-video-badge {
                position: absolute; top: 8px; right: 8px;
                background: rgba(239,68,68,0.95); color: white;
                border: 2px solid white; border-radius: 8px; padding: 6px 12px;
                font-size: 12px; font-weight: 600;
                font-family: 'Inter', system-ui, sans-serif;
                cursor: pointer; z-index: 70;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                display: flex; align-items: center; gap: 4px;
                transition: background 0.15s ease, transform 0.15s ease;
                user-select: none;
            }
            .gk-video-badge:hover { background: rgba(220,38,38,1); transform: scale(1.05); }
            .gk-video-preview {
                width: 100%; aspect-ratio: 16/9; border-radius: 8px;
                background: #1e293b; border: 1px solid #334155;
                overflow: hidden; margin-top: 8px;
            }
            .gk-video-preview iframe { width: 100%; height: 100%; border: none; }

            /* ─── Icon Picker ─── */
            .gk-icon-badge {
                position: absolute; top: -4px; right: -4px;
                background: rgba(139,92,246,0.95); color: white;
                border: 2px solid white; border-radius: 50%; width: 22px; height: 22px;
                font-size: 10px; font-weight: 700;
                cursor: pointer; z-index: 70;
                box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                display: flex; align-items: center; justify-content: center;
                transition: background 0.15s ease, transform 0.15s ease;
                user-select: none;
            }
            .gk-icon-badge:hover { background: rgba(109,62,216,1); transform: scale(1.15); }
            .gk-icon-grid {
                display: grid; grid-template-columns: repeat(6, 1fr); gap: 4px;
                max-height: 240px; overflow-y: auto; padding: 4px;
            }
            .gk-icon-option {
                width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;
                border-radius: 6px; cursor: pointer; border: 2px solid transparent;
                transition: all 0.1s; background: #f8fafc;
            }
            .gk-icon-option:hover { background: #e2e8f0; border-color: #94a3b8; }
            .gk-icon-option.selected { border-color: #3b82f6; background: #eff6ff; }
            .gk-icon-option svg { width: 20px; height: 20px; }

            /* ─── Repeater Field ─── */
            .gk-repeater-item-wrapper {
                position: relative; border: 2px dashed #94a3b8; border-radius: 8px;
                padding: 12px; margin-bottom: 8px; transition: border-color 0.15s;
            }
            .gk-repeater-item-wrapper:hover { border-color: #3b82f6; }
            .gk-repeater-item-controls {
                position: absolute; top: -12px; right: 8px;
                display: flex; gap: 4px; z-index: 70;
            }
            .gk-repeater-item-remove {
                background: rgba(239,68,68,0.95); color: white;
                border: 2px solid white; border-radius: 6px; padding: 2px 8px;
                font-size: 11px; font-weight: 600; cursor: pointer;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                transition: background 0.15s; user-select: none;
            }
            .gk-repeater-item-remove:hover { background: rgba(220,38,38,1); }
            .gk-repeater-item-label {
                position: absolute; top: -12px; left: 8px;
                background: #3b82f6; color: white; padding: 2px 10px;
                border-radius: 4px; font-size: 10px; font-weight: 600;
                font-family: 'Inter', system-ui, sans-serif;
                user-select: none;
            }
            .gk-repeater-add-btn {
                display: flex; align-items: center; justify-content: center; gap: 6px;
                padding: 10px 20px; border: 2px dashed #94a3b8; border-radius: 8px;
                background: transparent; color: #94a3b8; cursor: pointer;
                font-size: 13px; font-weight: 600;
                font-family: 'Inter', system-ui, sans-serif;
                transition: border-color 0.15s, color 0.15s; width: 100%;
            }
            .gk-repeater-add-btn:hover { border-color: #3b82f6; color: #3b82f6; }

            /* ─── Popover Panel ─── */
            .gk-popover {
                position: fixed; z-index: 10001;
                background: white; border-radius: 12px;
                box-shadow: 0 8px 30px rgba(0,0,0,0.2);
                font-family: 'Inter', system-ui, sans-serif;
                font-size: 13px; color: #1e293b;
                min-width: 320px; max-width: 400px;
                animation: gk-popover-in 0.15s ease;
            }
            @keyframes gk-popover-in { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
            .gk-popover-header {
                padding: 12px 16px; border-bottom: 1px solid #e2e8f0;
                display: flex; align-items: center; justify-content: space-between; font-weight: 600;
            }
            .gk-popover-header button {
                background: none; border: none; cursor: pointer;
                color: #94a3b8; font-size: 18px; line-height: 1;
            }
            .gk-popover-body { padding: 16px; display: flex; flex-direction: column; gap: 12px; }
            .gk-popover-footer {
                padding: 12px 16px; border-top: 1px solid #e2e8f0;
                display: flex; gap: 8px; justify-content: flex-end;
            }
            .gk-popover label { display: block; font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.3px; }
            .gk-popover input[type="text"], .gk-popover input[type="url"], .gk-popover input[type="number"],
            .gk-popover select, .gk-popover textarea {
                width: 100%; padding: 8px 10px; border: 1px solid #d1d5db;
                border-radius: 6px; font-size: 13px; font-family: inherit; transition: border-color 0.15s;
            }
            .gk-popover input:focus, .gk-popover select:focus, .gk-popover textarea:focus {
                outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.15);
            }
            .gk-popover .gk-color-row { display: flex; align-items: center; gap: 8px; }
            .gk-popover input[type="color"] { width: 40px; height: 36px; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer; padding: 2px; }
            .gk-popover .gk-btn-apply { background: #3b82f6; color: white; padding: 6px 16px; border-radius: 6px; border: none; cursor: pointer; font-size: 12px; font-weight: 600; }
            .gk-popover .gk-btn-apply:hover { background: #2563eb; }
            .gk-popover .gk-btn-remove { background: #ef4444; color: white; padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer; font-size: 12px; font-weight: 500; }
            .gk-popover .gk-btn-remove:hover { background: #dc2626; }
            .gk-popover .gk-btn-secondary { background: #f1f5f9; color: #475569; padding: 6px 12px; border-radius: 6px; border: 1px solid #e2e8f0; cursor: pointer; font-size: 12px; }

            /* ─── Background Overlay (front-end) ─── */
            .gk-section-overlay {
                position: absolute; inset: 0; pointer-events: none; z-index: 0;
                transition: background 0.3s ease;
            }
            [data-section-bg] { position: relative; }
            [data-section-bg] > *:not(.gk-section-overlay):not(.gk-bg-badge) { position: relative; z-index: 1; }

            /* ─── BG Editor Tabs ─── */
            .gk-bg-tabs { display: flex; border-bottom: 1px solid #e2e8f0; margin: -16px -16px 12px -16px; }
            .gk-bg-tabs button {
                flex: 1; padding: 10px 8px; background: none; border: none; border-bottom: 2px solid transparent;
                color: #94a3b8; font-size: 12px; font-weight: 600; cursor: pointer; text-transform: uppercase;
                letter-spacing: 0.3px; transition: all 0.15s;
            }
            .gk-bg-tabs button.active { color: #3b82f6; border-bottom-color: #3b82f6; }
            .gk-bg-tabs button:hover:not(.active) { color: #64748b; }
            .gk-bg-tab-content { display: none; }
            .gk-bg-tab-content.active { display: block; }

            /* ─── Color Type Toggle ─── */
            .gk-color-type-toggle { display: flex; gap: 4px; margin-bottom: 8px; }
            .gk-color-type-toggle button {
                flex: 1; padding: 6px 10px; border-radius: 6px; border: 1px solid #e2e8f0;
                background: #f8fafc; color: #64748b; font-size: 11px; font-weight: 600;
                cursor: pointer; transition: all 0.15s;
            }
            .gk-color-type-toggle button.active { background: #3b82f6; color: white; border-color: #3b82f6; }

            /* ─── RGBA Color Row ─── */
            .gk-rgba-row { display: flex; align-items: center; gap: 8px; }
            .gk-rgba-row input[type="color"] { width: 40px; height: 36px; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer; padding: 2px; flex-shrink: 0; }
            .gk-rgba-row input[type="text"] { flex: 1; }
            .gk-opacity-row { display: flex; align-items: center; gap: 8px; margin-top: 6px; }
            .gk-opacity-row label { margin: 0; min-width: 55px; font-size: 11px; }
            .gk-opacity-row input[type="range"] { flex: 1; height: 6px; accent-color: #3b82f6; }
            .gk-opacity-row span { font-size: 11px; color: #64748b; min-width: 35px; text-align: right; }

            /* ─── Gradient Controls ─── */
            .gk-gradient-controls { display: flex; flex-direction: column; gap: 10px; }
            .gk-gradient-type-row { display: flex; align-items: center; gap: 8px; }
            .gk-gradient-type-row select { flex: 1; }
            .gk-gradient-angle-row { display: flex; align-items: center; gap: 8px; }
            .gk-gradient-angle-row input[type="range"] { flex: 1; accent-color: #3b82f6; }
            .gk-gradient-angle-row span { font-size: 11px; color: #64748b; min-width: 30px; text-align: right; }
            .gk-gradient-preview {
                height: 24px; border-radius: 6px; border: 1px solid #e2e8f0; margin-top: 4px;
            }

            /* ─── Button Style Preview ─── */
            .gk-style-options { display: flex; gap: 6px; flex-wrap: wrap; }
            .gk-style-option {
                padding: 6px 14px; border-radius: 6px; cursor: pointer;
                font-size: 12px; font-weight: 500; border: 2px solid transparent; transition: all 0.15s;
            }
            .gk-style-option.selected { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.2); }

            /* ─── Toast ─── */
            .gk-toast {
                position: fixed; bottom: 20px; right: 20px; z-index: 10000;
                padding: 12px 20px; border-radius: 8px; color: white;
                font-size: 13px; font-weight: 500;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                transform: translateY(100px); opacity: 0; transition: all 0.3s ease;
            }
            .gk-toast.gk-show { transform: translateY(0); opacity: 1; }
            .gk-toast.gk-success { background: #22c55e; }
            .gk-toast.gk-error { background: #ef4444; }

            body.gk-editor-active { padding-top: 44px; }

            /* ─── CSS Sidebar ─── */
            .gk-css-sidebar {
                position: fixed; top: 44px; right: 0; bottom: 0; width: 420px;
                background: #1e293b; color: #e2e8f0; z-index: 9998;
                transform: translateX(100%); transition: transform 0.3s ease;
                display: flex; flex-direction: column;
                font-family: 'Inter', system-ui, sans-serif;
                box-shadow: -4px 0 20px rgba(0,0,0,0.3);
            }
            .gk-css-sidebar.gk-open { transform: translateX(0); }
            .gk-css-sidebar-header {
                padding: 12px 16px; border-bottom: 1px solid #334155;
                display: flex; align-items: center; justify-content: space-between;
                font-size: 14px; font-weight: 600;
            }
            .gk-css-sidebar-header button { background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 18px; }
            .gk-css-sidebar-tabs {
                display: flex; border-bottom: 1px solid #334155;
            }
            .gk-css-sidebar-tabs button {
                flex: 1; padding: 8px; background: none; border: none; color: #94a3b8;
                font-size: 12px; font-weight: 500; cursor: pointer; border-bottom: 2px solid transparent;
            }
            .gk-css-sidebar-tabs button.active { color: #3b82f6; border-bottom-color: #3b82f6; }
            .gk-css-sidebar textarea {
                flex: 1; width: 100%; padding: 16px; background: #0f172a; color: #a5f3fc;
                border: none; font-family: 'Fira Code', 'Consolas', monospace; font-size: 13px;
                line-height: 1.6; resize: none; outline: none;
            }
            .gk-css-sidebar-footer {
                padding: 10px 16px; border-top: 1px solid #334155;
                display: flex; gap: 8px; justify-content: flex-end;
            }

            /* ─── Theme Editor Panel ─── */
            .gk-theme-panel {
                position: fixed; top: 44px; right: 0; bottom: 0; width: 360px;
                background: white; z-index: 9998;
                transform: translateX(100%); transition: transform 0.3s ease;
                display: flex; flex-direction: column;
                font-family: 'Inter', system-ui, sans-serif;
                box-shadow: -4px 0 20px rgba(0,0,0,0.15);
                overflow-y: auto;
            }
            .gk-theme-panel.gk-open { transform: translateX(0); }
            .gk-theme-panel-header {
                padding: 14px 16px; border-bottom: 1px solid #e2e8f0;
                display: flex; align-items: center; justify-content: space-between;
                font-size: 14px; font-weight: 600; color: #1e293b;
                position: sticky; top: 0; background: white; z-index: 1;
            }
            .gk-theme-panel-header button { background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 18px; }
            .gk-theme-section { padding: 16px; border-bottom: 1px solid #f1f5f9; }
            .gk-theme-section h4 { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; }
            .gk-theme-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
            .gk-theme-row label { font-size: 13px; color: #475569; min-width: 100px; }
            .gk-theme-row input[type="color"] { width: 36px; height: 36px; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer; padding: 2px; }
            .gk-theme-row input[type="text"], .gk-theme-row select {
                flex: 1; padding: 6px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px;
            }
        </style>

        <div class="gk-inline-editor-bar" id="gk-editor-bar">
            <div class="gk-bar-left">
                <span class="gk-badge">Editor</span>
                <span id="gk-editor-status">Click "Edit" to start editing</span>
            </div>
            <div class="gk-bar-right">
                <a href="/admin" class="gk-btn-admin">Admin Panel</a>
                <button class="gk-btn-toggle" id="gk-btn-css" onclick="GKEditor.toggleCssSidebar()" style="display:none;">CSS</button>
                <button class="gk-btn-toggle" id="gk-btn-theme" onclick="GKEditor.toggleThemePanel()" style="display:none;">Theme</button>
                <button class="gk-btn-toggle" id="gk-btn-edit" onclick="GKEditor.toggleEdit()">Edit Page</button>
                <button class="gk-btn-save" id="gk-btn-save" onclick="GKEditor.save()" disabled style="display:none;">Save Changes</button>
                <button class="gk-btn-cancel" id="gk-btn-discard" onclick="GKEditor.discard()" style="display:none;">Discard</button>
            </div>
        </div>

        {{-- Hidden file inputs --}}
        <input type="file" id="gk-image-upload" accept="image/*" style="display:none;">
        <input type="file" id="gk-bg-image-upload" accept="image/*" style="display:none;">
        <input type="file" id="gk-video-upload" accept="video/mp4,video/webm,video/ogg" style="display:none;">

        {{-- CSS Sidebar --}}
        <div class="gk-css-sidebar" id="gk-css-sidebar">
            <div class="gk-css-sidebar-header">
                <span>CSS Editor</span>
                <button onclick="GKEditor.toggleCssSidebar()">&times;</button>
            </div>
            <div class="gk-css-sidebar-tabs">
                <button class="active" onclick="GKEditor.switchCssTab('global', this)">Global CSS</button>
                <button onclick="GKEditor.switchCssTab('page', this)">Page CSS</button>
            </div>
            <textarea id="gk-css-editor" placeholder="/* Write your CSS here */"></textarea>
            <div class="gk-css-sidebar-footer">
                <button class="gk-btn-apply" onclick="GKEditor.saveCss()">Save CSS</button>
                <button class="gk-btn-secondary" onclick="GKEditor.previewCss()">Preview</button>
            </div>
        </div>

        {{-- Hidden file input for logo upload --}}
        <input type="file" id="gk-logo-upload" accept="image/*" style="display:none;">

        {{-- Theme Editor Panel --}}
        <div class="gk-theme-panel" id="gk-theme-panel">
            <div class="gk-theme-panel-header">
                <span>Theme Editor</span>
                <button onclick="GKEditor.toggleThemePanel()">&times;</button>
            </div>
            <div class="gk-theme-section">
                <h4>Logo</h4>
                <div class="gk-theme-row" style="flex-direction: column; align-items: stretch; gap: 8px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        @php $currentLogo = \CreativeCatCo\GkCmsCore\Models\Setting::get('logo', ''); @endphp
                        @if($currentLogo)
                            <img id="gk-logo-preview" src="{{ asset('storage/' . $currentLogo) }}" alt="Logo" style="max-height:40px; border-radius:4px; background:#fff; padding:4px;">
                        @else
                            <img id="gk-logo-preview" src="" alt="Logo" style="max-height:40px; border-radius:4px; background:#fff; padding:4px; display:none;">
                        @endif
                        <input type="text" id="gk-theme-logo" value="{{ $currentLogo }}" placeholder="Upload or enter path" style="flex:1;">
                    </div>
                    <button type="button" onclick="document.getElementById('gk-logo-upload').click()" class="gk-btn-secondary" style="width:100%; padding:6px 12px; font-size:12px;">
                        Upload Logo
                    </button>
                </div>
                <div class="gk-theme-row" style="margin-top: 8px;">
                    <label>Show Tagline</label>
                    <label style="display:flex; align-items:center; gap:6px; cursor:pointer; min-width:auto;">
                        <input type="checkbox" id="gk-theme-show-tagline" {{ \CreativeCatCo\GkCmsCore\Models\Setting::get('show_tagline_header', true) ? 'checked' : '' }} style="width:16px; height:16px;">
                        <span style="font-size:12px; color:#64748b;">in header</span>
                    </label>
                </div>
            </div>
            <div class="gk-theme-section">
                <h4>Brand Colors</h4>
                <div class="gk-theme-row">
                    <label>Primary</label>
                    <input type="color" id="gk-theme-primary" value="{{ \CreativeCatCo\GkCmsCore\Models\Setting::get('theme_primary_color', '#cfff2e') }}">
                    <input type="text" id="gk-theme-primary-hex" value="{{ \CreativeCatCo\GkCmsCore\Models\Setting::get('theme_primary_color', '#cfff2e') }}" style="width:80px;">
                </div>
                <div class="gk-theme-row">
                    <label>Secondary</label>
                    <input type="color" id="gk-theme-secondary" value="{{ \CreativeCatCo\GkCmsCore\Models\Setting::get('theme_secondary_color', '#293726') }}">
                    <input type="text" id="gk-theme-secondary-hex" value="{{ \CreativeCatCo\GkCmsCore\Models\Setting::get('theme_secondary_color', '#293726') }}" style="width:80px;">
                </div>
            </div>
            <div class="gk-theme-section">
                <h4>Header & Footer</h4>
                <div class="gk-theme-row">
                    <label>Header BG</label>
                    <input type="color" id="gk-theme-header-bg" value="{{ \CreativeCatCo\GkCmsCore\Models\Setting::get('theme_header_bg', '#15171e') }}">
                    <input type="text" id="gk-theme-header-bg-hex" value="{{ \CreativeCatCo\GkCmsCore\Models\Setting::get('theme_header_bg', '#15171e') }}" style="width:80px;">
                </div>
                <div class="gk-theme-row">
                    <label>Footer BG</label>
                    <input type="color" id="gk-theme-footer-bg" value="{{ \CreativeCatCo\GkCmsCore\Models\Setting::get('theme_footer_bg', '#15171e') }}">
                    <input type="text" id="gk-theme-footer-bg-hex" value="{{ \CreativeCatCo\GkCmsCore\Models\Setting::get('theme_footer_bg', '#15171e') }}" style="width:80px;">
                </div>
            </div>
            <div class="gk-theme-section">
                <h4>Typography</h4>
                @php
                    $fontOptions = \CreativeCatCo\GkCmsCore\Filament\Pages\SettingPage::getFontOptions();
                    $currentHeading = \CreativeCatCo\GkCmsCore\Models\Setting::get('theme_font_heading', 'Inter');
                    $currentBody = \CreativeCatCo\GkCmsCore\Models\Setting::get('theme_font_body', 'Inter');
                @endphp
                <div class="gk-theme-row">
                    <label>Headings</label>
                    <select id="gk-theme-font-heading">
                        @foreach($fontOptions as $fontValue => $fontLabel)
                            <option value="{{ $fontValue }}" {{ $currentHeading === $fontValue ? 'selected' : '' }}>{{ $fontLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="gk-theme-row">
                    <label>Body</label>
                    <select id="gk-theme-font-body">
                        @foreach($fontOptions as $fontValue => $fontLabel)
                            <option value="{{ $fontValue }}" {{ $currentBody === $fontValue ? 'selected' : '' }}>{{ $fontLabel }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="padding: 16px;">
                <button class="gk-btn-apply" onclick="GKEditor.saveTheme()" style="width:100%;">Save Theme Settings</button>
            </div>
        </div>

        <script>
            const GKEditor = {
                isEditing: false,
                changes: {},
                originalValues: {},
                originalBgValues: {},
                pageSlug: '{{ $page->slug ?? "" }}',
                csrfToken: '{{ csrf_token() }}',
                activePopover: null,
                activeToolbar: null,
                buttonStyles: {},

                // ─── Icon Library (Heroicons outline subset) ───
                iconLibrary: {
                    'phone': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
                    'mail': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
                    'map-pin': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
                    'globe': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
                    'star': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
                    'heart': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
                    'check-circle': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
                    'shield': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
                    'zap': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
                    'clock': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
                    'users': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
                    'settings': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
                    'home': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
                    'code': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
                    'monitor': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
                    'smartphone': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>',
                    'trending-up': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>',
                    'dollar-sign': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
                    'award': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>',
                    'target': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
                    'briefcase': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
                    'camera': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>',
                    'edit': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>',
                    'search': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
                    'lock': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
                    'truck': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
                    'message-circle': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>',
                    'bar-chart': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>',
                    'layers': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>',
                    'play-circle': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>',
                    'headphones': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>',
                },

                // ─── Color Presets ───
                colorPresets: [
                    '#ef4444','#f97316','#f59e0b','#eab308','#84cc16','#22c55e','#14b8a6','#06b6d4',
                    '#3b82f6','#6366f1','#8b5cf6','#a855f7','#d946ef','#ec4899','#f43f5e',
                    '#1e293b','#334155','#475569','#64748b','#94a3b8','#ffffff','#f8fafc','#000000'
                ],

                init() {
                    document.body.classList.add('gk-editor-active');

                    // Bridge: Convert data-field + data-field-type="section_bg" to data-section-bg
                    // The AI creates sections with data-field="hero_bg" data-field-type="section_bg"
                    // but the inline editor looks for data-section-bg attribute
                    document.querySelectorAll('[data-field-type="section_bg"]').forEach(el => {
                        const fieldName = el.dataset.field;
                        if (fieldName && !el.hasAttribute('data-section-bg')) {
                            el.setAttribute('data-section-bg', fieldName);
                            if (!el.dataset.fieldLabel) {
                                el.dataset.fieldLabel = fieldName.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                            }
                        }
                        // Remove from regular field processing to avoid conflicts
                        el.removeAttribute('data-field');
                        el.removeAttribute('data-field-type');
                    });

                    // Auto-detect: On custom template pages, add data-section-bg to <section> elements
                    // that don't already have background editing attributes
                    document.querySelectorAll('#main-content section').forEach((el, idx) => {
                        if (!el.hasAttribute('data-section-bg') && !el.hasAttribute('data-field')) {
                            const sectionId = el.id || 'section_' + idx + '_bg';
                            el.setAttribute('data-section-bg', sectionId);
                            el.dataset.fieldLabel = 'Section ' + (idx + 1) + ' Background';
                        }
                    });

                    // Discover button styles from the page
                    const styleEl = document.querySelector('[data-button-styles]');
                    if (styleEl) {
                        try { this.buttonStyles = JSON.parse(styleEl.dataset.buttonStyles); } catch(e) {}
                    }
                    if (Object.keys(this.buttonStyles).length === 0) {
                        this.buttonStyles = {
                            primary: { label: 'Primary', classes: 'bg-blue-600 text-white hover:bg-blue-700 px-6 py-3 rounded-lg font-semibold' },
                            secondary: { label: 'Secondary', classes: 'border-2 border-blue-600 text-blue-600 hover:bg-blue-50 px-6 py-3 rounded-lg font-semibold' },
                            accent: { label: 'Accent', classes: 'bg-amber-500 text-white hover:bg-amber-600 px-6 py-3 rounded-lg font-semibold' }
                        };
                    }

                    // Store original values for all editable fields
                    document.querySelectorAll('[data-field]').forEach(el => {
                        const key = el.dataset.field;
                        const type = el.dataset.fieldType || 'text';
                        if (type === 'image') {
                            this.originalValues[key] = el.tagName === 'IMG' ? el.src : el.style.backgroundImage;
                        } else if (type === 'gallery') {
                            const items = [];
                            el.querySelectorAll('[data-gallery-item]').forEach(img => {
                                items.push({ src: img.getAttribute('src') || '', alt: img.getAttribute('alt') || '' });
                            });
                            this.originalValues[key] = JSON.parse(JSON.stringify(items));
                        } else if (type === 'button') {
                            this.originalValues[key] = {
                                text: el.textContent.trim(), link: el.getAttribute('href') || '#',
                                style: el.dataset.buttonStyle || 'primary', visible: el.style.display !== 'none'
                            };
                        } else if (type === 'color') {
                            this.originalValues[key] = el.dataset.colorValue || el.style.backgroundColor || '';
                        } else if (type === 'video') {
                            this.originalValues[key] = {
                                url: el.dataset.videoUrl || '', type: el.dataset.videoType || 'embed', path: el.dataset.videoPath || ''
                            };
                        } else if (type === 'icon') {
                            this.originalValues[key] = el.dataset.iconName || '';
                        } else if (type === 'repeater') {
                            this.originalValues[key] = this.getRepeaterState(el);
                        } else {
                            this.originalValues[key] = el.innerHTML;
                        }
                    });

                    // Store original values for section backgrounds
                    document.querySelectorAll('[data-section-bg]').forEach(el => {
                        const key = el.dataset.sectionBg;
                        const overlayEl = el.querySelector('.gk-section-overlay');
                        this.originalBgValues[key] = {
                            color: el.style.backgroundColor || '',
                            background: el.style.background || '',
                            image: el.style.backgroundImage || '',
                            mode: el.dataset.bgMode || 'cover',
                            overlayBg: overlayEl ? overlayEl.style.background : ''
                        };
                    });
                },

                toggleEdit() {
                    this.isEditing = !this.isEditing;
                    const btn = document.getElementById('gk-btn-edit');
                    const saveBtn = document.getElementById('gk-btn-save');
                    const discardBtn = document.getElementById('gk-btn-discard');

                    if (this.isEditing) {
                        btn.textContent = 'Stop Editing';
                        btn.classList.add('active');
                        saveBtn.style.display = '';
                        discardBtn.style.display = '';
                        document.getElementById('gk-btn-css').style.display = '';
                        document.getElementById('gk-btn-theme').style.display = '';
                        this.enableEditing();
                    } else {
                        btn.textContent = 'Edit Page';
                        btn.classList.remove('active');
                        saveBtn.style.display = 'none';
                        discardBtn.style.display = 'none';
                        document.getElementById('gk-btn-css').style.display = 'none';
                        document.getElementById('gk-btn-theme').style.display = 'none';
                        this.disableEditing();
                        this.closePopover();
                        this.closeToolbar();
                        this.closeCssSidebar();
                        this.closeThemePanel();
                    }
                },

                enableEditing() {
                    document.querySelectorAll('[data-field]').forEach(el => {
                        const type = el.dataset.fieldType || 'text';
                        el.classList.add('gk-editable-active');

                        if (type === 'image') {
                            this.addImageOverlay(el, 'field');
                        } else if (type === 'gallery') {
                            this.enableGalleryEditing(el);
                        } else if (type === 'button') {
                            el.addEventListener('click', el._buttonClickHandler = (e) => {
                                e.preventDefault(); e.stopPropagation();
                                this.openButtonEditor(el);
                            });
                        } else if (type === 'button_group') {
                            el.querySelectorAll('[data-button-index]').forEach(btn => {
                                btn.classList.add('gk-editable-active');
                                btn.addEventListener('click', btn._clickHandler = (e) => {
                                    e.preventDefault(); e.stopPropagation();
                                    this.openButtonEditor(btn, el.dataset.field);
                                });
                            });
                            if (!el.querySelector('.gk-add-button')) {
                                const addBtn = document.createElement('button');
                                addBtn.className = 'gk-add-button';
                                addBtn.textContent = '+ Add Button';
                                addBtn.style.cssText = 'padding:6px 14px;border:2px dashed #94a3b8;border-radius:8px;background:transparent;color:#94a3b8;cursor:pointer;font-size:12px;font-weight:600;margin-left:8px;';
                                addBtn.onclick = (e) => { e.preventDefault(); this.addButtonToGroup(el); };
                                el.appendChild(addBtn);
                            }
                        } else if (type === 'color') {
                            this.enableColorEditing(el);
                        } else if (type === 'video') {
                            this.enableVideoEditing(el);
                        } else if (type === 'icon') {
                            this.enableIconEditing(el);
                        } else if (type === 'repeater') {
                            this.enableRepeaterEditing(el);
                        } else {
                            // text, textarea, richtext — all get contentEditable + light toolbar
                            el.contentEditable = true;
                            el.classList.add('gk-editing');
                            el.addEventListener('input', el._inputHandler = () => this.markChanged(el));
                            el.addEventListener('focus', el._focusHandler = () => this.showLightToolbar(el, type));
                            el.addEventListener('blur', el._blurHandler = (e) => {
                                // Delay hiding to allow toolbar button clicks
                                setTimeout(() => {
                                    if (!this.activeToolbar || !this.activeToolbar.contains(document.activeElement)) {
                                        this.closeToolbar();
                                    }
                                }, 200);
                            });
                            if (type === 'text') {
                                el.addEventListener('keydown', el._keyHandler = (e) => {
                                    if (e.key === 'Enter') { e.preventDefault(); el.blur(); }
                                });
                            }
                        }
                    });

                    // Section backgrounds
                    document.querySelectorAll('[data-section-bg]').forEach(el => {
                        el.classList.add('gk-editable-active');
                        this.addBgOverlay(el);
                    });

                    document.getElementById('gk-editor-status').textContent = 'Editing mode — click on highlighted elements to edit';
                },

                disableEditing() {
                    document.querySelectorAll('[data-field]').forEach(el => {
                        const type = el.dataset.fieldType || 'text';
                        el.classList.remove('gk-editable-active', 'gk-editing');
                        el.contentEditable = false;

                        // Clean up image wrapper
                        if (type === 'image' && el.tagName === 'IMG' && el.parentElement && el.parentElement.classList.contains('gk-image-wrapper')) {
                            const wrapper = el.parentElement;
                            wrapper.querySelector('.gk-image-overlay')?.remove();
                            wrapper.querySelector('.gk-image-badge')?.remove();
                            wrapper.onclick = null;
                            wrapper.parentNode.insertBefore(el, wrapper);
                            wrapper.remove();
                        } else if (type === 'image') {
                            el.querySelector('.gk-image-overlay')?.remove();
                            el.querySelector('.gk-image-badge')?.remove();
                        }

                        if (type === 'gallery') this.disableGalleryEditing(el);
                        if (type === 'color') this.disableColorEditing(el);
                        if (type === 'video') this.disableVideoEditing(el);
                        if (type === 'icon') this.disableIconEditing(el);
                        if (type === 'repeater') this.disableRepeaterEditing(el);

                        if (type === 'button_group') {
                            el.querySelectorAll('[data-button-index]').forEach(btn => {
                                btn.classList.remove('gk-editable-active');
                                if (btn._clickHandler) btn.removeEventListener('click', btn._clickHandler);
                            });
                            el.querySelector('.gk-add-button')?.remove();
                        }

                        if (el._buttonClickHandler) el.removeEventListener('click', el._buttonClickHandler);
                        if (el._inputHandler) el.removeEventListener('input', el._inputHandler);
                        if (el._keyHandler) el.removeEventListener('keydown', el._keyHandler);
                        if (el._focusHandler) el.removeEventListener('focus', el._focusHandler);
                        if (el._blurHandler) el.removeEventListener('blur', el._blurHandler);
                    });

                    document.querySelectorAll('[data-section-bg]').forEach(el => {
                        el.classList.remove('gk-editable-active');
                        el.querySelector('.gk-bg-badge')?.remove();
                    });

                    this.closeToolbar();
                    document.getElementById('gk-editor-status').textContent = 'Click "Edit" to start editing';
                },

                // ═══════════════════════════════════════════════════════════
                // LIGHT TOOLBAR (text/textarea/richtext)
                // ═══════════════════════════════════════════════════════════
                showLightToolbar(el, type) {
                    this.closeToolbar();
                    const rect = el.getBoundingClientRect();
                    const toolbar = document.createElement('div');
                    toolbar.className = 'gk-light-toolbar';
                    toolbar.style.top = Math.max(50, rect.top - 40) + 'px';
                    toolbar.style.left = Math.max(8, rect.left) + 'px';

                    // Bold
                    const boldBtn = document.createElement('button');
                    boldBtn.innerHTML = '<strong>B</strong>';
                    boldBtn.title = 'Bold';
                    boldBtn.onmousedown = (e) => { e.preventDefault(); document.execCommand('bold'); this.markChanged(el); this.updateToolbarState(toolbar); };
                    toolbar.appendChild(boldBtn);

                    // Italic
                    const italicBtn = document.createElement('button');
                    italicBtn.innerHTML = '<em>I</em>';
                    italicBtn.title = 'Italic';
                    italicBtn.onmousedown = (e) => { e.preventDefault(); document.execCommand('italic'); this.markChanged(el); this.updateToolbarState(toolbar); };
                    toolbar.appendChild(italicBtn);

                    // Separator
                    const sep = document.createElement('div');
                    sep.className = 'gk-toolbar-sep';
                    toolbar.appendChild(sep);

                    // Link
                    const linkBtn = document.createElement('button');
                    linkBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>';
                    linkBtn.title = 'Insert Link';
                    linkBtn.onmousedown = (e) => { e.preventDefault(); this.toggleLinkInput(toolbar, el); };
                    toolbar.appendChild(linkBtn);

                    if (type === 'richtext') {
                        const sep2 = document.createElement('div');
                        sep2.className = 'gk-toolbar-sep';
                        toolbar.appendChild(sep2);

                        // Unordered list
                        const ulBtn = document.createElement('button');
                        ulBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="3" cy="6" r="1" fill="currentColor"/><circle cx="3" cy="12" r="1" fill="currentColor"/><circle cx="3" cy="18" r="1" fill="currentColor"/></svg>';
                        ulBtn.title = 'Bullet List';
                        ulBtn.onmousedown = (e) => { e.preventDefault(); document.execCommand('insertUnorderedList'); this.markChanged(el); };
                        toolbar.appendChild(ulBtn);

                        // Ordered list
                        const olBtn = document.createElement('button');
                        olBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><text x="2" y="8" font-size="8" fill="currentColor" stroke="none">1</text><text x="2" y="14" font-size="8" fill="currentColor" stroke="none">2</text><text x="2" y="20" font-size="8" fill="currentColor" stroke="none">3</text></svg>';
                        olBtn.title = 'Numbered List';
                        olBtn.onmousedown = (e) => { e.preventDefault(); document.execCommand('insertOrderedList'); this.markChanged(el); };
                        toolbar.appendChild(olBtn);
                    }

                    document.body.appendChild(toolbar);
                    this.activeToolbar = toolbar;
                    this._activeToolbarEl = el;
                    this.updateToolbarState(toolbar);
                },

                updateToolbarState(toolbar) {
                    if (!toolbar) return;
                    const buttons = toolbar.querySelectorAll('button');
                    if (buttons[0]) buttons[0].classList.toggle('active', document.queryCommandState('bold'));
                    if (buttons[1]) buttons[1].classList.toggle('active', document.queryCommandState('italic'));
                },

                toggleLinkInput(toolbar, el) {
                    const existing = toolbar.querySelector('.gk-link-input-wrap');
                    if (existing) { existing.remove(); return; }

                    // Save current selection
                    const sel = window.getSelection();
                    const range = sel.rangeCount > 0 ? sel.getRangeAt(0).cloneRange() : null;

                    const wrap = document.createElement('div');
                    wrap.className = 'gk-link-input-wrap';
                    const input = document.createElement('input');
                    input.type = 'url';
                    input.placeholder = 'https://...';
                    // Check if selection is inside a link
                    const parentLink = sel.anchorNode?.parentElement?.closest('a');
                    if (parentLink) input.value = parentLink.href;
                    wrap.appendChild(input);

                    const applyBtn = document.createElement('button');
                    applyBtn.textContent = 'OK';
                    applyBtn.onmousedown = (e) => {
                        e.preventDefault();
                        // Restore selection
                        if (range) { sel.removeAllRanges(); sel.addRange(range); }
                        const url = input.value.trim();
                        if (url) {
                            document.execCommand('createLink', false, url);
                        } else {
                            document.execCommand('unlink');
                        }
                        this.markChanged(el);
                        wrap.remove();
                    };
                    wrap.appendChild(applyBtn);

                    const removeBtn = document.createElement('button');
                    removeBtn.textContent = 'Remove';
                    removeBtn.style.background = '#ef4444';
                    removeBtn.onmousedown = (e) => {
                        e.preventDefault();
                        if (range) { sel.removeAllRanges(); sel.addRange(range); }
                        document.execCommand('unlink');
                        this.markChanged(el);
                        wrap.remove();
                    };
                    if (parentLink) wrap.appendChild(removeBtn);

                    toolbar.appendChild(wrap);
                    input.focus();
                },

                closeToolbar() {
                    if (this.activeToolbar) {
                        this.activeToolbar.remove();
                        this.activeToolbar = null;
                        this._activeToolbarEl = null;
                    }
                },

                // ═══════════════════════════════════════════════════════════
                // IMAGE OVERLAY
                // ═══════════════════════════════════════════════════════════
                addImageOverlay(el, context) {
                    let container = el;
                    if (el.tagName === 'IMG') {
                        if (el.parentElement && el.parentElement.classList.contains('gk-image-wrapper')) {
                            container = el.parentElement;
                        } else {
                            const wrapper = document.createElement('div');
                            wrapper.className = 'gk-image-wrapper';
                            wrapper.style.cssText = 'position:relative;display:inline-block;width:100%;';
                            el.parentNode.insertBefore(wrapper, el);
                            wrapper.appendChild(el);
                            container = wrapper;
                        }
                        container.style.zIndex = '60';
                        container.style.position = 'relative';
                    } else {
                        const origPos = getComputedStyle(el).position;
                        if (origPos === 'static') el.style.position = 'relative';
                        container = el;
                    }

                    if (container.querySelector('.gk-image-overlay')) return;

                    const overlay = document.createElement('div');
                    overlay.className = 'gk-image-overlay';
                    overlay.innerHTML = '<span>Click to change image</span>';
                    container.appendChild(overlay);

                    const badge = document.createElement('div');
                    badge.className = 'gk-image-badge';
                    badge.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg> Change Image';
                    badge.onclick = (e) => { e.preventDefault(); e.stopPropagation(); if (context === 'field') this.uploadImage(el); };
                    container.appendChild(badge);

                    container.onclick = (e) => {
                        if (e.target.closest('.gk-image-badge')) return;
                        e.preventDefault(); e.stopPropagation();
                        if (context === 'field') this.uploadImage(el);
                    };

                    container.addEventListener('mouseenter', () => { overlay.style.opacity = '1'; });
                    container.addEventListener('mouseleave', () => { overlay.style.opacity = '0'; });
                },

                addBgOverlay(el) {
                    if (el.querySelector('.gk-bg-badge')) return;
                    const origPos = getComputedStyle(el).position;
                    if (origPos === 'static') el.style.position = 'relative';

                    const badge = document.createElement('div');
                    badge.className = 'gk-bg-badge';
                    badge.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg> Edit Background';
                    badge.onclick = (e) => { e.preventDefault(); e.stopPropagation(); this.openBgEditor(el); };
                    el.appendChild(badge);
                },

                // ═══════════════════════════════════════════════════════════
                // GALLERY EDITING
                // ═══════════════════════════════════════════════════════════
                enableGalleryEditing(el) {
                    const images = el.querySelectorAll('[data-gallery-item]');
                    images.forEach((img, idx) => {
                        let wrapper = img.parentElement;
                        if (!wrapper.classList.contains('gk-gallery-item-wrapper')) {
                            wrapper = document.createElement('div');
                            wrapper.className = 'gk-gallery-item-wrapper';
                            img.parentNode.insertBefore(wrapper, img);
                            wrapper.appendChild(img);
                        }
                        if (!wrapper.querySelector('.gk-gallery-item-badge')) {
                            const changeBadge = document.createElement('div');
                            changeBadge.className = 'gk-gallery-item-badge';
                            changeBadge.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg> Change';
                            changeBadge.onclick = (e) => { e.preventDefault(); e.stopPropagation(); this.uploadGalleryImage(el, idx); };
                            wrapper.appendChild(changeBadge);
                        }
                        if (!wrapper.querySelector('.gk-gallery-remove-badge')) {
                            const removeBadge = document.createElement('div');
                            removeBadge.className = 'gk-gallery-remove-badge';
                            removeBadge.textContent = '\u2715';
                            removeBadge.onclick = (e) => { e.preventDefault(); e.stopPropagation(); this.removeGalleryImage(el, idx); };
                            wrapper.appendChild(removeBadge);
                        }
                    });

                    if (!el.querySelector('.gk-gallery-add-btn')) {
                        const addBtn = document.createElement('div');
                        addBtn.className = 'gk-gallery-add-btn';
                        addBtn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add Image';
                        addBtn.onclick = (e) => { e.preventDefault(); e.stopPropagation(); this.addGalleryImage(el); };
                        const gridContainer = el.querySelector('[data-gallery-grid]') || el;
                        gridContainer.appendChild(addBtn);
                    }
                },

                uploadGalleryImage(galleryEl, index) {
                    const input = document.getElementById('gk-image-upload');
                    input.onchange = async () => {
                        const file = input.files[0]; if (!file) return;
                        const formData = new FormData();
                        formData.append('image', file); formData.append('_token', this.csrfToken);
                        try {
                            const res = await fetch('/api/cms/upload-image', { method: 'POST', body: formData });
                            const data = await res.json();
                            if (data.path) {
                                const imgs = galleryEl.querySelectorAll('[data-gallery-item]');
                                if (imgs[index]) imgs[index].src = data.url;
                                this.syncGalleryChanges(galleryEl);
                                this.toast('Image updated', 'success');
                            }
                        } catch (err) { this.toast('Upload failed', 'error'); }
                        input.value = '';
                    };
                    input.click();
                },

                addGalleryImage(galleryEl) {
                    const input = document.getElementById('gk-image-upload');
                    input.onchange = async () => {
                        const file = input.files[0]; if (!file) return;
                        const formData = new FormData();
                        formData.append('image', file); formData.append('_token', this.csrfToken);
                        try {
                            const res = await fetch('/api/cms/upload-image', { method: 'POST', body: formData });
                            const data = await res.json();
                            if (data.path) {
                                const gridContainer = galleryEl.querySelector('[data-gallery-grid]') || galleryEl;
                                const addBtn = gridContainer.querySelector('.gk-gallery-add-btn');
                                const newImg = document.createElement('img');
                                newImg.src = data.url; newImg.alt = 'Gallery image';
                                newImg.dataset.galleryItem = '';
                                newImg.className = 'w-full h-64 object-cover rounded-lg';
                                const wrapper = document.createElement('div');
                                wrapper.className = 'gk-gallery-item-wrapper';
                                wrapper.appendChild(newImg);
                                if (addBtn) gridContainer.insertBefore(wrapper, addBtn);
                                else gridContainer.appendChild(wrapper);
                                this.disableGalleryEditing(galleryEl);
                                this.enableGalleryEditing(galleryEl);
                                this.syncGalleryChanges(galleryEl);
                                this.toast('Image added', 'success');
                            }
                        } catch (err) { this.toast('Upload failed', 'error'); }
                        input.value = '';
                    };
                    input.click();
                },

                removeGalleryImage(galleryEl, index) {
                    const imgs = galleryEl.querySelectorAll('[data-gallery-item]');
                    if (imgs[index]) {
                        const wrapper = imgs[index].closest('.gk-gallery-item-wrapper');
                        if (wrapper) wrapper.remove(); else imgs[index].remove();
                    }
                    this.disableGalleryEditing(galleryEl);
                    this.enableGalleryEditing(galleryEl);
                    this.syncGalleryChanges(galleryEl);
                    this.toast('Image removed', 'success');
                },

                syncGalleryChanges(galleryEl) {
                    const key = galleryEl.dataset.field;
                    const items = [];
                    galleryEl.querySelectorAll('[data-gallery-item]').forEach(img => {
                        let src = img.getAttribute('src') || '';
                        const storageMatch = src.match(/\/storage\/(.+)$/);
                        if (storageMatch) src = 'storage/' + storageMatch[1];
                        items.push({ src: src, alt: img.getAttribute('alt') || '' });
                    });
                    this.changes[key] = items;
                    galleryEl.classList.add('gk-field-changed');
                    this.updateStatus();
                },

                disableGalleryEditing(el) {
                    el.querySelectorAll('.gk-gallery-item-badge').forEach(b => b.remove());
                    el.querySelectorAll('.gk-gallery-remove-badge').forEach(b => b.remove());
                    el.querySelectorAll('.gk-gallery-add-btn').forEach(b => b.remove());
                    el.querySelectorAll('.gk-gallery-item-wrapper').forEach(wrapper => {
                        const img = wrapper.querySelector('[data-gallery-item]');
                        if (img) { wrapper.parentNode.insertBefore(img, wrapper); wrapper.remove(); }
                    });
                },

                // ═══════════════════════════════════════════════════════════
                // COLOR PICKER FIELD
                // ═══════════════════════════════════════════════════════════
                enableColorEditing(el) {
                    const origPos = getComputedStyle(el).position;
                    if (origPos === 'static') el.style.position = 'relative';
                    if (el.querySelector('.gk-color-badge')) return;

                    const badge = document.createElement('div');
                    badge.className = 'gk-color-badge';
                    const currentColor = el.dataset.colorValue || el.style.backgroundColor || '#3b82f6';
                    badge.innerHTML = '<span style="display:inline-block;width:14px;height:14px;border-radius:4px;background:' + currentColor + ';border:1px solid rgba(255,255,255,0.5);"></span> Color';
                    badge.onclick = (e) => { e.preventDefault(); e.stopPropagation(); this.openColorPicker(el); };
                    el.appendChild(badge);
                },

                disableColorEditing(el) {
                    el.querySelector('.gk-color-badge')?.remove();
                },

                openColorPicker(el) {
                    const key = el.dataset.field;
                    const currentColor = el.dataset.colorValue || el.style.backgroundColor || '#3b82f6';
                    const rect = el.getBoundingClientRect();

                    const popover = this.createPopover(el.dataset.fieldLabel || 'Color', rect);
                    const body = popover.querySelector('.gk-popover-body');

                    // Color input row
                    const row = document.createElement('div');
                    row.className = 'gk-color-row';
                    const colorInput = document.createElement('input');
                    colorInput.type = 'color';
                    colorInput.value = this.rgbToHex(currentColor);
                    const hexInput = document.createElement('input');
                    hexInput.type = 'text';
                    hexInput.value = this.rgbToHex(currentColor);
                    hexInput.style.flex = '1';
                    hexInput.placeholder = '#000000';
                    colorInput.oninput = () => { hexInput.value = colorInput.value; this.applyColor(el, colorInput.value); };
                    hexInput.oninput = () => {
                        if (/^#[0-9a-fA-F]{6}$/.test(hexInput.value)) {
                            colorInput.value = hexInput.value; this.applyColor(el, hexInput.value);
                        }
                    };
                    row.appendChild(colorInput);
                    row.appendChild(hexInput);
                    body.appendChild(row);

                    // Presets
                    const presetLabel = document.createElement('label');
                    presetLabel.textContent = 'Presets';
                    body.appendChild(presetLabel);
                    const presetGrid = document.createElement('div');
                    presetGrid.className = 'gk-color-presets';
                    this.colorPresets.forEach(c => {
                        const swatch = document.createElement('div');
                        swatch.className = 'gk-color-preset';
                        swatch.style.background = c;
                        if (c.toLowerCase() === this.rgbToHex(currentColor).toLowerCase()) swatch.classList.add('selected');
                        swatch.onclick = () => {
                            colorInput.value = c; hexInput.value = c;
                            this.applyColor(el, c);
                            presetGrid.querySelectorAll('.gk-color-preset').forEach(s => s.classList.remove('selected'));
                            swatch.classList.add('selected');
                        };
                        presetGrid.appendChild(swatch);
                    });
                    body.appendChild(presetGrid);

                    // Footer
                    const footer = popover.querySelector('.gk-popover-footer');
                    const applyBtn = document.createElement('button');
                    applyBtn.className = 'gk-btn-apply';
                    applyBtn.textContent = 'Done';
                    applyBtn.onclick = () => this.closePopover();
                    footer.appendChild(applyBtn);
                },

                applyColor(el, color) {
                    const key = el.dataset.field;
                    const target = el.dataset.colorTarget || 'background-color';
                    if (target === 'color') {
                        el.style.color = color;
                    } else {
                        el.style.backgroundColor = color;
                    }
                    el.dataset.colorValue = color;
                    this.changes[key] = color;
                    el.classList.add('gk-field-changed');
                    this.updateStatus();
                    // Update badge swatch
                    const badge = el.querySelector('.gk-color-badge span');
                    if (badge) badge.style.background = color;
                },

                rgbToHex(color) {
                    if (!color) return '#000000';
                    if (color.startsWith('#')) return color;
                    const match = color.match(/\d+/g);
                    if (!match || match.length < 3) return '#000000';
                    return '#' + match.slice(0,3).map(x => parseInt(x).toString(16).padStart(2,'0')).join('');
                },

                // ═══════════════════════════════════════════════════════════
                // VIDEO/EMBED FIELD
                // ═══════════════════════════════════════════════════════════
                enableVideoEditing(el) {
                    const origPos = getComputedStyle(el).position;
                    if (origPos === 'static') el.style.position = 'relative';
                    if (el.querySelector('.gk-video-badge')) return;

                    const badge = document.createElement('div');
                    badge.className = 'gk-video-badge';
                    badge.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg> Edit Video';
                    badge.onclick = (e) => { e.preventDefault(); e.stopPropagation(); this.openVideoEditor(el); };
                    el.appendChild(badge);
                },

                disableVideoEditing(el) {
                    el.querySelector('.gk-video-badge')?.remove();
                },

                openVideoEditor(el) {
                    const key = el.dataset.field;
                    const currentUrl = el.dataset.videoUrl || '';
                    const rect = el.getBoundingClientRect();

                    const popover = this.createPopover(el.dataset.fieldLabel || 'Video', rect);
                    const body = popover.querySelector('.gk-popover-body');

                    // URL input
                    const urlLabel = document.createElement('label');
                    urlLabel.textContent = 'Video URL (YouTube, Vimeo, or direct)';
                    body.appendChild(urlLabel);
                    const urlInput = document.createElement('input');
                    urlInput.type = 'url';
                    urlInput.value = currentUrl;
                    urlInput.placeholder = 'https://www.youtube.com/watch?v=...';
                    body.appendChild(urlInput);

                    // Upload option
                    const uploadLabel = document.createElement('label');
                    uploadLabel.textContent = 'Or Upload Video';
                    body.appendChild(uploadLabel);
                    const uploadBtn = document.createElement('button');
                    uploadBtn.className = 'gk-btn-secondary';
                    uploadBtn.textContent = 'Choose Video File';
                    uploadBtn.style.width = '100%';
                    uploadBtn.onclick = () => {
                        const fileInput = document.getElementById('gk-video-upload');
                        fileInput.onchange = async () => {
                            const file = fileInput.files[0]; if (!file) return;
                            uploadBtn.textContent = 'Uploading...'; uploadBtn.disabled = true;
                            const formData = new FormData();
                            formData.append('video', file); formData.append('_token', this.csrfToken);
                            try {
                                const res = await fetch('/api/cms/upload-video', { method: 'POST', body: formData });
                                const data = await res.json();
                                if (data.url) {
                                    urlInput.value = data.url;
                                    uploadBtn.textContent = 'Uploaded!';
                                    this.toast('Video uploaded', 'success');
                                } else {
                                    uploadBtn.textContent = 'Choose Video File';
                                    this.toast('Upload failed', 'error');
                                }
                            } catch (err) {
                                uploadBtn.textContent = 'Choose Video File';
                                this.toast('Upload failed', 'error');
                            }
                            uploadBtn.disabled = false;
                            fileInput.value = '';
                        };
                        fileInput.click();
                    };
                    body.appendChild(uploadBtn);

                    // Preview
                    const previewLabel = document.createElement('label');
                    previewLabel.textContent = 'Preview';
                    body.appendChild(previewLabel);
                    const preview = document.createElement('div');
                    preview.className = 'gk-video-preview';
                    if (currentUrl) preview.innerHTML = this.getVideoEmbed(currentUrl);
                    body.appendChild(preview);

                    urlInput.oninput = () => {
                        const url = urlInput.value.trim();
                        if (url) preview.innerHTML = this.getVideoEmbed(url);
                        else preview.innerHTML = '';
                    };

                    // Footer
                    const footer = popover.querySelector('.gk-popover-footer');
                    const applyBtn = document.createElement('button');
                    applyBtn.className = 'gk-btn-apply';
                    applyBtn.textContent = 'Apply';
                    applyBtn.onclick = () => {
                        const url = urlInput.value.trim();
                        this.applyVideo(el, url);
                        this.closePopover();
                    };
                    footer.appendChild(applyBtn);

                    const removeBtn = document.createElement('button');
                    removeBtn.className = 'gk-btn-remove';
                    removeBtn.textContent = 'Remove';
                    removeBtn.onclick = () => {
                        this.applyVideo(el, '');
                        this.closePopover();
                    };
                    footer.appendChild(removeBtn);
                },

                applyVideo(el, url) {
                    const key = el.dataset.field;
                    const videoType = this.detectVideoType(url);
                    el.dataset.videoUrl = url;
                    el.dataset.videoType = videoType;

                    // Update the embed in the element
                    const iframe = el.querySelector('iframe');
                    const video = el.querySelector('video');
                    if (url) {
                        const embedUrl = this.getVideoEmbedUrl(url);
                        if (videoType === 'upload' || videoType === 'direct') {
                            if (video) { video.src = url; }
                            else if (iframe) { iframe.remove(); const v = document.createElement('video'); v.src = url; v.controls = true; v.style.cssText = 'width:100%;height:100%;'; el.insertBefore(v, el.firstChild); }
                            else { const v = document.createElement('video'); v.src = url; v.controls = true; v.style.cssText = 'width:100%;border-radius:8px;'; el.insertBefore(v, el.querySelector('.gk-video-badge')); }
                        } else {
                            if (iframe) { iframe.src = embedUrl; }
                            else if (video) { video.remove(); const f = document.createElement('iframe'); f.src = embedUrl; f.style.cssText = 'width:100%;aspect-ratio:16/9;border:none;border-radius:8px;'; f.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture'; f.allowFullscreen = true; el.insertBefore(f, el.firstChild); }
                            else { const f = document.createElement('iframe'); f.src = embedUrl; f.style.cssText = 'width:100%;aspect-ratio:16/9;border:none;border-radius:8px;'; f.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture'; f.allowFullscreen = true; el.insertBefore(f, el.querySelector('.gk-video-badge')); }
                        }
                    } else {
                        if (iframe) iframe.remove();
                        if (video) video.remove();
                    }

                    this.changes[key] = { url: url, type: videoType };
                    el.classList.add('gk-field-changed');
                    this.updateStatus();
                },

                detectVideoType(url) {
                    if (!url) return '';
                    if (url.includes('youtube.com') || url.includes('youtu.be')) return 'youtube';
                    if (url.includes('vimeo.com')) return 'vimeo';
                    if (url.includes('dailymotion.com')) return 'dailymotion';
                    if (url.includes('wistia.com') || url.includes('wistia.net')) return 'wistia';
                    if (url.match(/\.(mp4|webm|ogg)(\?|$)/i)) return 'direct';
                    if (url.includes('/storage/')) return 'upload';
                    return 'embed';
                },

                getVideoEmbedUrl(url) {
                    const type = this.detectVideoType(url);
                    if (type === 'youtube') {
                        const match = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
                        return match ? 'https://www.youtube.com/embed/' + match[1] : url;
                    }
                    if (type === 'vimeo') {
                        const match = url.match(/vimeo\.com\/(?:video\/)?(\d+)/);
                        return match ? 'https://player.vimeo.com/video/' + match[1] : url;
                    }
                    if (type === 'dailymotion') {
                        const match = url.match(/dailymotion\.com\/video\/([a-zA-Z0-9]+)/);
                        return match ? 'https://www.dailymotion.com/embed/video/' + match[1] : url;
                    }
                    return url;
                },

                getVideoEmbed(url) {
                    const type = this.detectVideoType(url);
                    if (type === 'direct' || type === 'upload') {
                        return '<video src="' + url + '" controls style="width:100%;height:100%;"></video>';
                    }
                    const embedUrl = this.getVideoEmbedUrl(url);
                    return '<iframe src="' + embedUrl + '" style="width:100%;height:100%;border:none;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                },

                // ═══════════════════════════════════════════════════════════
                // ICON PICKER FIELD
                // ═══════════════════════════════════════════════════════════
                enableIconEditing(el) {
                    const origPos = getComputedStyle(el).position;
                    if (origPos === 'static') el.style.position = 'relative';
                    if (el.querySelector('.gk-icon-badge')) return;

                    const badge = document.createElement('div');
                    badge.className = 'gk-icon-badge';
                    badge.innerHTML = '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>';
                    badge.onclick = (e) => { e.preventDefault(); e.stopPropagation(); this.openIconPicker(el); };
                    el.appendChild(badge);
                },

                disableIconEditing(el) {
                    el.querySelector('.gk-icon-badge')?.remove();
                },

                openIconPicker(el) {
                    const key = el.dataset.field;
                    const currentIcon = el.dataset.iconName || '';
                    const rect = el.getBoundingClientRect();

                    const popover = this.createPopover(el.dataset.fieldLabel || 'Icon', rect);
                    const body = popover.querySelector('.gk-popover-body');

                    // Search
                    const searchInput = document.createElement('input');
                    searchInput.type = 'text';
                    searchInput.placeholder = 'Search icons...';
                    body.appendChild(searchInput);

                    // Grid
                    const grid = document.createElement('div');
                    grid.className = 'gk-icon-grid';
                    body.appendChild(grid);

                    const renderIcons = (filter) => {
                        grid.innerHTML = '';
                        Object.entries(this.iconLibrary).forEach(([name, svg]) => {
                            if (filter && !name.includes(filter.toLowerCase())) return;
                            const option = document.createElement('div');
                            option.className = 'gk-icon-option';
                            if (name === currentIcon) option.classList.add('selected');
                            option.innerHTML = svg;
                            option.title = name;
                            option.onclick = () => {
                                this.applyIcon(el, name, svg);
                                grid.querySelectorAll('.gk-icon-option').forEach(o => o.classList.remove('selected'));
                                option.classList.add('selected');
                            };
                            grid.appendChild(option);
                        });
                    };

                    renderIcons('');
                    searchInput.oninput = () => renderIcons(searchInput.value);

                    // Footer
                    const footer = popover.querySelector('.gk-popover-footer');
                    const doneBtn = document.createElement('button');
                    doneBtn.className = 'gk-btn-apply';
                    doneBtn.textContent = 'Done';
                    doneBtn.onclick = () => this.closePopover();
                    footer.appendChild(doneBtn);
                },

                applyIcon(el, name, svg) {
                    const key = el.dataset.field;
                    el.dataset.iconName = name;

                    // Replace the SVG content inside the element
                    const existingSvg = el.querySelector('svg');
                    if (existingSvg) {
                        const temp = document.createElement('div');
                        temp.innerHTML = svg;
                        const newSvg = temp.querySelector('svg');
                        // Preserve original size classes/attributes
                        const origClass = existingSvg.getAttribute('class');
                        const origWidth = existingSvg.getAttribute('width');
                        const origHeight = existingSvg.getAttribute('height');
                        if (origClass) newSvg.setAttribute('class', origClass);
                        if (origWidth) newSvg.setAttribute('width', origWidth);
                        if (origHeight) newSvg.setAttribute('height', origHeight);
                        existingSvg.replaceWith(newSvg);
                    }

                    this.changes[key] = name;
                    el.classList.add('gk-field-changed');
                    this.updateStatus();
                },

                // ═══════════════════════════════════════════════════════════
                // REPEATER FIELD
                // ═══════════════════════════════════════════════════════════
                enableRepeaterEditing(el) {
                    const items = el.querySelectorAll('[data-repeater-item]');
                    items.forEach((item, idx) => {
                        if (item.classList.contains('gk-repeater-item-wrapper')) return;
                        item.classList.add('gk-repeater-item-wrapper');

                        // Item label
                        const label = document.createElement('div');
                        label.className = 'gk-repeater-item-label';
                        label.textContent = 'Item ' + (idx + 1);
                        item.appendChild(label);

                        // Controls
                        const controls = document.createElement('div');
                        controls.className = 'gk-repeater-item-controls';
                        const removeBtn = document.createElement('div');
                        removeBtn.className = 'gk-repeater-item-remove';
                        removeBtn.textContent = '\u2715 Remove';
                        removeBtn.onclick = (e) => { e.preventDefault(); e.stopPropagation(); this.removeRepeaterItem(el, idx); };
                        controls.appendChild(removeBtn);
                        item.appendChild(controls);

                        // Enable sub-field editing
                        item.querySelectorAll('[data-repeater-sub]').forEach(subEl => {
                            const subType = subEl.dataset.repeaterSubType || 'text';
                            if (subType === 'image') {
                                this.addImageOverlay(subEl, 'repeater');
                                // Override click to handle repeater image upload
                                const wrapper = subEl.tagName === 'IMG' ? subEl.parentElement : subEl;
                                wrapper.onclick = (e) => {
                                    e.preventDefault(); e.stopPropagation();
                                    this.uploadRepeaterImage(el, subEl);
                                };
                                const badge = wrapper.querySelector('.gk-image-badge');
                                if (badge) badge.onclick = (e) => { e.preventDefault(); e.stopPropagation(); this.uploadRepeaterImage(el, subEl); };
                            } else {
                                subEl.contentEditable = true;
                                subEl.classList.add('gk-editing');
                                subEl.addEventListener('input', subEl._inputHandler = () => {
                                    this.syncRepeaterChanges(el);
                                    el.classList.add('gk-field-changed');
                                    this.updateStatus();
                                });
                                subEl.addEventListener('focus', subEl._focusHandler = () => this.showLightToolbar(subEl, subType));
                                subEl.addEventListener('blur', subEl._blurHandler = () => {
                                    setTimeout(() => {
                                        if (!this.activeToolbar || !this.activeToolbar.contains(document.activeElement)) this.closeToolbar();
                                    }, 200);
                                });
                                if (subType === 'text') {
                                    subEl.addEventListener('keydown', subEl._keyHandler = (e) => {
                                        if (e.key === 'Enter') { e.preventDefault(); subEl.blur(); }
                                    });
                                }
                            }
                        });
                    });

                    // Add button
                    if (!el.querySelector('.gk-repeater-add-btn')) {
                        const addBtn = document.createElement('div');
                        addBtn.className = 'gk-repeater-add-btn';
                        addBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add Item';
                        addBtn.onclick = (e) => { e.preventDefault(); e.stopPropagation(); this.addRepeaterItem(el); };
                        el.appendChild(addBtn);
                    }
                },

                disableRepeaterEditing(el) {
                    el.querySelectorAll('[data-repeater-item]').forEach(item => {
                        item.classList.remove('gk-repeater-item-wrapper');
                        item.querySelector('.gk-repeater-item-label')?.remove();
                        item.querySelector('.gk-repeater-item-controls')?.remove();
                        item.querySelectorAll('[data-repeater-sub]').forEach(subEl => {
                            subEl.contentEditable = false;
                            subEl.classList.remove('gk-editing');
                            if (subEl._inputHandler) subEl.removeEventListener('input', subEl._inputHandler);
                            if (subEl._focusHandler) subEl.removeEventListener('focus', subEl._focusHandler);
                            if (subEl._blurHandler) subEl.removeEventListener('blur', subEl._blurHandler);
                            if (subEl._keyHandler) subEl.removeEventListener('keydown', subEl._keyHandler);
                            // Clean up image wrappers
                            if (subEl.tagName === 'IMG' && subEl.parentElement?.classList.contains('gk-image-wrapper')) {
                                const wrapper = subEl.parentElement;
                                wrapper.querySelector('.gk-image-overlay')?.remove();
                                wrapper.querySelector('.gk-image-badge')?.remove();
                                wrapper.parentNode.insertBefore(subEl, wrapper);
                                wrapper.remove();
                            }
                        });
                    });
                    el.querySelector('.gk-repeater-add-btn')?.remove();
                },

                addRepeaterItem(el) {
                    const templateHtml = el.dataset.repeaterTemplate;
                    if (!templateHtml) {
                        // Clone the last item as template
                        const items = el.querySelectorAll('[data-repeater-item]');
                        if (items.length === 0) { this.toast('No template for new items', 'error'); return; }
                        const lastItem = items[items.length - 1];
                        const clone = lastItem.cloneNode(true);
                        // Reset content in clone
                        clone.querySelectorAll('[data-repeater-sub]').forEach(subEl => {
                            const subType = subEl.dataset.repeaterSubType || 'text';
                            if (subType === 'image') {
                                if (subEl.tagName === 'IMG') subEl.src = 'https://placehold.co/400x300/e2e8f0/94a3b8?text=New+Image';
                            } else {
                                subEl.textContent = subEl.dataset.repeaterDefault || 'New item';
                            }
                        });
                        // Clean up editing artifacts
                        clone.classList.remove('gk-repeater-item-wrapper');
                        clone.querySelector('.gk-repeater-item-label')?.remove();
                        clone.querySelector('.gk-repeater-item-controls')?.remove();
                        clone.querySelectorAll('.gk-image-wrapper').forEach(w => {
                            const img = w.querySelector('img');
                            if (img) { w.parentNode.insertBefore(img, w); w.remove(); }
                        });
                        clone.querySelectorAll('.gk-image-overlay, .gk-image-badge').forEach(x => x.remove());

                        const addBtn = el.querySelector('.gk-repeater-add-btn');
                        if (addBtn) el.insertBefore(clone, addBtn);
                        else el.appendChild(clone);
                    } else {
                        const temp = document.createElement('div');
                        temp.innerHTML = templateHtml;
                        const newItem = temp.firstElementChild;
                        const addBtn = el.querySelector('.gk-repeater-add-btn');
                        if (addBtn) el.insertBefore(newItem, addBtn);
                        else el.appendChild(newItem);
                    }

                    // Re-init editing
                    this.disableRepeaterEditing(el);
                    this.enableRepeaterEditing(el);
                    this.syncRepeaterChanges(el);
                    this.toast('Item added', 'success');
                },

                removeRepeaterItem(el, index) {
                    const items = el.querySelectorAll('[data-repeater-item]');
                    if (items.length <= 1) { this.toast('Cannot remove the last item', 'error'); return; }
                    if (items[index]) items[index].remove();
                    this.disableRepeaterEditing(el);
                    this.enableRepeaterEditing(el);
                    this.syncRepeaterChanges(el);
                    this.toast('Item removed', 'success');
                },

                uploadRepeaterImage(repeaterEl, imgEl) {
                    const input = document.getElementById('gk-image-upload');
                    input.onchange = async () => {
                        const file = input.files[0]; if (!file) return;
                        const formData = new FormData();
                        formData.append('image', file); formData.append('_token', this.csrfToken);
                        try {
                            const res = await fetch('/api/cms/upload-image', { method: 'POST', body: formData });
                            const data = await res.json();
                            if (data.url) {
                                if (imgEl.tagName === 'IMG') imgEl.src = data.url;
                                else imgEl.style.backgroundImage = 'url(' + data.url + ')';
                                this.syncRepeaterChanges(repeaterEl);
                                this.toast('Image updated', 'success');
                            }
                        } catch (err) { this.toast('Upload failed', 'error'); }
                        input.value = '';
                    };
                    input.click();
                },

                getRepeaterState(el) {
                    const items = [];
                    el.querySelectorAll('[data-repeater-item]').forEach(item => {
                        const itemData = {};
                        item.querySelectorAll('[data-repeater-sub]').forEach(subEl => {
                            const subKey = subEl.dataset.repeaterSub;
                            const subType = subEl.dataset.repeaterSubType || 'text';
                            if (subType === 'image') {
                                let src = subEl.tagName === 'IMG' ? subEl.getAttribute('src') : subEl.style.backgroundImage;
                                const storageMatch = (src || '').match(/\/storage\/(.+?)(?:\)|"|\'|$)/);
                                itemData[subKey] = storageMatch ? 'storage/' + storageMatch[1] : src;                            } else {
                                itemData[subKey] = subEl.innerHTML;
                            }
                        });
                        items.push(itemData);
                    });
                    return items;
                },

                syncRepeaterChanges(el) {
                    const key = el.dataset.field;
                    this.changes[key] = this.getRepeaterState(el);
                    el.classList.add('gk-field-changed');
                    this.updateStatus();
                },

                // ═══════════════════════════════════════════════════════════
                // BUTTON EDITOR
                // ═══════════════════════════════════════════════════════════
                openButtonEditor(btn, groupKey) {
                    const rect = btn.getBoundingClientRect();
                    const isGroup = !!groupKey;
                    const key = isGroup ? groupKey : btn.dataset.field;
                    const index = parseInt(btn.dataset.buttonIndex || '0');

                    const popover = this.createPopover('Edit Button', rect);
                    const body = popover.querySelector('.gk-popover-body');

                    // Text
                    const textLabel = document.createElement('label');
                    textLabel.textContent = 'Button Text';
                    body.appendChild(textLabel);
                    const textInput = document.createElement('input');
                    textInput.type = 'text';
                    textInput.value = btn.textContent.trim();
                    body.appendChild(textInput);

                    // Link
                    const linkLabel = document.createElement('label');
                    linkLabel.textContent = 'Link URL';
                    body.appendChild(linkLabel);
                    const linkInput = document.createElement('input');
                    linkInput.type = 'url';
                    linkInput.value = btn.getAttribute('href') || '#';
                    body.appendChild(linkInput);

                    // Style
                    const styleLabel = document.createElement('label');
                    styleLabel.textContent = 'Button Style';
                    body.appendChild(styleLabel);
                    const styleGrid = document.createElement('div');
                    styleGrid.className = 'gk-style-options';
                    Object.entries(this.buttonStyles).forEach(([styleName, styleData]) => {
                        const opt = document.createElement('div');
                        opt.className = 'gk-style-option ' + styleData.classes;
                        if (styleName === (btn.dataset.buttonStyle || 'primary')) opt.classList.add('selected');
                        opt.textContent = styleData.label;
                        opt.onclick = () => {
                            styleGrid.querySelectorAll('.gk-style-option').forEach(o => o.classList.remove('selected'));
                            opt.classList.add('selected');
                            opt._styleName = styleName;
                        };
                        opt._styleName = styleName;
                        styleGrid.appendChild(opt);
                    });
                    body.appendChild(styleGrid);

                    // Footer
                    const footer = popover.querySelector('.gk-popover-footer');
                    const applyBtn = document.createElement('button');
                    applyBtn.className = 'gk-btn-apply';
                    applyBtn.textContent = 'Apply';
                    applyBtn.onclick = () => {
                        btn.textContent = textInput.value;
                        btn.setAttribute('href', linkInput.value);
                        const selectedStyle = styleGrid.querySelector('.selected');
                        if (selectedStyle) {
                            const newStyleName = selectedStyle._styleName;
                            btn.dataset.buttonStyle = newStyleName;
                            if (this.buttonStyles[newStyleName]) {
                                btn.className = this.buttonStyles[newStyleName].classes;
                            }
                        }
                        this.syncButtonChanges(key, isGroup);
                        this.closePopover();
                    };
                    footer.appendChild(applyBtn);

                    if (isGroup) {
                        const removeBtn = document.createElement('button');
                        removeBtn.className = 'gk-btn-remove';
                        removeBtn.textContent = 'Remove';
                        removeBtn.onclick = () => {
                            btn.remove();
                            this.syncButtonChanges(key, true);
                            this.closePopover();
                        };
                        footer.appendChild(removeBtn);
                    }
                },

                addButtonToGroup(groupEl) {
                    const key = groupEl.dataset.field;
                    const defaultStyle = Object.keys(this.buttonStyles)[0] || 'primary';
                    const styleClasses = this.buttonStyles[defaultStyle]?.classes || '';
                    const newBtn = document.createElement('a');
                    newBtn.href = '#';
                    newBtn.textContent = 'New Button';
                    newBtn.dataset.buttonIndex = groupEl.querySelectorAll('[data-button-index]').length;
                    newBtn.dataset.buttonStyle = defaultStyle;
                    newBtn.className = styleClasses;
                    newBtn.classList.add('gk-editable-active');
                    newBtn.addEventListener('click', newBtn._clickHandler = (e) => {
                        e.preventDefault(); e.stopPropagation();
                        this.openButtonEditor(newBtn, key);
                    });
                    const addBtn = groupEl.querySelector('.gk-add-button');
                    if (addBtn) groupEl.insertBefore(newBtn, addBtn);
                    else groupEl.appendChild(newBtn);
                    this.syncButtonChanges(key, true);
                },

                syncButtonChanges(key, isGroup) {
                    if (isGroup) {
                        const groupEl = document.querySelector('[data-field="' + key + '"]');
                        if (!groupEl) return;
                        const buttons = [];
                        groupEl.querySelectorAll('[data-button-index]').forEach(btn => {
                            buttons.push({
                                text: btn.textContent.trim(),
                                link: btn.getAttribute('href') || '#',
                                style: btn.dataset.buttonStyle || 'primary',
                                visible: btn.style.display !== 'none'
                            });
                        });
                        this.changes[key] = buttons;
                    } else {
                        const btn = document.querySelector('[data-field="' + key + '"]');
                        if (!btn) return;
                        this.changes[key] = {
                            text: btn.textContent.trim(),
                            link: btn.getAttribute('href') || '#',
                            style: btn.dataset.buttonStyle || 'primary',
                            visible: true
                        };
                    }
                    this.updateStatus();
                },

                // ═══════════════════════════════════════════════════════════
                // BACKGROUND EDITOR (Enhanced with Overlay & Gradient)
                // ═══════════════════════════════════════════════════════════

                // Helper: Parse rgba string to {hex, opacity}
                parseRgba(str) {
                    if (!str) return { hex: '#000000', opacity: 1 };
                    if (str.startsWith('#')) {
                        if (str.length === 9) {
                            const a = parseInt(str.slice(7, 9), 16) / 255;
                            return { hex: str.slice(0, 7), opacity: Math.round(a * 100) / 100 };
                        }
                        return { hex: str.length >= 7 ? str.slice(0, 7) : str, opacity: 1 };
                    }
                    const m = str.match(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(?:,\s*([\d.]+))?\s*\)/);
                    if (m) {
                        const hex = '#' + [m[1], m[2], m[3]].map(x => parseInt(x).toString(16).padStart(2, '0')).join('');
                        return { hex, opacity: m[4] !== undefined ? parseFloat(m[4]) : 1 };
                    }
                    return { hex: this.rgbToHex(str), opacity: 1 };
                },

                // Helper: Build rgba string from hex + opacity
                buildRgba(hex, opacity) {
                    const r = parseInt(hex.slice(1, 3), 16);
                    const g = parseInt(hex.slice(3, 5), 16);
                    const b = parseInt(hex.slice(5, 7), 16);
                    return `rgba(${r}, ${g}, ${b}, ${opacity})`;
                },

                // Helper: Build gradient CSS string
                buildGradient(type, color1, color2, angle) {
                    if (type === 'radial') return `radial-gradient(circle, ${color1}, ${color2})`;
                    return `linear-gradient(${angle}deg, ${color1}, ${color2})`;
                },

                // Helper: Create an RGBA color picker group
                createRgbaColorPicker(container, initialColor, initialOpacity, onChange) {
                    const rgbaRow = document.createElement('div');
                    rgbaRow.className = 'gk-rgba-row';
                    const colorInput = document.createElement('input');
                    colorInput.type = 'color';
                    colorInput.value = initialColor || '#000000';
                    const hexInput = document.createElement('input');
                    hexInput.type = 'text';
                    hexInput.value = initialColor || '#000000';
                    hexInput.placeholder = '#000000';
                    rgbaRow.appendChild(colorInput);
                    rgbaRow.appendChild(hexInput);
                    container.appendChild(rgbaRow);

                    const opacityRow = document.createElement('div');
                    opacityRow.className = 'gk-opacity-row';
                    const opLabel = document.createElement('label');
                    opLabel.textContent = 'Opacity';
                    const opSlider = document.createElement('input');
                    opSlider.type = 'range'; opSlider.min = '0'; opSlider.max = '100'; opSlider.value = Math.round((initialOpacity ?? 1) * 100);
                    const opVal = document.createElement('span');
                    opVal.textContent = opSlider.value + '%';
                    opacityRow.appendChild(opLabel);
                    opacityRow.appendChild(opSlider);
                    opacityRow.appendChild(opVal);
                    container.appendChild(opacityRow);

                    const fire = () => onChange(colorInput.value, parseInt(opSlider.value) / 100);
                    colorInput.oninput = () => { hexInput.value = colorInput.value; fire(); };
                    hexInput.oninput = () => {
                        if (/^#[0-9a-fA-F]{6}$/.test(hexInput.value)) { colorInput.value = hexInput.value; fire(); }
                    };
                    opSlider.oninput = () => { opVal.textContent = opSlider.value + '%'; fire(); };

                    return { colorInput, hexInput, opSlider, opVal };
                },

                // Helper: Create solid/gradient toggle + controls
                createColorModeSection(container, opts) {
                    const { initialType, initialSolid, initialGradient, onUpdate, label } = opts;
                    const type = initialType || 'solid';

                    // Type toggle
                    const toggleWrap = document.createElement('div');
                    toggleWrap.className = 'gk-color-type-toggle';
                    const solidBtn = document.createElement('button');
                    solidBtn.textContent = 'Solid';
                    if (type === 'solid') solidBtn.classList.add('active');
                    const gradBtn = document.createElement('button');
                    gradBtn.textContent = 'Gradient';
                    if (type === 'gradient') gradBtn.classList.add('active');
                    toggleWrap.appendChild(solidBtn);
                    toggleWrap.appendChild(gradBtn);
                    container.appendChild(toggleWrap);

                    // Solid panel
                    const solidPanel = document.createElement('div');
                    solidPanel.className = 'gk-bg-tab-content' + (type === 'solid' ? ' active' : '');
                    const solidParsed = this.parseRgba(initialSolid || '');
                    const solidPicker = this.createRgbaColorPicker(solidPanel, solidParsed.hex, solidParsed.opacity, (hex, op) => {
                        onUpdate('solid', { solid: this.buildRgba(hex, op) });
                    });
                    container.appendChild(solidPanel);

                    // Gradient panel
                    const gradPanel = document.createElement('div');
                    gradPanel.className = 'gk-bg-tab-content' + (type === 'gradient' ? ' active' : '');
                    const gradControls = document.createElement('div');
                    gradControls.className = 'gk-gradient-controls';

                    const g = initialGradient || {};
                    const gc1 = this.parseRgba(g.color1 || 'rgba(0,0,0,0.7)');
                    const gc2 = this.parseRgba(g.color2 || 'rgba(0,0,0,0)');

                    // Color 1
                    const c1Label = document.createElement('label');
                    c1Label.textContent = 'Color 1';
                    gradControls.appendChild(c1Label);
                    let gradColor1 = g.color1 || 'rgba(0,0,0,0.7)';
                    let gradColor2 = g.color2 || 'rgba(0,0,0,0)';
                    let gradAngle = g.angle ?? 180;
                    let gradType = g.type || 'linear';

                    this.createRgbaColorPicker(gradControls, gc1.hex, gc1.opacity, (hex, op) => {
                        gradColor1 = this.buildRgba(hex, op);
                        updateGradPreview();
                        onUpdate('gradient', { gradient: { color1: gradColor1, color2: gradColor2, angle: gradAngle, type: gradType } });
                    });

                    // Color 2
                    const c2Label = document.createElement('label');
                    c2Label.textContent = 'Color 2';
                    gradControls.appendChild(c2Label);
                    this.createRgbaColorPicker(gradControls, gc2.hex, gc2.opacity, (hex, op) => {
                        gradColor2 = this.buildRgba(hex, op);
                        updateGradPreview();
                        onUpdate('gradient', { gradient: { color1: gradColor1, color2: gradColor2, angle: gradAngle, type: gradType } });
                    });

                    // Gradient type
                    const typeRow = document.createElement('div');
                    typeRow.className = 'gk-gradient-type-row';
                    const typeLabel = document.createElement('label');
                    typeLabel.textContent = 'Type';
                    typeLabel.style.minWidth = '40px';
                    const typeSelect = document.createElement('select');
                    ['linear', 'radial'].forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t; opt.textContent = t.charAt(0).toUpperCase() + t.slice(1);
                        if (t === gradType) opt.selected = true;
                        typeSelect.appendChild(opt);
                    });
                    typeSelect.onchange = () => {
                        gradType = typeSelect.value;
                        angleRow.style.display = gradType === 'linear' ? 'flex' : 'none';
                        updateGradPreview();
                        onUpdate('gradient', { gradient: { color1: gradColor1, color2: gradColor2, angle: gradAngle, type: gradType } });
                    };
                    typeRow.appendChild(typeLabel);
                    typeRow.appendChild(typeSelect);
                    gradControls.appendChild(typeRow);

                    // Angle
                    const angleRow = document.createElement('div');
                    angleRow.className = 'gk-gradient-angle-row';
                    angleRow.style.display = gradType === 'linear' ? 'flex' : 'none';
                    const angleLabel = document.createElement('label');
                    angleLabel.textContent = 'Angle';
                    angleLabel.style.minWidth = '40px';
                    const angleSlider = document.createElement('input');
                    angleSlider.type = 'range'; angleSlider.min = '0'; angleSlider.max = '360'; angleSlider.value = gradAngle;
                    const angleVal = document.createElement('span');
                    angleVal.textContent = gradAngle + '°';
                    angleSlider.oninput = () => {
                        gradAngle = parseInt(angleSlider.value);
                        angleVal.textContent = gradAngle + '°';
                        updateGradPreview();
                        onUpdate('gradient', { gradient: { color1: gradColor1, color2: gradColor2, angle: gradAngle, type: gradType } });
                    };
                    angleRow.appendChild(angleLabel);
                    angleRow.appendChild(angleSlider);
                    angleRow.appendChild(angleVal);
                    gradControls.appendChild(angleRow);

                    // Preview
                    const gradPreview = document.createElement('div');
                    gradPreview.className = 'gk-gradient-preview';
                    gradControls.appendChild(gradPreview);
                    const updateGradPreview = () => {
                        gradPreview.style.background = this.buildGradient(gradType, gradColor1, gradColor2, gradAngle);
                    };
                    updateGradPreview();

                    gradPanel.appendChild(gradControls);
                    container.appendChild(gradPanel);

                    // Toggle logic
                    let currentType = type;
                    solidBtn.onclick = () => {
                        currentType = 'solid';
                        solidBtn.classList.add('active'); gradBtn.classList.remove('active');
                        solidPanel.classList.add('active'); gradPanel.classList.remove('active');
                        const hex = solidPicker.colorInput.value;
                        const op = parseInt(solidPicker.opSlider.value) / 100;
                        onUpdate('solid', { solid: this.buildRgba(hex, op) });
                    };
                    gradBtn.onclick = () => {
                        currentType = 'gradient';
                        gradBtn.classList.add('active'); solidBtn.classList.remove('active');
                        gradPanel.classList.add('active'); solidPanel.classList.remove('active');
                        onUpdate('gradient', { gradient: { color1: gradColor1, color2: gradColor2, angle: gradAngle, type: gradType } });
                    };

                    return { getCurrentType: () => currentType };
                },

                openBgEditor(el) {
                    const key = el.dataset.sectionBg;
                    const rect = el.getBoundingClientRect();
                    const badgeRect = el.querySelector('.gk-bg-badge')?.getBoundingClientRect() || rect;

                    const popover = this.createPopover(el.dataset.fieldLabel || 'Background', badgeRect);
                    popover.style.maxWidth = '420px';
                    popover.style.maxHeight = '80vh';
                    popover.style.overflow = 'auto';
                    const body = popover.querySelector('.gk-popover-body');

                    // Get existing overlay data
                    const existingOverlay = el.querySelector('.gk-section-overlay');
                    const existingOverlayBg = existingOverlay ? existingOverlay.style.background : '';

                    // ─── TABS ───
                    const tabs = document.createElement('div');
                    tabs.className = 'gk-bg-tabs';
                    const tabColor = document.createElement('button');
                    tabColor.textContent = 'Color'; tabColor.classList.add('active');
                    const tabImage = document.createElement('button');
                    tabImage.textContent = 'Image';
                    const tabOverlay = document.createElement('button');
                    tabOverlay.textContent = 'Overlay';
                    tabs.appendChild(tabColor); tabs.appendChild(tabImage); tabs.appendChild(tabOverlay);
                    body.appendChild(tabs);

                    const panelColor = document.createElement('div');
                    panelColor.className = 'gk-bg-tab-content active';
                    const panelImage = document.createElement('div');
                    panelImage.className = 'gk-bg-tab-content';
                    const panelOverlay = document.createElement('div');
                    panelOverlay.className = 'gk-bg-tab-content';

                    // Tab switching
                    [tabColor, tabImage, tabOverlay].forEach((tab, i) => {
                        tab.onclick = () => {
                            [tabColor, tabImage, tabOverlay].forEach(t => t.classList.remove('active'));
                            [panelColor, panelImage, panelOverlay].forEach(p => p.classList.remove('active'));
                            tab.classList.add('active');
                            [panelColor, panelImage, panelOverlay][i].classList.add('active');
                        };
                    });

                    // ═══ COLOR TAB ═══
                    const colorLabel = document.createElement('label');
                    colorLabel.textContent = 'Background Color';
                    panelColor.appendChild(colorLabel);

                    // Get current bg color data
                    const currentBgData = this.changes[key] || {};
                    const currentColorType = currentBgData.colorType || 'solid';
                    const currentBgColor = el.style.backgroundColor || '';
                    const currentGradient = currentBgData.colorGradient || null;

                    let bgColorValue = currentBgColor;
                    let bgColorType = currentColorType;
                    let bgGradientData = currentGradient;

                    this.createColorModeSection(panelColor, {
                        initialType: currentColorType,
                        initialSolid: currentBgColor || '#ffffff',
                        initialGradient: currentGradient,
                        onUpdate: (type, data) => {
                            bgColorType = type;
                            if (type === 'solid') {
                                bgColorValue = data.solid;
                                bgGradientData = null;
                                el.style.background = '';
                                el.style.backgroundColor = data.solid;
                            } else {
                                bgGradientData = data.gradient;
                                bgColorValue = '';
                                el.style.backgroundColor = '';
                                el.style.background = this.buildGradient(data.gradient.type, data.gradient.color1, data.gradient.color2, data.gradient.angle);
                                // Preserve bg image if set
                                if (el.style.backgroundImage && el.style.backgroundImage !== 'none' && !el.style.backgroundImage.includes('gradient')) {
                                    // Re-apply image on top
                                }
                            }
                        }
                    });

                    body.appendChild(panelColor);

                    // ═══ IMAGE TAB ═══
                    const imgLabel = document.createElement('label');
                    imgLabel.textContent = 'Background Image';
                    panelImage.appendChild(imgLabel);

                    // Current image preview
                    const currentBgImg = el.style.backgroundImage;
                    if (currentBgImg && currentBgImg !== 'none') {
                        const previewDiv = document.createElement('div');
                        previewDiv.style.cssText = 'width:100%;height:80px;border-radius:6px;border:1px solid #e2e8f0;margin-bottom:8px;background-size:cover;background-position:center;';
                        previewDiv.style.backgroundImage = currentBgImg;
                        panelImage.appendChild(previewDiv);
                    }

                    const uploadBtn = document.createElement('button');
                    uploadBtn.className = 'gk-btn-secondary';
                    uploadBtn.textContent = 'Upload Image';
                    uploadBtn.style.width = '100%';
                    uploadBtn.onclick = () => this.uploadBgImage(el, uploadBtn);
                    panelImage.appendChild(uploadBtn);

                    // Mode
                    const modeLabel = document.createElement('label');
                    modeLabel.textContent = 'Background Mode';
                    modeLabel.style.marginTop = '12px';
                    panelImage.appendChild(modeLabel);
                    const modeSelect = document.createElement('select');
                    ['cover', 'contain', 'repeat', 'fixed'].forEach(m => {
                        const opt = document.createElement('option');
                        opt.value = m; opt.textContent = m.charAt(0).toUpperCase() + m.slice(1);
                        if (m === (el.dataset.bgMode || 'cover')) opt.selected = true;
                        modeSelect.appendChild(opt);
                    });
                    modeSelect.onchange = () => {
                        el.dataset.bgMode = modeSelect.value;
                        this.applyBgMode(el, modeSelect.value);
                    };
                    panelImage.appendChild(modeSelect);

                    // Remove image button
                    const removeImgBtn = document.createElement('button');
                    removeImgBtn.className = 'gk-btn-remove';
                    removeImgBtn.textContent = 'Remove Image';
                    removeImgBtn.style.cssText = 'width:100%;margin-top:12px;';
                    removeImgBtn.onclick = () => {
                        el.style.backgroundImage = '';
                        this.toast('Background image removed', 'success');
                    };
                    panelImage.appendChild(removeImgBtn);

                    body.appendChild(panelImage);

                    // ═══ OVERLAY TAB ═══
                    const overlayLabel = document.createElement('label');
                    overlayLabel.textContent = 'Color Overlay';
                    panelOverlay.appendChild(overlayLabel);
                    const overlayDesc = document.createElement('p');
                    overlayDesc.textContent = 'Add a semi-transparent color layer on top of the background.';
                    overlayDesc.style.cssText = 'font-size:11px;color:#94a3b8;margin:-8px 0 8px 0;';
                    panelOverlay.appendChild(overlayDesc);

                    // Parse existing overlay
                    const overlayData = currentBgData.overlay || {};
                    let overlayType = overlayData.type || 'solid';
                    let overlaySolid = overlayData.solid || 'rgba(0, 0, 0, 0.5)';
                    let overlayGradient = overlayData.gradient || { color1: 'rgba(0,0,0,0.7)', color2: 'rgba(0,0,0,0)', angle: 180, type: 'linear' };

                    const applyOverlayToEl = () => {
                        let overlayEl = el.querySelector('.gk-section-overlay');
                        if (!overlayEl) {
                            overlayEl = document.createElement('div');
                            overlayEl.className = 'gk-section-overlay';
                            el.insertBefore(overlayEl, el.firstChild);
                        }
                        if (overlayType === 'solid') {
                            overlayEl.style.background = overlaySolid;
                        } else {
                            overlayEl.style.background = this.buildGradient(overlayGradient.type, overlayGradient.color1, overlayGradient.color2, overlayGradient.angle);
                        }
                    };

                    this.createColorModeSection(panelOverlay, {
                        initialType: overlayType,
                        initialSolid: overlaySolid,
                        initialGradient: overlayGradient,
                        onUpdate: (type, data) => {
                            overlayType = type;
                            if (type === 'solid') {
                                overlaySolid = data.solid;
                            } else {
                                overlayGradient = data.gradient;
                            }
                            applyOverlayToEl();
                        }
                    });

                    // Remove overlay button
                    const removeOverlayBtn = document.createElement('button');
                    removeOverlayBtn.className = 'gk-btn-remove';
                    removeOverlayBtn.textContent = 'Remove Overlay';
                    removeOverlayBtn.style.cssText = 'width:100%;margin-top:12px;';
                    removeOverlayBtn.onclick = () => {
                        const oel = el.querySelector('.gk-section-overlay');
                        if (oel) oel.remove();
                        overlayType = 'none';
                        overlaySolid = '';
                        overlayGradient = {};
                        this.toast('Overlay removed', 'success');
                    };
                    panelOverlay.appendChild(removeOverlayBtn);

                    body.appendChild(panelOverlay);

                    // ═══ FOOTER ═══
                    const footer = popover.querySelector('.gk-popover-footer');
                    const applyBtn = document.createElement('button');
                    applyBtn.className = 'gk-btn-apply';
                    applyBtn.textContent = 'Done';
                    applyBtn.onclick = () => {
                        this.syncBgChanges(el, {
                            colorType: bgColorType,
                            colorGradient: bgGradientData,
                            overlay: {
                                type: overlayType,
                                solid: overlaySolid,
                                gradient: overlayGradient
                            }
                        });
                        this.closePopover();
                    };
                    footer.appendChild(applyBtn);
                },

                uploadBgImage(el, btn) {
                    const input = document.getElementById('gk-bg-image-upload');
                    input.onchange = async () => {
                        const file = input.files[0]; if (!file) return;
                        btn.textContent = 'Uploading...'; btn.disabled = true;
                        const formData = new FormData();
                        formData.append('image', file); formData.append('_token', this.csrfToken);
                        try {
                            const res = await fetch('/api/cms/upload-image', { method: 'POST', body: formData });
                            const data = await res.json();
                            if (data.url) {
                                el.style.backgroundImage = 'url(' + data.url + ')';
                                el.style.backgroundSize = 'cover';
                                el.style.backgroundPosition = 'center';
                                this.syncBgChanges(el);
                                this.toast('Background updated', 'success');
                            }
                        } catch (err) { this.toast('Upload failed', 'error'); }
                        btn.textContent = 'Upload Image'; btn.disabled = false;
                        input.value = '';
                    };
                    input.click();
                },

                applyBgMode(el, mode) {
                    if (mode === 'cover') { el.style.backgroundSize = 'cover'; el.style.backgroundRepeat = 'no-repeat'; el.style.backgroundAttachment = ''; }
                    else if (mode === 'contain') { el.style.backgroundSize = 'contain'; el.style.backgroundRepeat = 'no-repeat'; el.style.backgroundAttachment = ''; }
                    else if (mode === 'repeat') { el.style.backgroundSize = 'auto'; el.style.backgroundRepeat = 'repeat'; el.style.backgroundAttachment = ''; }
                    else if (mode === 'fixed') { el.style.backgroundSize = 'cover'; el.style.backgroundRepeat = 'no-repeat'; el.style.backgroundAttachment = 'fixed'; }
                },

                syncBgChanges(el, extraData) {
                    const key = el.dataset.sectionBg;
                    let imageUrl = el.style.backgroundImage || '';
                    const urlMatch = imageUrl.match(/url\(["']?(.+?)["']?\)/);
                    if (urlMatch) imageUrl = urlMatch[1];
                    const storageMatch = imageUrl.match(/\/storage\/(.+)$/);
                    const imagePath = storageMatch ? storageMatch[1] : '';

                    const data = {
                        color: el.style.backgroundColor || '',
                        image: imagePath,
                        mode: el.dataset.bgMode || 'cover'
                    };

                    // Merge extra data (colorType, colorGradient, overlay)
                    if (extraData) {
                        if (extraData.colorType) data.colorType = extraData.colorType;
                        if (extraData.colorGradient) data.colorGradient = extraData.colorGradient;
                        if (extraData.overlay) data.overlay = extraData.overlay;
                    }

                    // If gradient background, store the gradient CSS
                    if (data.colorType === 'gradient' && data.colorGradient) {
                        data.color = ''; // Clear solid color
                        data.gradient = this.buildGradient(data.colorGradient.type, data.colorGradient.color1, data.colorGradient.color2, data.colorGradient.angle);
                    }

                    this.changes[key] = data;
                    el.classList.add('gk-field-changed');
                    this.updateStatus();
                },

                // ═══════════════════════════════════════════════════════════
                // POPOVER SYSTEM
                // ═══════════════════════════════════════════════════════════
                createPopover(title, anchorRect) {
                    this.closePopover();

                    const popover = document.createElement('div');
                    popover.className = 'gk-popover';
                    popover.id = 'gk-active-popover';

                    const header = document.createElement('div');
                    header.className = 'gk-popover-header';
                    header.innerHTML = '<span>' + title + '</span>';
                    const closeBtn = document.createElement('button');
                    closeBtn.innerHTML = '&times;';
                    closeBtn.onclick = () => this.closePopover();
                    header.appendChild(closeBtn);
                    popover.appendChild(header);

                    const body = document.createElement('div');
                    body.className = 'gk-popover-body';
                    popover.appendChild(body);

                    const footer = document.createElement('div');
                    footer.className = 'gk-popover-footer';
                    popover.appendChild(footer);

                    document.body.appendChild(popover);

                    // Position
                    const popRect = popover.getBoundingClientRect();
                    let top = anchorRect.bottom + 8;
                    let left = anchorRect.left;
                    if (top + popRect.height > window.innerHeight) top = anchorRect.top - popRect.height - 8;
                    if (left + popRect.width > window.innerWidth) left = window.innerWidth - popRect.width - 16;
                    if (left < 8) left = 8;
                    if (top < 50) top = 50;
                    popover.style.top = top + 'px';
                    popover.style.left = left + 'px';

                    this.activePopover = popover;
                    return popover;
                },

                closePopover() {
                    if (this.activePopover) {
                        this.activePopover.remove();
                        this.activePopover = null;
                    }
                },

                // ═══════════════════════════════════════════════════════════
                // IMAGE UPLOAD
                // ═══════════════════════════════════════════════════════════
                uploadImage(el) {
                    const input = document.getElementById('gk-image-upload');
                    input.onchange = async () => {
                        const file = input.files[0]; if (!file) return;
                        const formData = new FormData();
                        formData.append('image', file); formData.append('_token', this.csrfToken);
                        try {
                            const res = await fetch('/api/cms/upload-image', { method: 'POST', body: formData });
                            const data = await res.json();
                            if (data.path) {
                                const key = el.dataset.field;
                                if (el.tagName === 'IMG') {
                                    el.src = data.url;
                                } else {
                                    el.style.backgroundImage = 'url(' + data.url + ')';
                                }
                                this.changes[key] = 'storage/' + data.path;
                                el.classList.add('gk-field-changed');
                                this.updateStatus();
                                this.toast('Image updated', 'success');
                            }
                        } catch (err) { this.toast('Upload failed: ' + err.message, 'error'); }
                        input.value = '';
                    };
                    input.click();
                },

                // ═══════════════════════════════════════════════════════════
                // SAVE & DISCARD
                // ═══════════════════════════════════════════════════════════
                markChanged(el) {
                    const key = el.dataset.field;
                    if (key) {
                        this.changes[key] = el.innerHTML;
                        el.classList.add('gk-field-changed');
                        this.updateStatus();
                    }
                },

                updateStatus() {
                    const count = Object.keys(this.changes).length;
                    const saveBtn = document.getElementById('gk-btn-save');
                    saveBtn.disabled = count === 0;
                    document.getElementById('gk-editor-status').textContent = count > 0
                        ? count + ' change' + (count > 1 ? 's' : '') + ' pending'
                        : 'Editing mode — click on highlighted elements to edit';
                },

                async save() {
                    const saveBtn = document.getElementById('gk-btn-save');
                    saveBtn.textContent = 'Saving...'; saveBtn.disabled = true;

                    // Separate section bg changes from field changes
                    const fieldChanges = {};
                    const bgKeys = Array.from(document.querySelectorAll('[data-section-bg]')).map(el => el.dataset.sectionBg);

                    Object.entries(this.changes).forEach(([key, value]) => {
                        if (bgKeys.includes(key)) {
                            fieldChanges[key] = value;
                        } else {
                            // For text/textarea, strip to textContent if it's a simple text field
                            const el = document.querySelector('[data-field="' + key + '"]');
                            const type = el?.dataset.fieldType || 'text';
                            if (type === 'text') {
                                fieldChanges[key] = el ? el.textContent.trim() : value;
                            } else if (type === 'textarea') {
                                // Preserve basic formatting
                                fieldChanges[key] = el ? el.innerHTML : value;
                            } else {
                                fieldChanges[key] = value;
                            }
                        }
                    });

                    try {
                        const res = await fetch('/api/cms/pages/' + this.pageSlug + '/fields', {
                            method: 'PATCH',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
                            body: JSON.stringify({ fields: fieldChanges })
                        });
                        const data = await res.json();
                        if (res.ok) {
                            this.changes = {};
                            document.querySelectorAll('.gk-field-changed').forEach(el => el.classList.remove('gk-field-changed'));
                            this.updateStatus();
                            this.toast('Changes saved!', 'success');
                        } else {
                            this.toast('Save failed: ' + (data.message || 'Unknown error'), 'error');
                        }
                    } catch (err) {
                        this.toast('Save failed: ' + err.message, 'error');
                    }
                    saveBtn.textContent = 'Save Changes';
                    saveBtn.disabled = Object.keys(this.changes).length === 0;
                },

                discard() {
                    if (!confirm('Discard all changes?')) return;

                    // Restore original values
                    document.querySelectorAll('[data-field]').forEach(el => {
                        const key = el.dataset.field;
                        const type = el.dataset.fieldType || 'text';
                        if (this.originalValues[key] === undefined) return;

                        if (type === 'image') {
                            if (el.tagName === 'IMG') el.src = this.originalValues[key];
                            else el.style.backgroundImage = this.originalValues[key];
                        } else if (type === 'gallery') {
                            // Reload page for gallery discard (complex DOM restore)
                            location.reload(); return;
                        } else if (type === 'repeater') {
                            location.reload(); return;
                        } else if (type === 'color') {
                            const target = el.dataset.colorTarget || 'background-color';
                            if (target === 'color') el.style.color = this.originalValues[key];
                            else el.style.backgroundColor = this.originalValues[key];
                            el.dataset.colorValue = this.originalValues[key];
                        } else if (type === 'video') {
                            // Reload for video discard
                            location.reload(); return;
                        } else if (type === 'icon') {
                            // Reload for icon discard
                            location.reload(); return;
                        } else if (type === 'button' || type === 'button_group') {
                            // Reload for button discard
                            location.reload(); return;
                        } else {
                            el.innerHTML = this.originalValues[key];
                        }
                        el.classList.remove('gk-field-changed');
                    });

                    // Restore bg values
                    document.querySelectorAll('[data-section-bg]').forEach(el => {
                        const key = el.dataset.sectionBg;
                        if (this.originalBgValues[key]) {
                            el.style.backgroundColor = this.originalBgValues[key].color;
                            el.style.background = this.originalBgValues[key].background || '';
                            el.style.backgroundImage = this.originalBgValues[key].image;
                            // Restore overlay
                            const overlayEl = el.querySelector('.gk-section-overlay');
                            if (this.originalBgValues[key].overlayBg) {
                                if (overlayEl) {
                                    overlayEl.style.background = this.originalBgValues[key].overlayBg;
                                }
                            } else if (overlayEl) {
                                overlayEl.remove();
                            }
                        }
                        el.classList.remove('gk-field-changed');
                    });

                    this.changes = {};
                    this.updateStatus();
                    this.toast('Changes discarded', 'success');
                },

                // ═══════════════════════════════════════════════════════════
                // TOAST NOTIFICATIONS
                // ═══════════════════════════════════════════════════════════
                toast(msg, type) {
                    const existing = document.querySelector('.gk-toast');
                    if (existing) existing.remove();
                    const toast = document.createElement('div');
                    toast.className = 'gk-toast gk-' + type;
                    toast.textContent = msg;
                    document.body.appendChild(toast);
                    requestAnimationFrame(() => toast.classList.add('gk-show'));
                    setTimeout(() => { toast.classList.remove('gk-show'); setTimeout(() => toast.remove(), 300); }, 3000);
                },

                // ═══════════════════════════════════════════════════════════
                // CSS SIDEBAR
                // ═══════════════════════════════════════════════════════════
                cssTab: 'global',
                globalCss: '',
                pageCss: '',
                cssLoaded: false,

                toggleCssSidebar() {
                    const sidebar = document.getElementById('gk-css-sidebar');
                    const isOpen = sidebar.classList.contains('gk-open');
                    if (isOpen) {
                        this.closeCssSidebar();
                    } else {
                        this.closeThemePanel();
                        sidebar.classList.add('gk-open');
                        document.getElementById('gk-btn-css').classList.add('active');
                        if (!this.cssLoaded) this.loadCss();
                    }
                },

                closeCssSidebar() {
                    document.getElementById('gk-css-sidebar')?.classList.remove('gk-open');
                    document.getElementById('gk-btn-css')?.classList.remove('active');
                },

                async loadCss() {
                    try {
                        const res = await fetch('/api/cms/css?page=' + this.pageSlug, {
                            headers: { 'X-CSRF-TOKEN': this.csrfToken }
                        });
                        const data = await res.json();
                        this.globalCss = data.global_css || '';
                        this.pageCss = data.page_css || '';
                        this.cssLoaded = true;
                        const editor = document.getElementById('gk-css-editor');
                        editor.value = this.cssTab === 'global' ? this.globalCss : this.pageCss;
                    } catch (err) { this.toast('Failed to load CSS', 'error'); }
                },

                switchCssTab(tab, btn) {
                    this.cssTab = tab;
                    const editor = document.getElementById('gk-css-editor');
                    // Save current tab content
                    if (tab === 'global') {
                        this.pageCss = editor.value;
                        editor.value = this.globalCss;
                    } else {
                        this.globalCss = editor.value;
                        editor.value = this.pageCss;
                    }
                    // Update tab buttons
                    btn.parentElement.querySelectorAll('button').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                },

                async saveCss() {
                    const editor = document.getElementById('gk-css-editor');
                    if (this.cssTab === 'global') this.globalCss = editor.value;
                    else this.pageCss = editor.value;

                    try {
                        const res = await fetch('/api/cms/css', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
                            body: JSON.stringify({
                                page_slug: this.pageSlug,
                                global_css: this.globalCss,
                                page_css: this.pageCss
                            })
                        });
                        if (res.ok) {
                            this.toast('CSS saved!', 'success');
                            this.applyCssPreview();
                        } else {
                            this.toast('Failed to save CSS', 'error');
                        }
                    } catch (err) { this.toast('Failed to save CSS', 'error'); }
                },

                previewCss() {
                    const editor = document.getElementById('gk-css-editor');
                    if (this.cssTab === 'global') this.globalCss = editor.value;
                    else this.pageCss = editor.value;
                    this.applyCssPreview();
                    this.toast('CSS preview applied', 'success');
                },

                applyCssPreview() {
                    let styleEl = document.getElementById('gk-preview-css');
                    if (!styleEl) {
                        styleEl = document.createElement('style');
                        styleEl.id = 'gk-preview-css';
                        document.head.appendChild(styleEl);
                    }
                    styleEl.textContent = (this.globalCss || '') + '\n' + (this.pageCss || '');
                },

                // ═══════════════════════════════════════════════════════════
                // THEME EDITOR PANEL
                // ═══════════════════════════════════════════════════════════
                toggleThemePanel() {
                    const panel = document.getElementById('gk-theme-panel');
                    const isOpen = panel.classList.contains('gk-open');
                    if (isOpen) {
                        this.closeThemePanel();
                    } else {
                        this.closeCssSidebar();
                        panel.classList.add('gk-open');
                        document.getElementById('gk-btn-theme').classList.add('active');
                        // Sync color inputs
                        const primary = document.getElementById('gk-theme-primary');
                        const primaryHex = document.getElementById('gk-theme-primary-hex');
                        const secondary = document.getElementById('gk-theme-secondary');
                        const secondaryHex = document.getElementById('gk-theme-secondary-hex');
                        primary.oninput = () => { primaryHex.value = primary.value; };
                        primaryHex.oninput = () => { if (/^#[0-9a-fA-F]{6}$/.test(primaryHex.value)) primary.value = primaryHex.value; };
                        secondary.oninput = () => { secondaryHex.value = secondary.value; };
                        secondaryHex.oninput = () => { if (/^#[0-9a-fA-F]{6}$/.test(secondaryHex.value)) secondary.value = secondaryHex.value; };

                        // Sync header/footer bg color inputs
                        const headerBg = document.getElementById('gk-theme-header-bg');
                        const headerBgHex = document.getElementById('gk-theme-header-bg-hex');
                        const footerBg = document.getElementById('gk-theme-footer-bg');
                        const footerBgHex = document.getElementById('gk-theme-footer-bg-hex');
                        headerBg.oninput = () => { headerBgHex.value = headerBg.value; };
                        headerBgHex.oninput = () => { if (/^#[0-9a-fA-F]{6}$/.test(headerBgHex.value)) headerBg.value = headerBgHex.value; };
                        footerBg.oninput = () => { footerBgHex.value = footerBg.value; };
                        footerBgHex.oninput = () => { if (/^#[0-9a-fA-F]{6}$/.test(footerBgHex.value)) footerBg.value = footerBgHex.value; };

                        // Initialize logo upload handler
                        this.initLogoUpload();
                    }
                },

                closeThemePanel() {
                    document.getElementById('gk-theme-panel')?.classList.remove('gk-open');
                    document.getElementById('gk-btn-theme')?.classList.remove('active');
                },

                async saveTheme() {
                    const formData = new FormData();
                    formData.append('theme_primary_color', document.getElementById('gk-theme-primary').value);
                    formData.append('theme_secondary_color', document.getElementById('gk-theme-secondary').value);
                    formData.append('theme_header_bg', document.getElementById('gk-theme-header-bg').value);
                    formData.append('theme_footer_bg', document.getElementById('gk-theme-footer-bg').value);
                    formData.append('theme_font_heading', document.getElementById('gk-theme-font-heading').value);
                    formData.append('theme_font_body', document.getElementById('gk-theme-font-body').value);
                    formData.append('logo', document.getElementById('gk-theme-logo').value);
                    formData.append('show_tagline_header', document.getElementById('gk-theme-show-tagline').checked ? '1' : '0');

                    // If a logo file was selected via the upload input, send it as a file
                    const logoUploadInput = document.getElementById('gk-logo-upload');
                    if (logoUploadInput && logoUploadInput.files.length > 0) {
                        formData.append('logo', logoUploadInput.files[0]);
                    }

                    try {
                        const res = await fetch('/api/cms/theme', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': this.csrfToken },
                            body: formData
                        });
                        if (res.ok) {
                            this.toast('Theme settings saved!', 'success');
                            // Apply CSS variables live
                            const pc = document.getElementById('gk-theme-primary').value;
                            const sc = document.getElementById('gk-theme-secondary').value;
                            const hb = document.getElementById('gk-theme-header-bg').value;
                            const fb = document.getElementById('gk-theme-footer-bg').value;
                            const fh = document.getElementById('gk-theme-font-heading').value;
                            const fbo = document.getElementById('gk-theme-font-body').value;
                            document.documentElement.style.setProperty('--color-primary', pc);
                            document.documentElement.style.setProperty('--color-secondary', sc);
                            document.documentElement.style.setProperty('--header-bg', hb);
                            document.documentElement.style.setProperty('--footer-bg', fb);
                            document.documentElement.style.setProperty('--font-heading', "'" + fh + "', sans-serif");
                            document.documentElement.style.setProperty('--font-body', "'" + fbo + "', sans-serif");
                        } else {
                            this.toast('Failed to save theme', 'error');
                        }
                    } catch (err) { this.toast('Failed to save theme', 'error'); }
                },

                initLogoUpload() {
                    const logoInput = document.getElementById('gk-logo-upload');
                    if (!logoInput) return;
                    logoInput.addEventListener('change', async (e) => {
                        const file = e.target.files[0];
                        if (!file) return;
                        const formData = new FormData();
                        formData.append('image', file);
                        formData.append('folder', 'branding');
                        try {
                            const res = await fetch('/api/cms/upload-image', {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': this.csrfToken },
                                body: formData
                            });
                            const data = await res.json();
                            if (data.path) {
                                document.getElementById('gk-theme-logo').value = data.path;
                                const preview = document.getElementById('gk-logo-preview');
                                preview.src = '/storage/' + data.path;
                                preview.style.display = 'block';
                                this.toast('Logo uploaded! Click Save to apply.', 'success');
                            }
                        } catch (err) { this.toast('Logo upload failed', 'error'); }
                        logoInput.value = '';
                    });
                }
            };

            // Initialize
            GKEditor.init();

            // Close popover when clicking outside
            document.addEventListener('click', (e) => {
                if (GKEditor.activePopover && !GKEditor.activePopover.contains(e.target) &&
                    !e.target.closest('.gk-bg-badge') && !e.target.closest('.gk-image-badge') &&
                    !e.target.closest('.gk-video-badge') && !e.target.closest('.gk-icon-badge') &&
                    !e.target.closest('.gk-color-badge') && !e.target.closest('.gk-gallery-item-badge') &&
                    !e.target.closest('[data-button-index]') && !e.target.closest('[data-field-type="button"]')) {
                    GKEditor.closePopover();
                }
            });
        </script>
    @endif
@endauth
