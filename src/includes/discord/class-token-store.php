<?php

namespace SeattleMakers\Discord;

require_once 'class-tokens.php';

interface Token_Store
{
    public function get(int $userId): Tokens|false;
    public function set(int $userId, Tokens $tokens): void;
}