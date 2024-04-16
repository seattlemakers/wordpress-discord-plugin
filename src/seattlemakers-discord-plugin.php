<?php
/*
Plugin Name: Seattle Makers Discord Sync
Plugin URI: http://github.com/seattlemakers/wordpress-discord-plugin
Description: Synchronizes Seattle Makers membership roles with Discord roles
Version: 0.1.0
Author: Jacob Buys
Author URI: http://arithmeticulo.us
License: MIT
*/
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin Name: My Plugin
 * ...
 */

spl_autoload_register(function ($class) {
    $prefix = 'SeattleMakers\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative_class = substr($class, strlen($prefix));

    $relative_class_path = strtolower(str_replace('\\', '/', str_replace('_', '-', $relative_class)));
    $dir = dirname($relative_class_path);
    if ($dir === ".") {
        $dir = "";
    } else {
        $dir = $dir . "/";
    }
    $file = sprintf("%sincludes/%sclass-%s.php", plugin_dir_path(__FILE__), $dir, basename($relative_class_path));

    if (file_exists($file)) {
        require_once $file;
    }
});

new SeattleMakers\Discord_Role_Sync(__FILE__);
