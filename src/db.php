<?php

/**
 * @return MongoDB\Driver\Manager
 */
function getMongoManager(): MongoDB\Driver\Manager {
    static $manager;
    if (!$manager) {
        // host „mongo” bo z docker-compose
        $uri = getenv('MONGO_URI') ?: 'mongodb://mongo:27017';
        $manager = new MongoDB\Driver\Manager($uri);
    }
    return $manager;
}

/**
 * @return Redis
 */
function getRedisClient(): Redis {
    static $redis;
    if (!$redis) {
        $redis = new Redis();
        $host  = getenv('REDIS_HOST') ?: 'redis';
        $port  = (int)(getenv('REDIS_PORT') ?: 6379);
        $redis->connect($host, $port);
    }
    return $redis;
}
