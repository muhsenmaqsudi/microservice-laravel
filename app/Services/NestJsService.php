<?php


namespace App\Services;


use Illuminate\Redis\RedisManager;
use Illuminate\Support\Str;
use Predis\PubSub\Consumer;

class NestJsService
{
    /**
     * @var RedisManager $redis
     */
    protected $redis;

    public function __construct(RedisManager $redis)
    {
        $this->redis = $redis;
    }

    public function send($pattern, $data = null)
    {
        $payload = $this->newPayload($pattern, $data);
        return $this->callNestMicroservice($payload);
    }

    /**
     * Create new UUID
     * @return string
     */
    protected function newUuid()
    {
        return Str::uuid()->toString();
    }

    /**
     * Create new collection
     * @return \Illuminate\Support\Collection
     */
    protected function newCollection()
    {
        return collect();
    }

    protected function newPayload($pattern, $data)
    {
        return [
            'id' => $this->newUuid(),
            'pattern' => $pattern['cmd'],
            'data' => $data
        ];
    }

    /**
     * Make request to microservice
     *
     * @param array $payload
     * @return \Illuminate\Support\Collection
     */
    protected function callNestMicroservice($payload)
    {
        $uuid = $payload['id'];
        $pattern = $payload['pattern'];
        // Subscribe to the response channel
        /** @var Consumer $loop */
        $loop = $this->redis->connection('pubsub')
            ->pubSubLoop(['subscribe' => "{$pattern}.reply"]);
        // Send payload across the request channel
        $this->redis->connection('default')
            ->publish("{$pattern}", json_encode($payload));
        // Create a collection to store response(s); there could be multiple!
        // (e.g., if NestJS returns an observable)
        $result = $this->newCollection();
        // Loop through the response object(s), pushing the returned vals into
        // the collection.  If isDisposed is true, break out of the loop.
        foreach ($loop as $msg) {
            if ($msg->kind === 'message') {
                $res = json_decode($msg->payload);
                if ($res->id === $uuid) {
                    $result->push($res->response);
                    if (property_exists($res, 'isDisposed') && $res->isDisposed) {
                        $loop->stop();
                    }
                }
            }
        }
        return $result; // return the collection
    }
}
