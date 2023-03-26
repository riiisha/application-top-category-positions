<?php

namespace App\Service;

use App\Repository\PositionsRepository;
use DateInterval;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

class PositionCacheService
{
    private string $defaultTtl = 'PT1H';
    private PositionsRepository $positionsRepository;
    private CacheItemPoolInterface $cache;

    /**
     * NewsService constructor.
     *
     */
    public function __construct(
        PositionsRepository    $positionsRepository,
        CacheItemPoolInterface $cache,
    )
    {
        $this->positionsRepository = $positionsRepository;
        $this->cache = $cache;
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function getCachedPositionByDate($date)
    {
        $cacheKey = $date;
        $item = $this->cache->getItem($cacheKey);

        if (!$item->isHit()) {
            $positions = $this->positionsRepository->getPositionByDate($date);
            $item->set($positions);
            $item->expiresAfter(new DateInterval($this->defaultTtl));
            $this->cache->save($item);
        }

        return $item->get();
    }
}
