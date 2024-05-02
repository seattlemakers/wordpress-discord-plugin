<?php

namespace SeattleMakers\Discord;

// https://discord.com/developers/docs/resources/user#user-object
class User {
    public string $id;
    public string $username;
    public string $discriminator;
    public ?string $global_name;
    public ?string $avatar;
    public ?bool $bot;
    public ?bool $system;
    public ?bool $mfa_enabled;
    public ?string $banner;
    public ?int $accent_color;
    public ?string $locale;
    public ?bool $verified;
    public ?string $email;
    public ?int $flags;
    public ?int $premium_type;
    public ?int $public_flags;
    public ?string $avatar_decoration;


    public function __construct(\stdClass $data)
    {
        $this->id = $data->id ?? null;
        $this->username = $data->username ?? null;
        $this->discriminator = $data->discriminator ?? null;
        $this->global_name = $data->global_name ?? null;
        $this->avatar = $data->avatar ?? null;
        $this->bot = $data->bot ?? null;
        $this->system = $data->system ?? null;
        $this->mfa_enabled = $data->mfa_enabled ?? null;
        $this->banner = $data->banner ?? null;
        $this->accent_color = $data->accent_color ?? null;
        $this->locale = $data->locale ?? null;
        $this->verified = $data->verified ?? null;
        $this->email = $data->email ?? null;
        $this->flags = $data->flags ?? null;
        $this->premium_type = $data->premium_type ?? null;
        $this->public_flags = $data->public_flags ?? null;
        $this->avatar_decoration = $data->avatar_decoration ?? null;
    }
}