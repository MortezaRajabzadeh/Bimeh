<?php

return [
    /**
     * Will disable microscope if set to false.
     */
    'is_enabled' => env('MICROSCOPE_ENABLED', false),

    /**
     * Avoids auto-fix if is set to true.
     */
    'no_fix' => false,

    /**
     * An array of patterns relative to base_path that should be ignored when reporting.
     */
    'ignore' => [
        // 'nova*'
    ],

    /**
     * You can turn off the extra variable passing detection, which performs some logs.
     */
    'log_unused_view_vars' => true,

    /**
     * An array of root namespaces to be ignored while scanning for errors.
     */
    'ignored_namespaces' => [
        // 'Laravel\\Nova\\',
        // 'Laravel\\Nova\\Tests\\'
    ],

    /**
     * By default, we only process the first 2500 characters of a file to find the "class" keyword.
     * So, if you have a lot of use statements or very big docblocks for your classes so that
     * the "class" falls deep down, you may increase this value, so that it searches deeper.
     */
    'class_search_buffer' => 2500,

    /**
     * The doc blocks in your controllers are generated based on this template.
     * You can change this template to customize the check:action_comments results.
     */
    'action_comment_template' => 'microscope_package::actions_comment',

    /**
     * If a non-default route file is not being scanned,
     * you can manually add its path here, as below:.
     */
    'additional_route_files' => [
        // app()->basePath('some_folder/my_route.php''),
    ],

    /**
     * If you have additional config files, you can list their folders here.
     */
    'additional_config_paths' => [
        // 'my/folder/configs'
    ],

    /**
     * If you have additional composer.json files, you can list their file paths here.
     */
    'additional_composer_paths' => [
        //
    ],
];
