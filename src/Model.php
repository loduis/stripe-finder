<?php

namespace Stripe\Tools\Finder;

use Stripe\Stripe;

abstract class Model
{
    const MAX_LIMIT = 100;

    protected $index = 0;

    public function __construct($key)
    {
        Stripe::setApiKey($key);
    }

    public function all(...$params)
    {
        $filter = null;
        $options = [];
        foreach ($params as $param) {
            if (is_callable($param)) {
                $filter = $param;
            } elseif (is_array($param)) {
                $options = $param;
            }
        }
        if (isset($options['limit']) && $options['limit'] > static::MAX_LIMIT) {
            $options['limit'] = static::MAX_LIMIT;
        }
        $this->index = 0;
        yield from $this->findAll($options, $filter);
    }

    public function allAsArray(...$params)
    {
        return iterator_to_array($this->all(...$params));
    }

    protected function findAll(array $params, callable $filter = null)
    {
        if ($filter === null) {
            $filter = function ($object) { return true; };
        }
        $finder = static::FINDER;
        $objects = $finder::all($params);
        $data = $objects->data;
        foreach ($data as $object) {
            if ($filter($object)) {
                $index = $this->index ++;
                yield $index => $object;
            }
        }
        if ($objects->has_more) {
            usleep(1000);
            $last = end($data);
            $params['starting_after'] = $last->id;
            yield from $this->findAll($params);
        }
    }
}
