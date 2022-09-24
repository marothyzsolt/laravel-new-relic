<?php

namespace MarothyZsolt\LaravelNewRelic\Logging;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Monolog\Logger;
use Illuminate\Http\Request;
use Monolog\Handler\BufferHandler;
use NewRelic\Monolog\Enricher\{Handler, Processor};
use Throwable;

class NewRelicLogger
{
    private ?Request $request;
    private ?Throwable $throwable = null;
    private string $channel = 'single';

    public function __construct(Request $request = null)
    {
        $this->request = $request;
    }

    public function __invoke(array $config, Throwable $throwable = null): Logger
    {
        $log = new Logger('newrelic');
        $log->pushProcessor(new Processor);
        $handler = new Handler;
        $handler->setLicenseKey(config('newrelic.license_key'));

        $log->pushHandler(new BufferHandler($handler));

        $this->channel = $config['channel'] ?? debug_backtrace()[2]['args'][0];

        foreach ($log->getHandlers() as $handler) {
            $handler->pushProcessor([$this, 'includeMetaData']);
        }

        return $log;
    }

    public function includeMetaData(array $record): array
    {
        $record['hostname'] = gethostname();
        $record['environment'] = config('app.env');
        $record['service'] = config('newrelic.app_name', 'Laravel');
        $record['channel'] = $this->channel;

        $this->collectException($record);
        $this->collectExtraData($record);

        if ($this->request) {
            $record['system'] = $this->collectSystemData();
            $record['client_ips'] = join(',', $this->request->ips());

            if ($user = Auth::user()) {
                $record['user'] = $this->collectUserData($user);
            }
        }

        return $record;
    }

    private function collectSystemData(): array
    {
        $route = $this->request->route();
        $routeAction = $route?->getAction();
        $systemData = [
            'uri' => $this->request->getRequestUri(),
            'url' => $this->request->fullUrl(),
            'runtime' => now()->timestamp - LARAVEL_START,
        ];

        if (isset($routeAction['controller'])) {
            $systemData['controller'] = $routeAction['controller'];
        }
        if (isset($routeAction['middleware'])) {
            $systemData['middleware'] = is_array($routeAction['middleware']) ? join(',', $routeAction['middleware']) : $routeAction['middleware'];
        }

        return $systemData;
    }

    private function generateStackTrace(): array
    {
        $stackTrace = [];

        foreach ($this->throwable->getTrace() as $key => $item) {
            $key = str_pad($key, 3, "0", STR_PAD_LEFT);
            $arguments = '(' . join(',', $this->separateArguments($item['args'] ?? [])) . ')';
            $file = $item['file'] ?? $item['class'] ?? 'unknown';
            $line = $item['line'] ?? 'unknown';
            $function = $item['function'];

            $stackTrace[$key] = $file . ':' . $line . ':' . $function . $arguments;
        }

        return $stackTrace;
    }

    public function throwable(Throwable $e): void
    {
        $this->throwable = $e;
    }

    private function separateArguments(array $args): array
    {
        return array_map(function ($arg) {
            return match (true) {
                is_object($arg) => get_class($arg),
                is_array($arg) => json_encode($arg),
                is_resource($arg) => get_resource_type($arg),
                is_bool($arg) => $arg ? 'true' : 'false',
                is_null($arg) => 'null',
                is_string($arg) => '"' . $arg . '"',
                default => $arg,
            };
        }, $args);
    }

    private function collectUserData(Authenticatable $user): array
    {
        return [
            'id' => $user->id ?? null,
            'email' => $user->email ?? null,
        ];
    }

    private function collectExtraData(array &$record): void
    {
        /*if (config('newrelic.extra_data') && is_callable(config('newrelic.extra_data'))) {
            $extraData = config('newrelic.extra_data')($this->request, $this->throwable);
            if (is_array($extraData) && count($extraData) > 0) {
                $record['extra'] = $extraData;
            }
        }*/
    }

    private function collectException(array &$record): void
    {
        if ($this->throwable !== null && $record['level'] >= Logger::ERROR) {
            $record['exception'] = $this->generateStackTrace();
        }
    }
}
