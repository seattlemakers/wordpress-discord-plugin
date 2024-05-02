<?php

namespace SeattleMakers\Discord;

class Tokens
{
    public int $expires_at;
    public string $access_token;
    public string $refresh_token;
    public array $scopes;
    public string|null $discord_user_id;

    public function __construct($body = false)
    {
        $this->discord_user_id = null;
        $this->scopes = [];

        if ($body) {
            $this->update($body);
        }
    }

    public function update($body): Tokens
    {
        if (isset($body->access_token)) {
            $this->access_token = $body->access_token;
        }
        if (isset($body->refresh_token)) {
            $this->refresh_token = $body->refresh_token;
        }

        $this->expires_at = time() + $body->expires_in;

        return $this;
    }

    public function update_authorization(Authorization $authorization): Tokens
    {
        $this->discord_user_id = $authorization->user->id;
        $this->scopes = $authorization->scopes;

        return $this;
    }
}