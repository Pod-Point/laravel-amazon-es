<?php

namespace PodPoint\LaravelAmazonElasticsearch\Providers;

use Elasticsearch\Client;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use PodPoint\LaravelAmazonElasticsearch\Scout\Engine;

class ScoutProvider extends ServiceProvider
{
    /**
     * Create a new scout engine that uses signed AWS credentials
     *
     * @param Repository $config
     * @return void
     */
    public function boot(Repository $config)
    {
        resolve(EngineManager::class)->extend('signed-elasticsearch', function (Application $app) {
            $client = $app->make(Client::class);

            return new Engine($client, config('scout.elasticsearch.index'));
        });
    }
}
