<?php

namespace SeattleMakers\Discord;

use http\Exception\RuntimeException;

class Client
{
    private string $base_url = 'https://discord.com/api/v10';

    private string $discord_client_id;
    private string $discord_client_secret;
    private string $discord_bot_token;
    private string $discord_oauth_redirect_url;

    private Token_Store $tokenStore;

    public function __construct(
        string      $discord_client_id,
        string      $discord_client_secret,
        string      $discord_bot_token,
        string      $discord_oauth_redirect_url,
        Token_Store $token_store
    ) {
        $this->discord_client_id = $discord_client_id;
        $this->discord_client_secret = $discord_client_secret;
        $this->discord_bot_token = $discord_bot_token;
        $this->discord_oauth_redirect_url = $discord_oauth_redirect_url;
        $this->tokenStore = $token_store;
    }

    public function oauth_url(): OAuth_URL
    {
        $state = bin2hex(random_bytes(16));

        $url = $this->base_url . '/oauth2/authorize?' . http_build_query([
                'client_id' => $this->discord_client_id,
                'redirect_uri' => $this->discord_oauth_redirect_url,
                'response_type' => 'code',
                'state' => $state,
                'scope' => 'guilds.join role_connections.write identify',
                'prompt' => 'consent',
            ]);

        return new OAuth_URL($state, $url);
    }

    /**
     * @throws No_Auth_Exception
     */
    public function update_role_connection($user_id, $metadata)
    {
        $auth = $this->authenticate($user_id);

        $body = $this->request(
            'PUT',
            '/users/@me/applications/' . $this->discord_client_id . '/role-connection',
            headers: ['Authorization' => 'Bearer ' . $auth->access_token],
            body: json_encode([
                "platform_name" => "Seattle Makers",
                "platform_username" => $user_id,
                "metadata" => $metadata,
            ])
        );

        if (!$body) {
            return false;
        }

        return json_decode($body);
    }

    /**
     * @throws No_Auth_Exception
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

    private function refresh_tokens(Tokens $tokens): void
    {
        $body = $this->request(
            'POST', '/oauth2/token',
            headers: ['Content-Type' => 'application/x-www-form-urlencoded'],
            body: [
                'client_id' => $this->discord_client_id,
                'client_secret' => $this->discord_client_secret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $tokens->refresh_token,
            ]
        );

        if (!$body) {
            return;
        }

        $object = json_decode($body);
        $tokens->update($object);
    }

    private function request(string $method, string $path, $headers = [], $body = null): string|false
    {
        $args = [
            'method' => $method,
            'headers' => array_merge([
                'Content-Type' => 'application/json',
            ], $headers),
        ];
        if ($body) {
            $args['body'] = $body;
        }

        $response = wp_remote_request($this->base_url . $path, $args);
        if (is_wp_error($response)) {
            // TODO: handle this with a typed exception?
            throw new RuntimeException($response->get_error_message());
        }
        $response_body = wp_remote_retrieve_body($response);
        $http_status = wp_remote_retrieve_response_code($response);

        if ($http_status < 400) {
            return $response_body;
        }

        if ($http_status == 404) {
            return false;
        }

        $http_status_message = wp_remote_retrieve_response_message($response);
        throw new Discord_Exception(sprintf("HTTP %d (%s): %s", $http_status, $http_status_message, $response_body));
    }

    private function update_authorization(Tokens $tokens): void
    {
        $body = $this->request('GET', '/oauth2/@me',
            headers: ['Authorization' => 'Bearer ' . $tokens->access_token]
        );

        if (!$body) {
            throw new Discord_Exception('Failed to get authorization');
        }

        $auth = new Authorization(json_decode($body));
        $tokens->update_authorization($auth);
    }

    /**
     * @throws No_Auth_Exception
     */
    public function get_user($user_id): User|false
    {
        $auth = $this->authenticate($user_id);

        $body = $this->request('GET', '/users/@me',
            ['Authorization' => 'Bearer ' . $auth->access_token]
        );

        if (!$body) {
            return false;
        }

        return new User(json_decode($body));
    }

    public function get_membership($discord_user_id, $server_id)
    {
        $response = $this->request(
            'GET', sprintf("/guilds/%s/members/%s", $server_id, $discord_user_id),
            headers: ['Authorization' => 'Bot ' . $this->discord_bot_token]
        );

        if (!$response) {
            return null;
        }

        return json_decode($response);
    }

    public function get_roles($server_id)
    {
        $response = $this->request(
            'GET', sprintf("/guilds/%s/roles", $server_id),
            headers: ['Authorization' => 'Bot ' . $this->discord_bot_token]
        );

        if (!$response) {
            return null;
        }

        return json_decode($response);
    }

    public function register_metadata(array $metadata)
    {
        $response = $this->request(
            'PUT', sprintf("/applications/%s/role-connections/metadata", $this->discord_client_id),
            headers: ['Authorization' => 'Bot ' . $this->discord_bot_token],
            body: json_encode($metadata),
        );

        if (!$response) {
            return null;
        }

        return json_decode($response);
    }

    /**
     * Given an OAuth2 code from the scope approval page, make a request to Discord's
     * OAuth2 service to retrieve an access token, refresh token, and expiration.
     */
    public function exchange_oauth_code($userId, $code): Tokens
    {
        $body = $this->request(
            'POST', '/oauth2/token',
            headers: ['Content-Type' => 'application/x-www-form-urlencoded'],
            body: [
                'client_id' => $this->discord_client_id,
                'client_secret' => $this->discord_client_secret,
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->discord_oauth_redirect_url,
            ]
        );

        if (!$body) {
            throw new Discord_Exception('Error fetching OAuth tokens');
        }

        $tokens = new Tokens(json_decode($body));
        $this->tokenStore->set($userId, $tokens);
        return $tokens;
    }

    /**
     * @throws No_Auth_Exception
     */
    public function join_server($user_id, $server_id, $nick)
    {
        $auth = $this->authenticate($user_id);

        $response = $this->request(
            'PUT', sprintf("/guilds/%s/members/%s", $server_id, $auth->discord_user_id),
            headers: ['Authorization' => 'Bot ' . $this->discord_bot_token,],
            body: json_encode([
                'access_token' => $auth->access_token,
                'nick' => $nick,
            ])
        );

        return json_decode($response);
    }

    public function forget(int $user_id): void
    {
        $this->tokenStore->clear($user_id);
    }
}