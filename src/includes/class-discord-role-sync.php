<?php

namespace SeattleMakers;

use Exception;
use SeattleMakers\Discord\No_Auth_Exception;

class Discord_Role_Sync
{
    private const QUERY_VAR = 'sm_discord';
    private const PAGE_NAME = "discord";

    public const DISCORD_CLIENT_ID_KEY = 'discord_client_id';
    public const DISCORD_CLIENT_SECRET_KEY = 'discord_client_secret';

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
        add_action('template_redirect', array($this, 'handle_oauth_callback'));

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
        add_rewrite_rule(
            sprintf("^%s/callback$", self::PAGE_NAME),
            sprintf("index.php?pagename=%s&%s=callback", self::PAGE_NAME, self::QUERY_VAR),
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
                $resp = $this->discord()->update_role_connection($user_id, $metadata);
                error_log(print_r($resp, true));
            } catch (Exception $e) {
                error_log(print_r($e, true));
            }
        }
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
        } catch (Discord\No_Auth_Exception | Discord\Refresh_Exception) {
            $oauth = $this->discord()->oauth_url();
            $this->save_oauth_state($user_id, $oauth->state);

            return $this->render_template("discord-link-disconnected", compact('oauth'));
        } catch (Discord\Post_Exception $e) {
            return $this->render_template("discord-link-error");
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
        error_log(print_r(array("state" => $state, "saved_state"=>$saved_state), true));
        if ($state != $saved_state) {
            wp_trigger_error(__METHOD__, "Failed to authorize with discord: callback state didn't match.");
        }

        $this->discord()->exchange_oauth_code($user_id, $code);
        $this->clear_oauth_state($user_id);

        $this->user_metadata_provider->user_changed($user_id);
        $this->flush_updates();

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