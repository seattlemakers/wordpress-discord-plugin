<?php

namespace SeattleMakers;

require_once 'discord/class-token-store.php';

class User_Meta_Token_Store implements Discord\Token_Store
{
    const META_KEY = "discord_tokens";

    public function get(int $userId): Discord\Tokens|false
    {
        $meta = get_user_meta($userId, self::META_KEY, true);
        error_log(print_r($meta, true));
        if (empty($meta)) {
            return false;
        }
        return $meta;
    }

    public function set(int $userId, Discord\Tokens $tokens): void
    {
        update_user_meta($userId, self::META_KEY, $tokens);
    }
}