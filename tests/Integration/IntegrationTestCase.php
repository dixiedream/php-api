<?php

namespace Tests\Integration;

use GuzzleHttp\Client;
use models\ApiKey;
use models\ApiKeyQuery;
use PHPUnit\Framework\TestCase;

class IntegrationTestCase extends TestCase
{
    /**
     * Api key for accessing API
     * @var string
     */
    protected $key = '1s8hc8ua09ck39c9ucmskj39d9iucxkk';

    /**
     * @var Client
     */
    protected $client;

    public function __construct()
    {
        parent::__construct();
        $this->client = new Client(['base_uri' => 'http://localhost/api/v1/']);
    }

    public function createDummyApiKey(): void
    {
        $newKey = new ApiKey();
        $newKey->setExpireDate(date('Y-m-d', strtotime('tomorrow')))
            ->setKeyValue($this->key)
            ->setName('test')
            ->save();
    }

    public function destroyDummyApiKey(): void
    {
        ApiKeyQuery::create()->deleteAll();
    }
}
