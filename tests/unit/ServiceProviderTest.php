<?php

use PodPoint\LaravelAmazonElasticsearch\Providers\ElasticsearchProvider;
use Elasticsearch\Client;

class ServiceProviderTest extends TestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ElasticsearchProvider::class,
        ];
    }

    /**
     * Ensures laravel can return an ES client.
     */
    public function testClientSetUp()
    {
        $client = $this->app->make(Client::class);

        $this->assertInstanceOf(Client::class, $client);
    }
}
