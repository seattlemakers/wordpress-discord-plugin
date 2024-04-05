<?php

namespace SeattleMakers\Discord;

class OAuth_URL
{
    public string $state;
    public string $url;

    public function __construct($state, $url)
    {
        $this->state = $state;
        $this->url = $url;
    }
}