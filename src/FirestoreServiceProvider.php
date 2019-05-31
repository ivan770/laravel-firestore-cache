<?php

namespace Ivan770\Firestore;

use Illuminate\Contracts\Cache\Factory;
use Illuminate\Support\ServiceProvider;

class FirestoreServiceProvider extends ServiceProvider
{
    public function boot(Factory $cache)
    {
        $cache->extend("firestore", function ($app) use ($cache) {
            return $cache->repository(new FirestoreCacheClient);
        });
    }
}