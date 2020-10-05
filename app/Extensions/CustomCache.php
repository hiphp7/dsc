<?php

namespace App\Extensions;

use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\CacheInterface;

/**
 * Class CustomCache
 * @package App\Extensions
 */
class CustomCache implements CacheInterface
{
    public function get($key, $default = null)
    {
        return Cache::get($key, $default);
    }

    public function set($key, $value, $ttl = null)
    {
        return Cache::put($key, $value, $ttl);
    }

    public function delete($key)
    {
        return Cache::forget($key);
    }

    public function clear()
    {
        // TODO: Implement clear() method.
    }

    public function getMultiple($keys, $default = null)
    {
        // TODO: Implement getMultiple() method.
    }

    public function setMultiple($values, $ttl = null)
    {
        // TODO: Implement setMultiple() method.
    }

    public function deleteMultiple($keys)
    {
        // TODO: Implement deleteMultiple() method.
    }

    public function has($key)
    {
        return Cache::has($key);
    }
}
