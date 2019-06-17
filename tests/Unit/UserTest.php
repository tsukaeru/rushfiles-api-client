<?php
use PHPUnit\Framework\TestCase;
use Tsukaeru\RushFiles\Client;
use Tsukaeru\RushFiles\User;

class UserTest extends TestCase
{
    public function testGetToken()
    {
        $client = $this->createMock(Client::class);
        $user = new User('username', [
            'cloudfile.jp' => 'cf_token',
            'rushfiles.com' => 'rf_token',
        ], $client);

        $this->assertEquals('rf_token', $user->getToken('rushfiles.com'));
        $this->assertEquals('cf_token', $user->getToken());
    }

    public function testGetDomains()
    {
        $client = $this->createMock(Client::class);

        $user = new User('username', [
            'cloudfile.jp' => 'cf_token',
            'rushfiles.com' => 'rf_token',
        ], $client);

        $this->assertEquals(['cloudfile.jp', 'rushfiles.com'], $user->getDomains()->toArray());

        $user = new User('username', [
            [
                'DomainUrl' => 'cloudfile.jp',
                'DomainToken' => 'cf_token',
            ],
            [
                'DomainUrl' => 'rushfiles.com',
                'DomainToken' => 'rf_token',
            ],
        ], $client);

        $this->assertEquals(['cloudfile.jp', 'rushfiles.com'], $user->getDomains()->toArray());
    }

    public function testGetShares()
    {
        $client = $this->createMock(Client::class);

        $map = [
            ['username', 'cf_token', 'cloudfile.jp', [['Id' => 'cf_share_id']]],
            ['username', 'rf_token', 'rushfiles.com', [['Id' => 'rf_share_id']]],
        ];
        $client->method('GetUserShares')->will($this->returnValueMap($map));

        $user = new User('username', [
            'cloudfile.jp' => 'cf_token',
            'rushfiles.com' => 'rf_token',
        ], $client);

        $this->assertEquals(2, count($user->getShares()));
        $this->assertEquals('cf_share_id', $user->getShare('cf_share_id')->getInternalName());
    }
}