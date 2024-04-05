<?php
/*
Plugin Name: Seattle Makers Discord Sync
Plugin URI: http://github.com/seattlemakers/discord-plugin
Description: Synchronizes Seattle Makers membership roles with Discord roles
Version: 0.1.0
Author: Jacob Buys
Author URI: http://arithmeticulo.us
License: MIT
*/
if (!defined('ABSPATH')) {
    exit;
}

require_once 'includes/class-discord-role-sync.php';

new SeattleMakers\Discord_Role_Sync(__FILE__);
