<?php

namespace PodPoint\LaravelAmazonElasticsearch\Providers;

use Elasticsearch\Client;
use Illuminate\Support\ServiceProvider;
use Aws\Credentials\CredentialProvider;
use Aws\Credentials\Credentials;
use Elasticsearch\ClientBuilder;
use Aws\ElasticsearchService\ElasticsearchPhpHandler;

class ElasticsearchProvider extends ServiceProvider
{
    /**
     * Create a new Elasticsearch client that uses signed AWS credentials.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton(Client::class, function () {
            $client = ClientBuilder::create();

            $provider = CredentialProvider::fromCredentials(
                new Credentials(env('AWS_KEY'), env('AWS_SECRET'))
            );

            $handler = new ElasticsearchPhpHandler(env('AWS_REGION', 'eu-west-1'), $provider);

            return $client->setHandler($handler)->build();
        });
    }
}
