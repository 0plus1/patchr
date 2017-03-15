<?php
/*
 * Patchr default configuration
 * Information about these value can be found in the documentation
 *
 * @copyright 2014-2017 Davide Gaido
 */
return [
    'db' => array(
        'host' => 'localhost',
        'user'  => 'patchr',
        'password' => 'secret',
        'database' => 'awesome_app',
        'table'     => 'patchr_dbpatches',
        'timeout'     => 500,
        'multiple'     => false,
    ),
    'naming' => array(
        'patches_dir' => storage_path().'/patchr',
        'prefix'  => 'patch-',
        'digits' => 5,
        'extension' => '.sql',
    ),
    'adv' => array(
        'skip_patches' => [],
    ),
];