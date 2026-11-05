<?php

namespace Illuminate\Support {
    class ServiceProvider
    {
        public $mergedConfig;

        protected function mergeConfigFrom($path, $key)
        {
            $this->mergedConfig = [$path, $key];
        }
    }
}

namespace Yurstore\ShipEngineAPI\ShipEngineRequest {
    function curl_init()
    {
        return \TestState::$mock
            ? (\TestState::$initFailure ? false : new \stdClass())
            : \curl_init();
    }

    function curl_setopt_array($handle, $options)
    {
        \TestState::$options = $options;
        if (!\TestState::$mock) {
            return \curl_setopt_array($handle, $options);
        }
        if (\TestState::$optionException) {
            throw new \ValueError('Invalid cURL option.');
        }
        return !\TestState::$optionFailure;
    }

    function curl_exec($handle)
    {
        \TestState::$executions++;
        return \TestState::$mock ? \TestState::$response : \curl_exec($handle);
    }

    function curl_error($handle)
    {
        return \TestState::$mock ? 'Operation timed out' : \curl_error($handle);
    }

    function curl_close($handle)
    {
        \TestState::$closures++;
        if (!\TestState::$mock) {
            \curl_close($handle);
        }
    }
}

namespace {
    use Yurstore\ShipEngineAPI\ShipEngineException\Failed;
    use Yurstore\ShipEngineAPI\ShipEngineException\Response;
    use Yurstore\ShipEngineAPI\ShipEngineRequest\Factory;
    use Yurstore\ShipEngineAPI\ShipEngineRequest\Request;

    class TestState
    {
        public static $mock = true;
        public static $initFailure = false;
        public static $optionFailure = false;
        public static $optionException = false;
        public static $options = [];
        public static $response = '{"ok":true}';
        public static $executions = 0;
        public static $closures = 0;
        public static $config = [];
        public static $environment = [];
    }

    class Log
    {
        public static $entries = [];

        public static function info($data)
        {
            self::$entries[] = $data;
        }
    }

    function config($key, $default = null)
    {
        return array_key_exists($key, TestState::$config)
            ? TestState::$config[$key]
            : $default;
    }

    function env($key, $default = null)
    {
        return TestState::$environment[$key] ?? $default;
    }

    spl_autoload_register(function ($class) {
        $prefix = 'Yurstore\\ShipEngineAPI\\';
        if (strpos($class, $prefix) === 0) {
            require __DIR__ . '/../src/' . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix))) . '.php';
        }
    });

    function check($condition, $message)
    {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }

    function expectException($class, $callback)
    {
        try {
            $callback();
        } catch (\Throwable $error) {
            check($error instanceof $class, 'Unexpected exception: ' . get_class($error) . ': ' . $error->getMessage());
            return $error;
        }
        throw new \RuntimeException('Expected exception: ' . $class);
    }

    function checkReleased($request)
    {
        $property = new \ReflectionProperty(Request::class, 'curl_handle');
        check($property->getValue($request) === null, 'Request retained its cURL handle.');
    }

    $provider = new \Yurstore\ShipEngineAPI\YurstoreShipEngineServiceProvider();
    $provider->register();
    check($provider->mergedConfig[1] === 'shipengine', 'Wrong Laravel configuration key.');
    $defaults = require $provider->mergedConfig[0];
    check($defaults === ['connect_timeout' => 5, 'timeout' => 30], 'Provider timeout defaults changed.');
    TestState::$environment = ['SHIPENGINE_CONNECT_TIMEOUT' => '3', 'SHIPENGINE_TIMEOUT' => '9'];
    $configured = require $provider->mergedConfig[0];
    check($configured === ['connect_timeout' => '3', 'timeout' => '9'], 'Environment configuration not wired.');

    $request = new Request([]);
    check($request->send() === ['ok' => true], 'Successful response changed.');
    check(TestState::$options[CURLOPT_CONNECTTIMEOUT] === 5, 'Connection default missing.');
    check(TestState::$options[CURLOPT_TIMEOUT] === 30, 'Total timeout default missing.');
    check(TestState::$closures === 1, 'Success did not close cURL.');
    checkReleased($request);
    check($request->send() === ['ok' => true], 'Repeated sends no longer work.');
    check(TestState::$closures === 2, 'Repeated send did not close cURL.');

    check(Factory::listCarriers() === ['ok' => true], 'Factory default response changed.');
    check(TestState::$options[CURLOPT_CONNECTTIMEOUT] === 5, 'Factory connection fallback missing.');
    check(TestState::$options[CURLOPT_TIMEOUT] === 30, 'Factory total timeout fallback missing.');

    TestState::$config = [
        'shipengine.api_key' => 'test-only',
        'shipengine.connect_timeout' => '2',
        'shipengine.timeout' => '7',
    ];
    check(Factory::listCarriers() === ['ok' => true], 'Factory response changed.');
    check(TestState::$options[CURLOPT_CONNECTTIMEOUT] === 2, 'Configured connection timeout not applied.');
    check(TestState::$options[CURLOPT_TIMEOUT] === 7, 'Configured total timeout not applied.');
    check(TestState::$options[CURLOPT_HTTPGET] === true, 'HTTP method changed.');
    check(TestState::$options[CURLOPT_HTTPHEADER][1] === 'api-key: test-only', 'API key changed.');

    foreach ([0, -1, null, false, true, 'invalid', 1.5, 1.0] as $invalid) {
        foreach ([CURLOPT_CONNECTTIMEOUT, CURLOPT_TIMEOUT] as $option) {
            expectException(\InvalidArgumentException::class, function () use ($option, $invalid) {
                new Request([$option => $invalid]);
            });
        }
    }
    TestState::$config['shipengine.timeout'] = 0;
    expectException(\InvalidArgumentException::class, function () {
        Factory::listCarriers();
    });

    $closures = TestState::$closures;
    $executions = TestState::$executions;
    TestState::$response = false;
    $error = expectException(Failed::class, function () use ($request) {
        $request->send();
    });
    check($error->getMessage() === 'Operation timed out', 'Transport error message changed.');
    check(TestState::$executions === $executions + 1, 'Transport failure was retried.');
    check(TestState::$closures === ++$closures, 'Transport failure did not close cURL.');
    checkReleased($request);

    TestState::$response = '{"request_id":"test-request","errors":[]}';
    check($request->send() === ['request_id' => 'test-request', 'errors' => []], 'Empty API errors changed.');
    $closures++;
    TestState::$response = '{"request_id":"test-request","errors":[{"error_source":"shipengine","error_type":"validation","error_code":"test","message":"Rejected"}]}';
    expectException(Response::class, function () use ($request) {
        $request->send();
    });
    check(count(Log::$entries) === 1, 'API error was not logged.');
    check(TestState::$closures === ++$closures, 'API exception did not close cURL.');
    checkReleased($request);

    TestState::$optionFailure = true;
    expectException(Failed::class, function () use ($request) {
        $request->send();
    });
    check(TestState::$closures === ++$closures, 'Configuration failure did not close cURL.');
    TestState::$optionFailure = false;
    TestState::$optionException = true;
    expectException(\ValueError::class, function () use ($request) {
        $request->send();
    });
    check(TestState::$closures === ++$closures, 'Configuration exception did not close cURL.');
    checkReleased($request);
    TestState::$optionException = false;
    TestState::$initFailure = true;
    expectException(Failed::class, function () use ($request) {
        $request->send();
    });
    check(TestState::$closures === $closures, 'Closed a failed initialization.');
    TestState::$initFailure = false;

    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $message);
    check($socket !== false, 'Unable to reserve local test port: ' . $message);
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    $process = proc_open(
        [PHP_BINARY, '-S', $address, __DIR__ . '/http-fixture.php'],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes
    );
    check(is_resource($process), 'Unable to start local HTTP fixture.');

    try {
        $ready = false;
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $probe = @stream_socket_client('tcp://' . $address, $errno, $message, 0.1);
            if ($probe !== false) {
                fclose($probe);
                $ready = true;
                break;
            }
            if (!proc_get_status($process)['running']) {
                throw new \RuntimeException('HTTP fixture exited: ' . stream_get_contents($pipes[2]));
            }
            usleep(100000);
        }
        check($ready, 'Local HTTP fixture did not become ready.');
        TestState::$mock = false;
        $options = [
            CURLOPT_URL => 'http://' . $address . '/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_PROXY => '',
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_TIMEOUT => 1,
        ];
        $realRequest = new Request($options);
        check($realRequest->send() === ['ok' => true], 'Real HTTP success failed.');
        checkReleased($realRequest);

        $options[CURLOPT_URL] .= 'slow';
        $slowRequest = new Request($options);
        $start = microtime(true);
        $error = expectException(Failed::class, function () use ($slowRequest) {
            $slowRequest->send();
        });
        $elapsed = microtime(true) - $start;
        check($elapsed >= 0.8 && $elapsed < 2.5, 'One-second timeout not enforced: ' . $elapsed);
        check(stripos($error->getMessage(), 'timed out') !== false, 'Real timeout error not preserved.');
        checkReleased($slowRequest);
        echo "PASS: defaults, configuration, validation, reuse, failures, cleanup, and real HTTP timeout ({$elapsed}s).\n";
    } finally {
        proc_terminate($process);
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }
        proc_close($process);
    }
}
