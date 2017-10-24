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
        $this->publishes([
            __DIR__.'/../config/elasticsearch.php' => config_path('elasticsearch.php'),
        ]);

        $this->app->singleton(Client::class, function () {
            $client = ClientBuilder::create();

            $provider = CredentialProvider::fromCredentials(
                new Credentials(config('elasticsearch.aws.key'), config('elasticsearch.aws.secret'))
            );

            $handler = new ElasticsearchPhpHandler(config('elasticsearch.aws.region'), $provider);

            return $client->setHandler($handler)
                ->setHosts(config('elasticsearch.hosts'))
                ->build();
        });
    }
}
