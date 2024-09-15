<?php

namespace Project\RestServer\Broadcasting;


use Closure;
use Generator;
use \Illuminate\Broadcasting\BroadcastException;
use Pusher\ApiErrorException;

class DBBroadcast
{
    private $handler;

    public function __construct($handler)
    {
        $this->handler = new $handler();
    }

    public function broadcast($channel, $event, $request)
    {
        // TODO: Implement broadcast() method.
        $payload = [
            'db' => $channel,
            'model' => $event,
            'message' => $request->post(),
            'header' => $request->header(),
        ];

        try {
            //$channel, $event
            $data = $this->handler->trigger($payload, $request);

            new \Project\RestServer\Broadcasting\WebSocket();
        }
        catch (ApiErrorException $e) {
            throw new BroadcastException(
                sprintf('Pusher error: %s.', $e->getMessage())
            );
        }

        return $data;
    }

    public function post($channel, $event, $request)
    {
        $payload = [
            'db' => $channel,
            'model' => $event,
            'message' => $request->post(),
            'header' => $request->header(),
        ];

        try {
            //$channel, $event
            $data = $this->handler->trigger($payload, $request);
        }
        catch (ApiErrorException $e) {
            throw new BroadcastException(
                sprintf('Pusher error: %s.', $e->getMessage())
            );
        }

        return $data;
    }

    public function delete($channel, $event, $request)
    {
        $payload = [
            'db' => $channel,
            'model' => $event,
            'message' => $request->post(),
            'header' => $request->header(),
        ];

        try {
            //$channel, $event
            $data = $this->handler->trigger($payload, $request);
        }
        catch (ApiErrorException $e) {
            throw new BroadcastException(
                sprintf('Pusher error: %s.', $e->getMessage())
            );
        }

        return $data;
    }

    public function fetch($channel, $model, $request)
    {
        $payload = [
            'db' => $channel,
            'model' => $model,
            'message' => $request->get('query', null) ? json_decode(urldecode($request->get('query')), true) : $request->post(),
            'header' => $request->header(),
        ];

        try {
            //$channel, $event
            $data = $this->handler->trigger($payload, $request);

            if ($data instanceof Generator || $data instanceof Closure || $data instanceof \Illuminate\Support\LazyCollection) {

                $rows = iterator_to_array($data);
            }
            else {

                $rows = $data;
            }
        }
        catch (ApiErrorException $e) {
            throw new BroadcastException(
                sprintf('Pusher error: %s.', $e->getMessage())
            );
        }

        return $rows;
    }
}
