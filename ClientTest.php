<?php

namespace SeattleMakers\Discord;

class TokenStore {
    private array $tokens = [];

    public function get($userId): Tokens {
        return $this->tokens[$userId] ?? null;
    }

    public function set($userId, Tokens $tokens): void {
        $this->tokens[$userId] = $tokens;
    }
}
<?php

require 'class-client.php';
require 'class-tokens.php';
require 'class-tokenstore.php';

use PHPUnit\Framework\TestCase;
use SeattleMakers\Discord\Client;
use SeattleMakers\Discord\Tokens;
use SeattleMakers\Discord\WP_Error;
use SeattleMakers\Discord\TokenStore;

class ClientTest extends TestCase {

    private $client;
    private $tokens;
    private $store;

    protected function setUp(): void {

        $this->tokens = new Tokens();
        $this->tokens->access_token = 'access_token';
        $this->tokens->expires_at = time() + 60;
        $this->store = new TokenStore();
        $this->store->set(1, $this->tokens);

        $this->client = new Client('client_id', 'client_secret', 'redirect_url', $this->store);
    }

    public function testGetUserSuccess() {

        $result = $this->client->get_user(1);

        $this->assertEquals($this->tokens, $result);
    }
}