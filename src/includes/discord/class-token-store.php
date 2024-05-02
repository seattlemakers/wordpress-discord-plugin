<?php

namespace SeattleMakers\Discord;

interface Token_Store
{
    public function get(int $user_id): Tokens|false;

    public function set(int $user_id, Tokens $tokens): void;

    public function clear(int $user_id): void;
}