<?php

namespace SeattleMakers\Discord;

class Tokens
{
    public int $expires_at;
    public string $access_token;
    public string $refresh_token;

    public function update($body): Tokens
    {
        if (isset($body->access_token))
            $this->access_token = $body->access_token;
        if (isset($body->refresh_token))
            $this->refresh_token = $body->refresh_token;

        $this->expires_at = time() + $body->expires_in * 1000;

        return $this;
    }
}