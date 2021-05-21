<?php

use PHPUnit\Framework\TestCase;
use Tsukaeru\RushFiles\API\AuthToken;
use Tsukaeru\RushFiles\API\Client;
use Tsukaeru\RushFiles\User;

class UserTest extends TestCase
{
    /**
     * @param string $username
     * @param array $domains Array of domains
     * @param DateTime $validUntil Defaults to 5min in the future is unspecified
     */
    private function createAuthToken($username, $domains, $validUntil = null)
    {
        if ($validUntil === null) {
            $validUntil = (new DateTime("+5 minutes"));
        }

        $primary = array_shift($domains);

        $accessToken = 'header.' . base64_encode(json_encode([
            'exp' => $validUntil->getTimestamp(),
            'sub' => $username,
            'primary_domain' => $primary,
            'domains' => $domains
        ])) . '.signature';

        return new AuthToken([
            'access_token' => $accessToken,
        ]);
    }

    public function testGetToken()
    {
        $client = $this->createMock(Client::class);
        $authToken = $this->createAuthToken('username', ['cloudfile.jp', 'rushfiles.com']);
        $user = new User($authToken, $client);

        $this->assertEquals($authToken->getAccessToken(), $user->getAccessToken());
    }

    public function testGetDomains()
    {
        $client = $this->createMock(Client::class);

        $authToken = $this->createAuthToken('username', ['cloudfile.jp', 'rushfiles.com']);
        $user = new User($authToken, $client);

        $this->assertEquals(['cloudfile.jp', 'rushfiles.com'], $user->getDomains());
    }

    public function testGetShares()
    {
        $client = $this->createMock(Client::class);
        $authToken = $this->createAuthToken('username', ['cloudfile.jp', 'rushfiles.com']);

        $map = [
            ['username', $authToken->getAccessToken(), 'cloudfile.jp', [['Id' => 'cf_share_id', 'ShareType' => 0]]],
            ['username', $authToken->getAccessToken(), 'rushfiles.com', [['Id' => 'rf_share_id', 'ShareType' => 0]]],
        ];
        $client->method('GetUserShares')->will($this->returnValueMap($map));

        $user = new User($authToken, $client);

        $this->assertEquals(2, count($user->getShares()));
        $this->assertEquals('cf_share_id', $user->getShare('cf_share_id')->getInternalName());
    }
}