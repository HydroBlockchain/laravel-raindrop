<?php

declare(strict_types=1);

namespace Adrenth\LaravelHydroRaindrop\Api;

use Adrenth\Raindrop;
use Adrenth\Raindrop\ApiAccessToken;
use Adrenth\Raindrop\Exception\UnableToAcquireAccessToken;
use Illuminate\Cache\Repository;

/**
 * Class CacheTokenStorage
 *
 * @package Adrenth\LaravelHydroRaindrop
 */
class CacheTokenStorage implements Raindrop\TokenStorage\TokenStorage
{
    private const CACHE_KEY = 'adrenth_raindrop_token';

    /**
     * @var Repository
     */
    private $cache;

    /**
     * @param Repository $cache
     */
    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessToken(): ApiAccessToken
    {
        if (!$this->cache->has(self::CACHE_KEY)) {
            throw new UnableToAcquireAccessToken('Access Token is not found in the storage.');
        }

        $token = $this->cache->get(self::CACHE_KEY);

        if ($token instanceof ApiAccessToken) {
            return $token;
        }

        $this->unsetAccessToken();

        throw new UnableToAcquireAccessToken('Access Token is not found in the storage.');
    }

    /**
     * {@inheritDoc}
     */
    public function setAccessToken(ApiAccessToken $token)
    {
        $this->cache->forever(self::CACHE_KEY, $token);
    }

    /**
     * {@inheritDoc}
     */
    public function unsetAccessToken()
    {
        $this->cache->forget(self::CACHE_KEY);
    }
}
