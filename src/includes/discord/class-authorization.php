<?php

namespace SeattleMakers\Discord;

// https://discord.com/developers/docs/topics/oauth2#get-current-authorization-information
use stdClass;

class Authorization
{
    public mixed $application;
    public array $scopes;
    public string $expires;
    public ?User $user;

    public function __construct(stdClass $response)
    {
        $this->application = $response->application ?? null;
        $this->scopes = $response->scopes ?? [];
        $this->expires = $response->expires ?? '';
        $this->user = isset($response->user) ? new User($response->user) : null;
    }
}
