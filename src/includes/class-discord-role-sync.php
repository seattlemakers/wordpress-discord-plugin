<?php

namespace SeattleMakers;

require_once 'discord/class-client.php';
require_once 'class-user-meta-token-store.php';

class Discord_Role_Sync
{
    private const QUERY_VAR = 'sm_discord';
    private const PAGE_NAME = "discord";

    public const DISCORD_CLIENT_ID_KEY = 'discord_client_id';
    public const DISCORD_CLIENT_SECRET_KEY = 'discord_client_secret';

    private string $plugin_file_path;
    private string $plugin_dir_path;

    public function __construct(string $plugin_file_path)
    {
        $this->plugin_file_path = $plugin_file_path;
        $this->plugin_dir_path = plugin_dir_path($this->plugin_file_path);

        register_activation_hook($plugin_file_path, array($this, 'activate'));
        register_deactivation_hook($plugin_file_path, array($this, 'deactivate'));

        add_action('init', array($this, 'add_rewrite_rule'));
        add_action('admin_init', array($this, 'admin_init'));

        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_oauth_callback'));

        // TODO: hook into user metadata update and actually update metadata in discord

        add_shortcode('discord_link', array($this, 'render_discord_link'));
    }

    public function admin_init(): void
    {
        if (!is_admin()) {
            return;
        }

        add_settings_section(
            'discord_role_sync',
            'Discord Role Sync',
            function () {
                echo '<p>Configure credentials for discord role sync</p>';
            },
            'general'
        );

        register_setting("general", "discord_client_id");
        add_settings_field(
            self::DISCORD_CLIENT_ID_KEY,
            'Discord Client ID',
            function () {
                $setting = get_option(self::DISCORD_CLIENT_ID_KEY);
                echo '<input type="text" name="' . self::DISCORD_CLIENT_ID_KEY . '" value="' . (isset($setting) ? esc_attr($setting) : '') . '">';
            },
            'general',
            'discord_role_sync'
        );

        register_setting("general", "discord_client_secret");
        add_settings_field(
            self::DISCORD_CLIENT_SECRET_KEY,
            'Discord Client Secret',
            function () {
                $setting = get_option(self::DISCORD_CLIENT_SECRET_KEY);
                echo '<input type="password" name="' . self::DISCORD_CLIENT_SECRET_KEY . '" value="' . (isset($setting) ? esc_attr($setting) : '') . '">';
            },
            'general',
            'discord_role_sync'
        );
    }

    public function activate(): void
    {
        $this->create_default_discord_page();
        $this->add_rewrite_rule();
        flush_rewrite_rules();
    }

    public function deactivate(): void
    {
        flush_rewrite_rules();
    }

    public function add_rewrite_rule(): void
    {
        add_rewrite_rule('^' . self::PAGE_NAME . '/callback$', "index.php?pagename=" . self::PAGE_NAME . "&" . self::QUERY_VAR . "=callback", 'top');
    }

    public function add_query_vars($vars)
    {
        $vars[] = self::QUERY_VAR;
        return $vars;
    }

    private function discord(): Discord\Client
    {
        return new Discord\Client(
            get_option("discord_client_id"),
            get_option("discord_client_secret"),
            site_url("/discord/callback"),
            new User_Meta_Token_Store()
        );
    }

    public function render_discord_link(): string|bool
    {
        if (!is_user_logged_in()) {
            auth_redirect();
        }

        $user_id = wp_get_current_user()->ID;

        try {
            $user = $this->discord()->get_user($user_id);

            return $this->render_template("discord-link-connected", compact('user'));
        } catch (Discord\NoAuthException) {
            $oauth = $this->discord()->oauth_url();
            $this->save_oauth_state($user_id, $oauth->state);

            ob_start();
            return $this->render_template("discord-link-disconnected", compact('oauth'));
        }
    }

    public function handle_oauth_callback(): void
    {
        global $wp_query;

        if (!isset($wp_query->query_vars[self::QUERY_VAR])) {
            return;
        }
        $action = $wp_query->query_vars[self::QUERY_VAR];

        if ('callback' != $action) {
            return;
        }

        if (!is_user_logged_in()) {
            auth_redirect();
        }

        $user_id = wp_get_current_user()->ID;
        if (!isset($_GET["state"])) {
            return;
        }
        $state = $_GET["state"];

        if (!isset($_GET["code"])) {
            return;
        }
        $code = $_GET["code"];

        $saved_state = $this->get_oauth_state($user_id);
        if ($state != $saved_state) {
            echo "Failed to connect, state mismatch, got {$state} expected {$saved_state}";
            exit;
        }

        $this->discord()->exchange_oauth_code($user_id, $code);
        $this->clear_oauth_state($user_id);

        wp_redirect("/discord");
        exit;
    }

    private function create_default_discord_page(): void
    {
        error_log('create discord page');
        $discord_page = get_page_by_path(self::PAGE_NAME);
        error_log('got discord page: ' . print_r($discord_page, true));

        if (!empty($discord_page)) {
            return;
        }
        wp_insert_post(array(
            'post_title' => 'Discord',
            'post_content' => $this->render_template("default-discord-page"),
            'post_status' => 'publish',
            'post_type' => 'page'
        ));
    }

    private function render_template(string $name, array $vars = array()): string
    {
        ob_start();
        foreach ($vars as $key => $val) {
            $$key = $val;
        }
        require "{$this->plugin_dir_path}templates/{$name}.php";
        return ob_get_clean();
    }

    /**
     * @param int $user_id
     * @param string $state
     * @return void
     */
    public function save_oauth_state(int $user_id, string $state): void
    {
        update_user_meta($user_id, "discord_oauth_state", $state);
    }

    /**
     * @param int $user_id
     * @return bool
     */
    public function clear_oauth_state(int $user_id): bool
    {
        return delete_user_meta($user_id, "discord_oauth_state");
    }

    /**
     * @param int $user_id
     * @return mixed
     */
    public function get_oauth_state(int $user_id): mixed
    {
        return get_user_meta($user_id, "discord_oauth_state", true);
    }
}