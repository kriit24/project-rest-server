<?php

namespace Project\RestServer\Broadcasting;

class WebSocket
{
    public function __construct()
    {
        /*
        if (env('BROADCAST_DSN')) {

            $context = stream_context_create();
            stream_context_set_option($context, 'ssl', 'verify_peer', false);
            stream_context_set_option($context, 'ssl', 'verify_peer_name', false);

            try {

                $client = new \WebSocket\Client(env('BROADCAST_DSN') . '/' . $channel .
                    '/?uuid=' . $request->server('HTTP_UUID') . '&token=' . $request->server('HTTP_TOKEN'), [
                    'context' => $context, // Attach stream context created above
                    'timeout' => 60, // 1 minute time out
                ]);

                $client->text(json_encode([
                    'event' => $event,
                    'message' => $data
                ], JSON_UNESCAPED_UNICODE));

                //echo $client->receive();
                $client->close();
            }
            catch (\Exception $e) {

                //pre($e->getMessage());
                //abort(404, "Cannot connect to WSS server");
            }
        }
        */
    }
}
