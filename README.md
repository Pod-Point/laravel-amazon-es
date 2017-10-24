# laravel-amazon-es

[![Packagist](https://img.shields.io/packagist/v/Pod-Point/laravel-amazon-es.svg)](https://packagist.org/packages/pod-point/laravel-amazon-es) [![Build Status](https://travis-ci.org/Pod-Point/laravel-amazon-es.svg?branch=master)](https://travis-ci.org/Pod-Point/laravel-amazon-es)

Laravel provider for signing AWS Elasticsearch Service requests using the [amazon-es-php](https://github.com/jeskew/amazon-es-php) package.

## Installation

Add the following line to your `composer.json` file:

```
"pod-point/laravel-amazon-es": "^0.1"
```

Then add the service provider in `config/app.php`:

```
PodPoint\LaravelAmazonElasticsearch\Providers\ElasticsearchProvider::class
```

Publish the config file and add the following to your `.env` file:

```
ELASTICSEARCH_HOST=
AWS_KEY=
AWS_SECRET=
AWS_REGION=
```
