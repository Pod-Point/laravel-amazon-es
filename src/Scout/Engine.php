<?php

namespace PodPoint\LaravelAmazonElasticsearch\Scout;

use Elasticsearch\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine as ScoutEngine;

class Engine extends ScoutEngine
{
    /**
     * The Elasticsearch client instance.
     *
     * @var Client
     */
    protected $client;

    /**
     * The index name.
     *
     * @var string
     */
    protected $index;

    /**
     * Create a new engine instance.
     *
     * @param  Client $client
     * @param string $index
     */
    public function __construct(Client $client, $index)
    {
        $this->client = $client;
        $this->index = $index;
    }

    /**
     * Update the given model in the index.
     *
     * @param  Collection $models
     * @return void
     */
    public function update($models)
    {
        $body = new BaseCollection();
        $models->each(function ($model) use ($body) {
            $array = $model->toSearchableArray();
            if (empty($array)) {
                return;
            }
            $body->push([
                'index' => [
                    '_index' => $this->index,
                    '_type'  => $model->searchableAs(),
                    '_id'    => $model->getKey(),
                ],
            ]);
            $body->push($array);
        });
        $this->client->bulk([
            'refresh' => true,
            'body'    => $body->all(),
        ]);
    }

    /**
     * Remove the given model from the index.
     *
     * @param  Collection $models
     * @return void
     */
    public function delete($models)
    {
        $body = new BaseCollection();
        $models->each(function ($model) use ($body) {
            $body->push([
                'delete' => [
                    '_index' => $this->index,
                    '_type'  => $model->searchableAs(),
                    '_id'    => $model->getKey(),
                ],
            ]);
        });
        $this->client->bulk([
            'refresh' => true,
            'body'    => $body->all(),
        ]);
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  Builder $query
     * @return mixed
     */
    public function search(Builder $query)
    {
        return $this->performSearch($query, [
            'filters' => $this->filters($query),
            'size'    => $query->limit ?: 10000,
        ]);
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  Builder $query
     * @param  int     $perPage
     * @param  int     $page
     * @return mixed
     */
    public function paginate(Builder $query, $perPage, $page)
    {
        $result = $this->performSearch($query, [
            'filters' => $this->filters($query),
            'size'    => $perPage,
            'from'    => (($page * $perPage) - $perPage),
        ]);
        $result['nbPages'] = (int) ceil($result['hits']['total'] / $perPage);

        return $result;
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  Builder $builder
     * @param  array   $options
     * @return mixed
     */
    protected function performSearch(Builder $builder, array $options = [])
    {
        $body = [];

        $shouldMatchMinimum = 0;

        if (!empty($builder->query)) {
            $body['query']['bool']['must']['match']['_all'] = [
                'query' => $builder->query,
                'fuzziness' => 1,
            ];
        }

        foreach ($builder->wheres as $where) {
            switch ($where['eq']) {
                case '=':
                    $body['query']['bool']['should'][] = ['match' => [$where['field'] => $where['value']]];
                    $shouldMatchMinimum ++;
                    break;

                case '!=':
                    if ($where['value'] == 'NULL') {
                        $body['query']['bool']['should'][] = ['exists' => ['field' => $where['field']]];
                    } else {
                        $body['query']['bool']['must_not'][] = ['match' => [$where['field'] => $where['value']]];
                    }
                    break;

                case '>=':
                    $body['query']['bool']['filter']['range'][$where['field']]['gte'] = $where['value'];
                    break;

                case '<=':
                    $body['query']['bool']['filter']['range'][$where['field']]['lte'] = $where['value'];
                    break;
            }
        }

        if ($shouldMatchMinimum > 0) {
            $body['query']['bool']['minimum_should_match'] = $shouldMatchMinimum;
        }

        if (count($builder->orders)) {
            foreach ($builder->orders as $order) {
                $body['sort'][][$order['column']] = $order['direction'];
            }
        }

        $params = [
            'index' => $this->index,
            'type'  => $builder->model->searchableAs(),
            'body'  => $body,
        ];

        if (array_key_exists('size', $options)) {
            $params['size'] = $options['size'];
        }

        if (array_key_exists('from', $options)) {
            $params['from'] = $options['from'];
        }

        if ($builder->callback) {
            return call_user_func($builder->callback, $this->client, $params);
        }

        return $this->client->search($params);
    }

    /**
     * Get the filter array for the query.
     *
     * @param  Builder $query
     * @return array
     */
    protected function filters(Builder $query)
    {
        return $query->wheres;
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param  mixed                               $results
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return Collection
     */
    public function map($results, $model)
    {
        if (count($results['hits']) === 0) {
            return Collection::make();
        }
        $keys = collect($results['hits']['hits'])
            ->pluck('_id')
            ->values()
            ->all();
        $models = $model->whereIn($model->getQualifiedKeyName(), $keys)
            ->withTrashed()
            ->get()
            ->keyBy($model->getKeyName());

        return Collection::make($results['hits']['hits'])
            ->map(function ($hit) use ($model, $models) {
                return isset($models[$hit['_source'][$model->getKeyName()]]) ? $models[$hit['_source'][$model->getKeyName()]] : null;
            })
            ->filter()
            ->values();
    }

    /**
     *
     * Pluck and return the primary keys of the results.
     *
     * @param  mixed                            $results
     * @return \Illuminate\Support\Collection
     */
    public function mapIds($results)
    {
        return collect($results['hits']['hits'])
            ->pluck('_id')
            ->values()
            ->all();
    }

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param  mixed $results
     * @return int
     */
    public function getTotalCount($results)
    {
        return $results['hits']['total'];
    }
}
