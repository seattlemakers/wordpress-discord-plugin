<?php
namespace SeattleMakers\Discord;

require 'class-oauth-url.php';

use Exception;

class NoAuthException extends Exception
{
}

class PostException extends Exception
{
}

class RefreshException extends Exception
{
}

class Client
{
    private string $base_url = 'https://discord.com/api/v10';

    private string $discord_client_id;
    private string $discord_client_secret;
    private string $discord_oauth_redirect_url;

    private Token_Store $tokenStore;

    public function __construct(string $discord_client_id, string $discord_client_secret, string $discord_oauth_redirect_url, Token_Store $token_store)
    {
        $this->discord_client_id = $discord_client_id;
        $this->discord_client_secret = $discord_client_secret;
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
                'scope' => 'role_connections.write identify',
                'prompt' => 'consent',
            ]);

        return new OAuth_URL($state, $url);
    }

    /**
     * @throws PostException
     * @throws NoAuthException
     * @throws RefreshException
     */
    public function update_role_connection($user_id, $metadata)
    {
        $auth = $this->authenticate($user_id);

        $response = wp_remote_post($this->base_url . '/users/@me/applications/' . $this->discord_client_id . '/role-connection', [
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $auth->access_token
            ],
            'body' => json_encode($metadata),
        ]);

        if (is_wp_error($response)) {
            throw new PostException($response->get_error_message());
        }

        return json_decode(wp_remote_retrieve_body($response));
    }

    /**
     * @throws PostException
     * @throws NoAuthException
     * @throws RefreshException
     */
    public function get_user($user_id)
    {
        $auth = $this->authenticate($user_id);

        $response = wp_remote_post($this->base_url . '/oauth2/@me', [
            'method' => 'GET',
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $auth->access_token
            ],
        ]);

        if (is_wp_error($response)) {
            throw new PostException($response->get_error_message());
        }

        return json_decode(wp_remote_retrieve_body($response));
    }

    /**
     * @throws RefreshException
     * @throws NoAuthException
     */
    private function authenticate($user_id): Tokens
    {
        $tokens = $this->tokenStore->get($user_id);
        if (!$tokens) {
            throw new NoAuthException();
        }
        if ($tokens->expires_at <= time()) {
            $this->refresh_tokens($tokens);
            $this->tokenStore->set($user_id, $tokens);
        }
        return $tokens;
    }

    /**
     * @throws RefreshException
     */
    private function refresh_tokens(Tokens $tokens): void
    {
        $response = wp_remote_post($this->base_url . '/oauth2/token', [
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
            throw new RefreshException($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response));
        $tokens->update($body);
    }

    /**
     * Given an OAuth2 code from the scope approval page, make a request to Discord's
     * OAuth2 service to retrieve an access token, refresh token, and expiration.
     */
    public function exchange_oauth_code($userId, $code): Tokens
    {
        $response = wp_remote_post($this->base_url . '/oauth2/token', array(
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
}