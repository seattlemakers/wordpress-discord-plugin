<?php

namespace SeattleMakers;

use Exception;
use SeattleMakers\Discord\User_Metadata;
use WP_User;

class Discord_Role_Sync
{
    private const QUERY_VAR = 'sm_discord';
    private const PAGE_NAME = "discord";

    private Presspoint\User_Metadata_Provider $user_metadata_provider;

    private ?Discord\Client $discord = null;
    private string $template_path;
    private string $plugin_file;

    private Settings $settings;

    public function __construct(string $plugin_file_path)
    {
        $this->plugin_file = basename(dirname($plugin_file_path)) . "/" . basename($plugin_file_path);
        $this->user_metadata_provider = new Presspoint\User_Metadata_Provider();
        $this->template_path = dirname($plugin_file_path) . "/templates";

        register_activation_hook($plugin_file_path, [$this, 'activate']);
        register_deactivation_hook($plugin_file_path, [$this, 'deactivate']);

        $this->settings = new Settings(
            register_metadata: function () {
                return $this->discord()->register_metadata(User_Metadata::SCHEMA);
            }
        );

        add_action('init', [$this, 'add_rewrite_rule']);

        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'handle_discord_action']);

        add_shortcode('discord_link', [$this, 'render_discord_link']);

        add_action('wp_footer', [$this, 'flush_updates']);
        add_action('admin_footer', [$this, 'flush_updates']);

        add_filter('plugin_action_links', [$this, 'add_plugin_action_links'], 10, 2);
    }

    private function discord(): Discord\Client
    {
        if ($this->discord == null) {
            $this->discord = new Discord\Client(
                $this->settings->discord_client_id(),
                $this->settings->discord_client_secret(),
                $this->settings->discord_bot_token(),
                site_url($this->action_url("callback")),
                new User_Meta_Token_Store()
            );
        }
        return $this->discord;
    }

    /**
     * @param string $action
     * @return string
     */
    public function action_url(string $action): string
    {
        return sprintf("/%s/%s", self::PAGE_NAME, $action);
    }

    public function add_plugin_action_links(array $links, string $file): array
    {
        if ($file == $this->plugin_file) {
            $url = admin_url("options-general.php?page=discord-settings");
            $links['discord_settings'] = "<a href='{$url}'>Settings</a>";
        }
        return $links;
    }

    public function activate(): void
    {
        $this->create_default_discord_page();
        $this->add_rewrite_rule();
        flush_rewrite_rules();
    }

    private function create_default_discord_page(): void
    {
        error_log('create discord page');
        $discord_page = get_page_by_path(self::PAGE_NAME);
        error_log('got discord page: ' . print_r($discord_page, true));

        if (!empty($discord_page)) {
            return;
        }
        wp_insert_post([
            'post_title' => 'Discord',
            'post_slug' => self::PAGE_NAME,
            'post_content' => $this->render_template("default-discord-page"),
            'post_status' => 'publish',
            'post_type' => 'page',
        ]);
    }

    private function render_template(string $name, array $vars = []): string
    {
        ob_start();
        foreach ($vars as $key => $val) {
            $$key = $val;
        }
        require "{$this->template_path}/{$name}.php";
        return ob_get_clean();
    }

    public function add_rewrite_rule(): void
    {
        add_rewrite_rule(
            sprintf("^%s/(\w+)/?$", self::PAGE_NAME),
            sprintf('index.php?pagename=%s&%s=$matches[1]', self::PAGE_NAME, self::QUERY_VAR),
            'top'
        );
    }

    public function deactivate(): void
    {
        flush_rewrite_rules();
    }

    public function add_query_vars($vars)
    {
        $vars[] = self::QUERY_VAR;
        return $vars;
    }

    public function render_discord_link(): string|bool
    {
        if (!is_user_logged_in()) {
            return $this->render_template("discord-link-logged-out", []);
        }

        $user = wp_get_current_user();
        $nick = self::nick_for($user);
        $roles = $this->user_metadata_provider->get_metadata($user->ID)->to_list();

        try {
            $server_id = $this->settings->discord_server_id();

            $discord_user = $this->discord()->get_user($user->ID);
            $membership = $this->discord()->get_membership($discord_user->id, $server_id);

            return $this->render_template("discord-link-connected", [
                'user' => $discord_user,
                'membership' => $membership,
                'server_id' => $server_id,
                'nick' => $nick,
                'roles' => $roles,
            ]);
        } catch (Discord\No_Auth_Exception|Discord\Refresh_Exception) {
            $oauth = $this->discord()->oauth_url();
            $this->save_oauth_state($user->ID, $oauth->state);

            return $this->render_template("discord-link-disconnected", [
                "oauth" => $oauth,
                'nick' => $nick,
                'roles' => $roles,
            ]);
        } catch (Discord\Discord_Exception $e) {
            return $this->render_template("discord-link-error", ["error" => $e->getMessage()]);
        }
    }

    /**
     * @param WP_User|null $user
     * @return string
     */
    public static function nick_for(?WP_User $user): string
    {
        return sprintf("%s %s", $user->first_name, substr($user->last_name, 0, 1));
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

    private function handle_link(): void
    {
        if (!is_user_logged_in()) {
            auth_redirect();
        }
        $user = wp_get_current_user();
        $nick = self::nick_for($user);
        $server_id = $this->settings->discord_server_id();

        try {
            $this->discord()->join_server($user->ID, $server_id, $nick);
        } catch (Exception $ex) {
            wp_trigger_error(__METHOD__, "Failed to authorize with discord: " . $ex->getMessage());
        }

        wp_redirect($this->root_url());
        exit;
    }

    private function root_url(): string
    {
        return sprintf("/%s", self::PAGE_NAME);
    }

    private function handle_unlink(): void
    {
        if (!is_user_logged_in()) {
            auth_redirect();
        }
        $user_id = wp_get_current_user()->ID;

        $this->discord()->forget($user_id);

        wp_redirect($this->root_url());
        exit;
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

            wp_redirect($this->action_url("link"));
            exit;
        } catch (Exception $ex) {
            wp_trigger_error(__METHOD__, "Failed to authorize with discord: " . $ex->getMessage());
            wp_redirect($this->root_url());
        }
    }

    /**
     * @param int $user_id
     * @return mixed
     */
    public function get_oauth_state(int $user_id): mixed
    {
        return get_user_meta($user_id, "discord_oauth_state", true);
    }

    /**
     * @param int $user_id
     * @return bool
     */
    public function clear_oauth_state(int $user_id): bool
    {
        return delete_user_meta($user_id, "discord_oauth_state");
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
}