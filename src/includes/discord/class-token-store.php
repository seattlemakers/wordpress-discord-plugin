<?php

namespace SeattleMakers\Discord;

interface Token_Store
{
    public function get(int $userId): Tokens|false;
    public function set(int $userId, Tokens $tokens): void;
}