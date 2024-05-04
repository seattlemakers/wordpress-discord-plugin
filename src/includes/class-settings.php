<?php

namespace SeattleMakers;

use Closure;
use Exception;

class Settings
{
    public const DISCORD_SERVER_ID_KEY = 'discord_server_id';
    public const DISCORD_CLIENT_ID_KEY = 'discord_client_id';
    public const DISCORD_CLIENT_SECRET_KEY = 'discord_client_secret';
    public const DISCORD_BOT_TOKEN_KEY = 'discord_bot_token';
    private Closure $register_metadata;

    public function __construct(Closure $register_metadata)
    {
        $this->register_metadata = $register_metadata;
        add_action('admin_init', [$this, 'admin_init']);
        add_action('admin_menu', [$this, 'admin_menu']);

    }

    public function admin_init(): void
    {
        if (!is_admin()) {
            return;
        }

        add_settings_section(
            'discord_credentials',
            'Credentials',
            function () {
                echo '<p>Configure credentials for discord role sync</p>';
            },
            'discord-settings'
        );

        register_setting("discord_credentials", self::DISCORD_SERVER_ID_KEY);
        add_settings_field(
            self::DISCORD_SERVER_ID_KEY,
            'Discord Server ID',
            function () {
                $setting = get_option(self::DISCORD_SERVER_ID_KEY);
                echo '<input type="text" name="' . self::DISCORD_SERVER_ID_KEY . '" value="' . (isset($setting) ? esc_attr($setting) : '') . '">';
            },
            'discord-settings',
            'discord_credentials'
        );

        register_setting("discord_credentials", self::DISCORD_CLIENT_ID_KEY);
        add_settings_field(
            self::DISCORD_CLIENT_ID_KEY,
            'Discord Client ID',
            function () {
                $setting = get_option(self::DISCORD_CLIENT_ID_KEY);
                echo '<input type="text" name="' . self::DISCORD_CLIENT_ID_KEY . '" value="' . (isset($setting) ? esc_attr($setting) : '') . '">';
            },
            'discord-settings',
            'discord_credentials'
        );

        register_setting("discord_credentials", self::DISCORD_CLIENT_SECRET_KEY);
        add_settings_field(
            self::DISCORD_CLIENT_SECRET_KEY,
            'Discord Client Secret',
            function () {
                $setting = get_option(self::DISCORD_CLIENT_SECRET_KEY);
                echo '<input type="password" name="' . self::DISCORD_CLIENT_SECRET_KEY . '" value="' . (isset($setting) ? esc_attr($setting) : '') . '">';
            },
            'discord-settings',
            'discord_credentials'
        );

        register_setting("discord_credentials", self::DISCORD_BOT_TOKEN_KEY);
        add_settings_field(
            self::DISCORD_BOT_TOKEN_KEY,
            'Discord Bot Token',
            function () {
                $setting = get_option(self::DISCORD_BOT_TOKEN_KEY);
                echo '<input type="password" name="' . self::DISCORD_BOT_TOKEN_KEY . '" value="' . (isset($setting) ? esc_attr($setting) : '') . '">';
            },
            'discord-settings',
            'discord_credentials'
        );
    }

    public function admin_menu(): void
    {
        add_options_page(
            'Discord Sync Settings',
            'Discord',
            'manage_options',
            'discord-settings',
            function () {
                ?>
                <div class="wrap">
                    <h1>Discord Role Sync Settings</h1>
                    <form action="options.php" method="POST">
                        <?php
                        settings_fields('discord_credentials');
                        do_settings_sections('discord-settings');
                        submit_button();
                        ?>
                    </form>
                    <h2>Connect Bot to Server</h2>
                    <p>After configuring credentials, grant the bot the right permissions on Discord:</p>
                    <p><a href="<?php printf("https://discord.com/oauth2/authorize?client_id=%s&scope=bot&permissions=402653185&guild_id=%s", $this->discord_client_id(), $this->discord_server_id()) ?>">Add to server</a></p>
                    <h2>Register Role Connection Metadata</h2>
                    <p>As a one-time setup, after configuring credentials, register metadata with Discord to link SM
                        roles with Discord roles:</p>
                    <form action="options-general.php?page=discord-settings" method="POST">
                        <?php
                        if (isset($_POST['register_metadata']) && check_admin_referer('register_metadata_clicked')) {
                            try {
                                $meta = ($this->register_metadata)();
                                ?>
                                <div class='notice success is-dismissible'><p><strong>Successfully registered
                                            metadata:</strong>
                                    <pre><?php echo(print_r($meta, true)) ?></pre>
                                    </p></div>
                                <?php
                            } catch (Exception $ex) {
                                ?>
                                <div class='notice error is-dismissible'><p><strong>Failed to register
                                            metadata:</strong>
                                    <pre><?php echo($ex->getMessage()) ?></pre>
                                    </p></div>
                                <?php
                            }
                        }
                        wp_nonce_field('register_metadata_clicked');
                        echo '<input type="hidden" value="true" name="register_metadata" />';
                        submit_button("Register Metadata", other_attributes: !$this->is_configured() ? ["disabled" => true] : []);
                        ?>
                    </form>
                </div>
                <?php
            }
        );
    }

    public function discord_client_id()
    {
        return get_option(self::DISCORD_CLIENT_ID_KEY);
    }

    public function discord_server_id()
    {
        return get_option(self::DISCORD_SERVER_ID_KEY);
    }

    private function is_configured(): bool
    {
        $options = [
            self::DISCORD_CLIENT_ID_KEY,
            self::DISCORD_CLIENT_SECRET_KEY,
            self::DISCORD_BOT_TOKEN_KEY,
        ];

        foreach ($options as $option) {
            if (!get_option($option) || trim(get_option($option)) === '') {
                return false;
            }
        }

        return true;
    }

    public function discord_client_secret()
    {
        return get_option(self::DISCORD_CLIENT_SECRET_KEY);
    }

    public function discord_bot_token()
    {
        return get_option(self::DISCORD_BOT_TOKEN_KEY);
    }

}