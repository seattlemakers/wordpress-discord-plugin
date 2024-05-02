<?php

namespace SeattleMakers\Discord;

use Exception;

class Client
{
    private string $base_url = 'https://discord.com/api/v10';

    private string $discord_client_id;
    private string $discord_client_secret;
    private string $discord_bot_token;
    private string $discord_oauth_redirect_url;

    private Token_Store $tokenStore;

    public function __construct(string $discord_client_id, string $discord_client_secret, string $discord_bot_token, string $discord_oauth_redirect_url, Token_Store $token_store)
    {
        $this->discord_client_id = $discord_client_id;
        $this->discord_client_secret = $discord_client_secret;
        $this->discord_bot_token = $discord_bot_token;
        $this->discord_oauth_redirect_url = $discord_oauth_redirect_url;
        $this->tokenStore = $token_store;
    }

    public function oauth_url(): OAuth_URL
    {
        $state = bin2hex(random_bytes(16));

        $url = $this->base_url . '/oauth2/authorize?' . http_build_query(array(
                'client_id' => $this->discord_client_id,
                'redirect_uri' => $this->discord_oauth_redirect_url,
                'response_type' => 'code',
                'state' => $state,
                'scope' => 'guilds.join role_connections.write identify',
                'prompt' => 'consent',
            ));

        return new OAuth_URL($state, $url);
    }

    /**
     * @throws Discord_Exception
     * @throws No_Auth_Exception
     * @throws Refresh_Exception
     */
    public function update_role_connection($user_id, $metadata)
    {
        $auth = $this->authenticate($user_id);

        $response = wp_remote_request($this->base_url . '/users/@me/applications/' . $this->discord_client_id . '/role-connection', [
            'method' => 'PUT',
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $auth->access_token
            ],
            'body' => json_encode(array(
                "platform_name" => "Seattle Makers",
                "platform_username" => $user_id,
                "metadata" => $metadata,
            )),
        ]);

        if (is_wp_error($response)) {
            throw new Discord_Exception($response->get_error_message());
        }

        return json_decode(wp_remote_retrieve_body($response));
    }

    /**
     * @throws Discord_Exception
     */
    private function update_authorization(Tokens $tokens): void
    {
        $response = wp_remote_request($this->base_url . '/oauth2/@me', [
            'method' => 'GET',
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $tokens->access_token
            ],
        ]);

        if (is_wp_error($response)) {
            throw new Discord_Exception($response->get_error_message());
        }

        // TODO: check $response['response']['code'] for 401 from discord and re-auth.


        $auth = new Authorization(json_decode(wp_remote_retrieve_body($response)));
        $tokens->update_authorization($auth);
    }

    /**
     * @throws Discord_Exception
     * @throws No_Auth_Exception
     * @throws Refresh_Exception
     */
    public function get_user($user_id): User
    {
        $auth = $this->authenticate($user_id);

        $response = wp_remote_request($this->base_url . '/users/@me', [
            'method' => 'GET',
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $auth->access_token
            ],
        ]);

        if (is_wp_error($response)) {
            throw new Discord_Exception($response->get_error_message());
        }

        // TODO: check $response['response']['code'] for 401 from discord and re-auth.

        return new User(json_decode(wp_remote_retrieve_body($response)));
    }


    /**
     * @throws Discord_Exception
     * @throws No_Auth_Exception
     * @throws Refresh_Exception
     */
    public function get_membership($discord_user_id, $server_id)
    {
        $response = wp_remote_request($this->base_url . '/guilds/' . $server_id . '/members/' . $discord_user_id, [
            'method' => 'GET',
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bot ' . $this->discord_bot_token
            ],
        ]);

        if (is_wp_error($response)) {
            throw new Discord_Exception($response->get_error_message());
        }
        if (wp_remote_retrieve_response_code($response) == 404) {
            return null;
        }
        if (wp_remote_retrieve_response_code($response) >= 400) {
            throw new Discord_Exception(wp_remote_retrieve_response_message($response) . " " . wp_remote_retrieve_body($response));
        }

        return json_decode(wp_remote_retrieve_body($response));
    }

    /**
     * @throws Refresh_Exception
     * @throws No_Auth_Exception
     * @throws Discord_Exception
     */
    private function authenticate($user_id): Tokens
    {
        $tokens = $this->tokenStore->get($user_id);
        if (!$tokens || !isset($tokens->refresh_token)) {
            throw new No_Auth_Exception();
        }
        if ($tokens->expires_at <= time()) {
            $this->refresh_tokens($tokens);
            $this->tokenStore->set($user_id, $tokens);
        }
        if ($tokens->discord_user_id === null) {
            $this->update_authorization($tokens);
            $this->tokenStore->set($user_id, $tokens);
        }
        return $tokens;
    }

    /**
     * @throws Refresh_Exception
     * @throws Discord_Exception
     */
    private function refresh_tokens(Tokens $tokens): void
    {
        $response = wp_remote_request($this->base_url . '/oauth2/token', [
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'client_id' => $this->discord_client_id,
                'client_secret' => $this->discord_client_secret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $tokens->refresh_token,
            ],
        ]);

        if (is_wp_error($response)) {
            throw new Refresh_Exception($response->get_error_message());
        }

        $this->handle_response_error($response);

        $body = json_decode(wp_remote_retrieve_body($response));
        $tokens->update($body);
    }

    /**
     * Given an OAuth2 code from the scope approval page, make a request to Discord's
     * OAuth2 service to retrieve an access token, refresh token, and expiration.
     */
    public function exchange_oauth_code($userId, $code): Tokens
    {
        $response = wp_remote_request($this->base_url . '/oauth2/token', array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
            'body' => array(
                'client_id' => $this->discord_client_id,
                'client_secret' => $this->discord_client_secret,
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->discord_oauth_redirect_url,
            )
        ));

        if (is_wp_error($response)) {
            throw new Exception('Error fetching OAuth tokens: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response));
        $tokens = (new Tokens())->update($body);
        $this->tokenStore->set($userId, $tokens);
        return $tokens;
    }

    /**
     * @throws Refresh_Exception
     * @throws Discord_Exception
     * @throws No_Auth_Exception
     */
    public function join_server($user_id, $server_id, $nick)
    {
        $auth = $this->authenticate($user_id);

        $response = wp_remote_request($this->base_url . '/guilds/' . $server_id . '/members/' . $auth->discord_user_id, array(
                'method' => 'PUT',
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bot ' . $this->discord_bot_token,
                ),
                'body' => json_encode(array(
                    'access_token' => $auth->access_token,
                    'nick' => $nick,
                )),
            )
        );

        if (is_wp_error($response)) {
            throw new Discord_Exception($response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code == 204) {
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response));
    }

    public function forget(int $user_id): void
    {
        $this->tokenStore->clear($user_id);
    }


    /**
     * @throws Discord_Exception
     */
    private function handle_response_error(array $response)
    {
        $status = $response['response']['code'];
        if ($status >= 400) {
            $message = $response['response']['message'];
            $body = wp_remote_retrieve_body($response);
            throw new Discord_Exception(sprintf("HTTP %d: (%s), body: %s", $status, $message, $body));
        }
    }
}