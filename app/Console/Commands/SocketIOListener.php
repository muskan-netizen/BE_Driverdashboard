<?php

namespace App\Console\Commands;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\Http\HttpBridge;
use Illuminate\Console\Command;

class SocketIOListener extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:socket';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */

    public function handle()
    {
        $host = 'dev-rochat.netsolutionindia.com';
        $port = 443;
        $path = '/';

        $origin = 'http://localhost/';

        $socket = stream_socket_client('tls://' . $host . ':' . $port, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]));

        if (!$socket) {
            die("Failed to connect: $errstr ($errno)\n");
        }

        // Construct the WebSocket handshake request headers
        $headers = [
            'Host: ' . $host,
            'Upgrade: websocket',
            'Connection: Upgrade',
            'Sec-WebSocket-Key: ' . base64_encode(random_bytes(16)),
            'Sec-WebSocket-Version: 13',
            'Origin: ' . $origin,
        ];

        // Send the WebSocket handshake request
        // $path = "/socket.io/1/?t=".time();

        $path = "/socket.io/?EIO=3&transport=websocket";

        fwrite($socket, "GET $path HTTP/1.1\r\n" . implode("\r\n", $headers) . "\r\n\r\n");

        // Read the WebSocket handshake response
        $response = stream_get_line($socket, 8192, "\r\n\r\n");
        echo $response;

        // Check if the handshake was successful
        if (strpos($response, ' 101 ') !== false) {
            echo "WebSocket handshake successful!\n";
            stream_set_blocking($socket, 0);

            // Start listening for incoming messages
            while (true) {
                $data = fread($socket, 8192);
                if ($data !== false && $data !== '') {
                    echo "Received data: $data\n";
                    // Handle the received data as needed
                }
            }
            fclose($socket);
        } else {
            echo "WebSocket handshake failed.\n";
        }

    }

    // private function handleWebSocket(ConnectionInterface $connection)
    // {
    //     // Handle WebSocket communication here
    //     $event = "custom-event";
    //     $data = "this is the data!!!";
    //     $emitMessage = '42["emit",{"event":"' . $event . '","data":"' . $data . '"}]';
    //     $connection->write($emitMessage);

    //     // Listen for incoming WebSocket messages
    //     $connection->on('custom-event', function ($data) {
    //         echo "Received WebSocket data: $data\n";

    //         // Implement your WebSocket logic here
    //     });

    //     // Listen for the WebSocket connection to close
    //     $connection->on('close', function () {
    //         echo "WebSocket Connection closed\n";
    //     });
    // }

    // public function test()
    // {
    //     // Replace this with the actual URL of your Socket.IO server
    //     $socketIoServerUrl = 'https://dev-rochat.netsolutionindia.com';


    //     // Parse the Socket.IO server URL
    //     $urlParts = parse_url($socketIoServerUrl);

    //     if ($urlParts === false || !isset($urlParts['scheme'])) {
    //         echo "Invalid Socket.IO server URL\n";
    //         exit(1);
    //     }

    //     $scheme = $urlParts['scheme'];
    //     $host = $urlParts['host'];
    //     $port = isset($urlParts['port']) ? $urlParts['port'] : ($scheme === 'https' ? 443 : 80);

    //     $host = 'dev-rochat.netsolutionindia.com';
    //     $port = 443;
    //     $path = '/';
    //     $origin = 'http://localhost';  // Replace with your actual origin

    //     // Construct the WebSocket handshake request headers
    //     $headers = [
    //         'Host: ' . $host,
    //         'Upgrade: websocket',
    //         'Connection: Upgrade',
    //         'Sec-WebSocket-Key: ' . base64_encode(random_bytes(16)),
    //         'Sec-WebSocket-Version: 13',
    //         'Origin: ' . $origin,
    //         'Accept: */*'
    //     ];

    //     $loop = Factory::create();
    //     $connector = new Connector($loop);

    //     $connector->connect("tls://$host:$port")->then(function (\React\Socket\ConnectionInterface $connection) use ($headers, $path, $loop) {
    //         echo "WebSocket connection established!\n";

    //         // Send the WebSocket handshake request
    //         // $handshake = "GET $path HTTP/1.1\r\n" . implode("\r\n", $headers) . "\r\n\r\n";
    //         // echo $handshake;
    //         $connection->write("GET $path HTTP/1.1\r\n" . implode("\r\n", $headers) . "\r\n\r\n");

    //         // Listen for incoming messages
    //         $connection->on('data', function ($data) {
    //             echo "Received data: $data\n";

    //             // Handle the received data as needed
    //         });

    //         // $webSocket = new WebSocket($connection, $loop);

    //         // // Periodically send a ping to keep the connection alive
    //         // $loop->addPeriodicTimer(30, function () use ($webSocket) {
    //         //     $webSocket->send('ping');
    //         // });

    //         // Close the connection after sending the message
    //         $connection->on('close', function () {
    //             echo "Connection closed.\n";
    //         });
    //     }, function (\Exception $e) {
    //         echo "Failed to establish WebSocket connection. " . $e->getMessage() . "\n";
    //     });

    //     $loop->run();
    // }
    }