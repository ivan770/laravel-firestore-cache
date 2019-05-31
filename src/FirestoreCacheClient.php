<?php

namespace Ivan770\Firestore;

use Carbon\Carbon;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Contracts\Cache\Store;

class FirestoreCacheClient implements Store
{
    protected $client;

    protected $collection;

    public function __construct()
    {
        $this->client = new FirestoreClient([
            'projectId' => config("cache.stores.firestore.id"),
            'keyFilePath' => config("cache.stores.firestore.key")
        ]);
        $this->collection = config("cache.stores.firestore.collection");
    }

    protected function timestamp()
    {
        return Carbon::now()->timestamp;
    }

    protected function collection()
    {
        return $this->client->collection($this->collection);
    }

    protected function ttlCheck($value, $key)
    {
        if ($value < $this->timestamp() && $value != 0) {
            $this->forget($key);
            return true;
        }
        return false;
    }

    protected function getTime($seconds)
    {
        $time = 0;
        if($seconds != -1) {
            $time = Carbon::now()->addSeconds($seconds)->timestamp;
        }
        return $time;
    }

    public function get($key)
    {
        $query = $this->collection()->document($key)->snapshot();
        if (isset($query['ttl'])) {
            if ($this->ttlCheck($query['ttl'], $key)) {
                return null;
            }
            return unserialize($query['value']);
        }
        return null;
    }

    public function many(array $keys)
    {
        $map = $keys;
        $values = [];
        foreach ($keys as $key => $value) {
            $keys[$key] = "{$this->collection}/{$value}";
        }
        $query = $this->client->documents($keys);
        foreach ($query as $key => $value) {
            $values[$map[$key]] = null;
            if ($value->exists()) {
                if ($this->ttlCheck($value['ttl'], $map[$key])) {
                    continue;
                }
                $values[$map[$key]] = unserialize($value["value"]);
            }
        }
        return $values;
    }

    public function put($key, $value, $seconds)
    {
        $time = $this->getTime($seconds);
        $this->collection()->document($key)->set([
            "value" => serialize($value),
            "ttl" => $time
        ]);
        return true;
    }

    public function putMany(array $values, $seconds)
    {
        $time = $this->getTime($seconds);
        $batch = $this->client->batch();
        foreach($values as $key => $value) {
            $document = $this->collection()->document($key);
            $batch->set($document, [
                "value" => serialize($value),
                "ttl" => $time
            ]);
        }
        $batch->commit();
        return true;
    }

    public function increment($key, $value = 1)
    {
        $document = $this->collection()->document($key);
        $snapshot = $document->snapshot();
        if(!$snapshot->exists()) {
            return false;
        }
        $cacheValue = unserialize($snapshot["value"]);
        if ($this->ttlCheck($snapshot['ttl'], $key)) {
            return false;
        }
        if(!is_int($cacheValue)) {
            return false;
        }
        $result = $cacheValue + $value;
        $document->update([
            ["path" => "value", "value" => serialize($result)]
        ]);
        return $result;
    }

    public function decrement($key, $value = 1)
    {
        return $this->increment($key, $value*-1);
    }

    public function forever($key, $value)
    {
        $this->put($key, $value, -1);
        return true;
    }

    public function forget($key)
    {
        $this->collection()->document($key)->delete();
        return true;
    }

    public function flush()
    {
        $documents = $this->client->collectionGroup($this->collection)->documents();
        foreach ($documents as $document) {
            $document->reference()->delete();
        }
        return true;
    }

    public function getPrefix()
    {
        return null;
    }
}