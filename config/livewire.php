<?php

return [
    'component_locations' => [
        resource_path('views/components'),
        resource_path('views/livewire'),
    ],
    'component_namespaces' => [
        'layouts' => resource_path('views/layouts'),
        'pages' => resource_path('views/pages'),
    ],
    'component_layout' => 'layouts::app',
    'component_placeholder' => null,
    'make_command' => [
        'type' => 'class',
        'emoji' => false,
        'with' => ['js' => false, 'css' => false, 'test' => false],
    ],

    // Le namespace historique est conservé pour éviter tout déplacement de code métier.
    'class_namespace' => 'App\\Http\\Livewire',
    'class_path' => app_path('Http/Livewire'),
    'view_path' => resource_path('views/livewire'),
    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK'),
        'rules' => null,
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4', 'mov', 'avi', 'wmv',
            'mp3', 'm4a', 'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],
    'render_on_redirect' => false,
    'legacy_model_binding' => false,
    'inject_assets' => false,
    'navigate' => ['show_progress_bar' => true, 'progress_bar_color' => '#1e3a8a'],
    'inject_morph_markers' => true,
    'smart_wire_keys' => true,
    'pagination_theme' => 'tailwind',
    'release_token' => 'cabinet-app-v2',
    'csp_safe' => false,
    'payload' => [
        'max_size' => 1024 * 1024,
        'max_nesting_depth' => 10,
        'max_calls' => 50,
        'max_components' => 200,
    ],
];
