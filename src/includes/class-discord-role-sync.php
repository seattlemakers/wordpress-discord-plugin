<?php

namespace SeattleMakers;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use SeattleMakers\Discord\Discord_Exception;
use SeattleMakers\Discord\No_Auth_Exception;
use SeattleMakers\Discord\Refresh_Exception;

class Discord_Role_Sync
{
    private const QUERY_VAR = 'sm_discord';
    private const PAGE_NAME = "discord";

    public const DISCORD_SERVER_ID_KEY = 'discord_server_id';
    public const DISCORD_CLIENT_ID_KEY = 'discord_client_id';
    public const DISCORD_CLIENT_SECRET_KEY = 'discord_client_secret';
    public const DISCORD_BOT_TOKEN_KEY = 'discord_bot_token';

    private Presspoint\User_Metadata_Provider $user_metadata_provider;

    private ?Discord\Client $discord = null;
    private string $template_path;

    public function __construct(string $plugin_file_path)
    {
        $this->user_metadata_provider = new Presspoint\User_Metadata_Provider();
        $this->template_path = dirname($plugin_file_path) . "/templates";

        register_activation_hook($plugin_file_path, array($this, 'activate'));
        register_deactivation_hook($plugin_file_path, array($this, 'deactivate'));

        add_action('init', array($this, 'add_rewrite_rule'));
        add_action('admin_init', array($this, 'admin_init'));

        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_discord_action'));

        add_shortcode('discord_link', array($this, 'render_discord_link'));

        add_action('wp_footer', array($this, 'flush_updates'));
        add_action('admin_footer', array($this, 'flush_updates'));
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

        register_setting("general", self::DISCORD_SERVER_ID_KEY);
        add_settings_field(
            self::DISCORD_SERVER_ID_KEY,
            'Discord Server ID',
            function () {
                $setting = get_option(self::DISCORD_SERVER_ID_KEY);
                echo '<input type="text" name="' . self::DISCORD_SERVER_ID_KEY . '" value="' . (isset($setting) ? esc_attr($setting) : '') . '">';
            },
            'general',
            'discord_role_sync'
        );

        register_setting("general", self::DISCORD_CLIENT_ID_KEY);
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

        register_setting("general", self::DISCORD_CLIENT_SECRET_KEY);
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

        register_setting("general", self::DISCORD_BOT_TOKEN_KEY);
        add_settings_field(
            self::DISCORD_BOT_TOKEN_KEY,
            'Discord Bot Token',
            function () {
                $setting = get_option(self::DISCORD_BOT_TOKEN_KEY);
                echo '<input type="password" name="' . self::DISCORD_BOT_TOKEN_KEY . '" value="' . (isset($setting) ? esc_attr($setting) : '') . '">';
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
        add_rewrite_rule(
            sprintf("^%s/(\w+)/?$", self::PAGE_NAME),
            sprintf('index.php?pagename=%s&%s=$matches[1]', self::PAGE_NAME, self::QUERY_VAR),
            'top'
        );
    }

    public function add_query_vars($vars)
    {
        $vars[] = self::QUERY_VAR;
        return $vars;
    }

    private function discord(): Discord\Client
    {
        if ($this->discord == null) {
            $this->discord = new Discord\Client(
                get_option(self::DISCORD_CLIENT_ID_KEY),
                get_option(self::DISCORD_CLIENT_SECRET_KEY),
                get_option(self::DISCORD_BOT_TOKEN_KEY),
                site_url(sprintf("/%s/callback", self::PAGE_NAME)),
                new User_Meta_Token_Store()
            );
        }
        return $this->discord;
    }

    public function flush_updates(): void
    {
        foreach ($this->user_metadata_provider->flush() as $user_id => $metadata) {
            try {
                $this->discord()->update_role_connection($user_id, $metadata);
            } catch (Exception $e) {
                error_log(sprintf("Failed to update role connection for %s: %s", $user_id, $e->getMessage()));
            }
        }
    }

    public function render_discord_link(): string|bool
    {
        if (!is_user_logged_in()) {
            return $this->render_template("discord-link-logged-out", []);
        }

        $user_id = wp_get_current_user()->ID;
        try {
            $server_id = get_option(self::DISCORD_SERVER_ID_KEY);
            $roles = $this->user_metadata_provider->get_metadata($user_id)->to_list();

            $user = $this->discord()->get_user($user_id);
            $membership = $this->discord()->get_membership($user->id, $server_id);

            return $this->render_template("discord-link-connected", array(
                'user' => $user,
                'membership' => $membership,
                'server_id' => $server_id,
                'roles' => $roles,
            ));
        } catch (Discord\No_Auth_Exception|Discord\Refresh_Exception) {
            $oauth = $this->discord()->oauth_url();
            $this->save_oauth_state($user_id, $oauth->state);

            return $this->render_template("discord-link-disconnected", array("oauth" => $oauth));
        } catch (Discord\Discord_Exception $e) {
            return $this->render_template("discord-link-error", array("error" => $e->getMessage()));
        }
    }

    public function handle_discord_action(): void
    {
        global $wp_query;
        if (!isset($wp_query->query_vars[self::QUERY_VAR])) {
            return;
        }
        $action = $wp_query->query_vars[self::QUERY_VAR];

        switch ($action) {
            case 'link':
                $this->handle_link();
                break;
            case 'unlink':
                $this->handle_unlink();
                break;
            case 'callback':
                $this->handle_oauth_callback();
                break;
        }
    }

    private function handle_oauth_callback(): void
    {
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
            wp_trigger_error(__METHOD__, "Failed to authorize with discord: callback state didn't match.");
        }

        try {
            $this->discord()->exchange_oauth_code($user_id, $code);
            $this->clear_oauth_state($user_id);

            $this->user_metadata_provider->user_changed($user_id);
            $this->flush_updates();
        } catch (Exception $ex) {
            wp_trigger_error(__METHOD__, "Failed to authorize with discord: " . $ex->getMessage());
        }

        wp_redirect("/" . self::PAGE_NAME . "/link");
        exit;
    }

    private function handle_link(): void
    {
        if (!is_user_logged_in()) {
            auth_redirect();
        }
        $user = wp_get_current_user();
        $nick = $user->first_name . " " . substr($user->last_name, 0, 1);
        $server_id = get_option(self::DISCORD_SERVER_ID_KEY);

        try {
            $this->discord()->join_server($user->ID, $server_id, $nick);
        } catch (Exception $ex) {
            wp_trigger_error(__METHOD__, "Failed to authorize with discord: " . $ex->getMessage());
        }

        wp_redirect("/" . self::PAGE_NAME);
        exit;
    }

    private function handle_unlink(): void
    {
        if (!is_user_logged_in()) {
            auth_redirect();
        }
        $user_id = wp_get_current_user()->ID;

        $this->discord()->forget($user_id);

        wp_redirect("/" . self::PAGE_NAME);
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
            'post_slug' => self::PAGE_NAME,
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
        require "{$this->template_path}/{$name}.php";
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