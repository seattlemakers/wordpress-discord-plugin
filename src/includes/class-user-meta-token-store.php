<?php

namespace SeattleMakers;

class User_Meta_Token_Store implements Discord\Token_Store
{
    const META_KEY = "discord_tokens";

    public function get(int $user_id): Discord\Tokens|false
    {
        $meta = get_user_meta($user_id, self::META_KEY, true);
        error_log(print_r($meta, true));
        if (empty($meta)) {
            return false;
        }
        return $meta;
    }

    public function set(int $user_id, Discord\Tokens $tokens): void
    {
        update_user_meta($user_id, self::META_KEY, $tokens);
    }

    public function clear(int $user_id): void
    {
        delete_user_meta($user_id, self::META_KEY);
    }
}