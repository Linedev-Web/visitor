<?php

namespace Shetabit\Visitor;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use ReflectionClass;
use Shetabit\Visitor\Contracts\UserAgentParser;
use Shetabit\Visitor\Exceptions\DriverNotFoundException;
use Shetabit\Visitor\Models\Visit;

class Visitor implements UserAgentParser
{
    /**
     * except.
     *
     * @var array
     */
    protected $except;
    /**
     * Configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * Driver name.
     *
     * @var string
     */
    protected $driver;

    /**
     * Driver instance.
     *
     * @var object
     */
    protected $driverInstance;

    /**
     * Request instance.
     *
     * @var Request
     */
    protected $request;

    /**
     * Visitor (user) instance.
     *
     * @var Model|null
     */
    protected $visitor;

    /**
     * Visitor constructor.
     *
     *
     * @throws Exception
     */
    public function __construct(Request $request, $config)
    {
        $this->request = $request;
        $this->config = $config;
        $this->except = $config['except'];
        $this->via($this->config['default']);
        $this->setVisitor($request->user());
    }

    /**
     * Change the driver on the fly.
     *
     *
     * @return $this
     *
     * @throws Exception
     */
    public function via($driver)
    {
        $this->driver = $driver;
        $this->validateDriver();

        return $this;
    }

    /**
     * Retrieve request's data
     */
    public function request(): array
    {
        return $this->request->all();
    }

    /**
     * Retrieve user's ip.
     */
    public function ip(): ?string
    {
        return $this->request->ip();
    }

    /**
     * Retrieve request's url
     */
    public function url(): string
    {
        return $this->request->fullUrl();
    }

    /**
     * Retrieve request's referer
     */
    public function referer(): ?string
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }

    /**
     * Retrieve request's method.
     */
    public function method(): string
    {
        return $this->request->getMethod();
    }

    /**
     * Retrieve http headers.
     */
    public function httpHeaders(): array
    {
        return $this->request->headers->all();
    }

    /**
     * Retrieve agent.
     */
    public function userAgent(): string
    {
        return $this->request->userAgent() ?? '';
    }

    /**
     * Retrieve device's name.
     *
     *
     * @throws Exception
     */
    public function device(): string
    {
        return $this->getDriverInstance()->device();
    }

    /**
     * Retrieve platform's name.
     *
     *
     * @throws Exception
     */
    public function platform(): string
    {
        return $this->getDriverInstance()->platform();
    }

    /**
     * Retrieve browser's name.
     *
     *
     * @throws Exception
     */
    public function browser(): string
    {
        return $this->getDriverInstance()->browser();
    }

    /**
     * Retrieve languages.
     *
     *
     * @throws Exception
     */
    public function languages(): array
    {
        return $this->getDriverInstance()->languages();
    }

    /**
     * Set visitor (user)
     *
     *
     * @return $this
     */
    public function setVisitor(?Model $user)
    {
        $this->visitor = $user;

        return $this;
    }

    /**
     * Retrieve visitor (user)
     */
    public function getVisitor(): ?Model
    {
        return $this->visitor;
    }

    /**
     * Create a visit log.
     */
    public function visit(?Model $model = null)
    {
        foreach ($this->except as $path) {
            if ($this->request->is($path)) {
                return;
            }
        }

        $data = $this->prepareLog();

        if ($model !== null && method_exists($model, 'visitLogs')) {
            $visit = $model->visitLogs()->create($data);
        } else {
            $visit = Visit::create($data);
        }

        return $visit;
    }

    /**
     * Retrieve online visitors.
     *
     * @param  int  $seconds
     */
    public function onlineVisitors(string $model, $seconds = 180)
    {
        return app($model)->online()->get();
    }

    /**
     * Determine if given visitor or current one is online.
     *
     * @param  int  $seconds
     * @return bool
     */
    public function isOnline(?Model $visitor = null, $seconds = 180)
    {
        $time = now()->subSeconds($seconds);

        $visitor = $visitor ?? $this->getVisitor();

        if (empty($visitor)) {
            return false;
        }

        return Visit::whereHasMorph('visitor', get_class($visitor), function ($query) use ($visitor) {
            $query->where('visitor_id', $visitor->id);
        })->whereDate('created_at', '>=', $time)->count() > 0;
    }

    /**
     * Prepare log's data.
     *
     *
     * @throws Exception
     */
    protected function prepareLog(): array
    {
        return [
            'method' => $this->method(),
            'request' => $this->request(),
            'url' => $this->url(),
            'referer' => $this->referer(),
            'languages' => $this->languages(),
            'useragent' => $this->userAgent(),
            'headers' => $this->httpHeaders(),
            'device' => $this->device(),
            'platform' => $this->platform(),
            'browser' => $this->browser(),
            'ip' => $this->ip(),
            'visitor_id' => $this->getVisitor() ? $this->getVisitor()->id : null,
            'visitor_type' => $this->getVisitor() ? get_class($this->getVisitor()) : null,
        ];
    }

    /**
     * Retrieve current driver instance or generate new one.
     *
     * @return mixed|object
     *
     * @throws Exception
     */
    protected function getDriverInstance()
    {
        if (! empty($this->driverInstance)) {
            return $this->driverInstance;
        }

        return $this->getFreshDriverInstance();
    }

    /**
     * Get new driver instance
     *
     * @return Driver
     *
     * @throws Exception
     */
    protected function getFreshDriverInstance()
    {
        $this->validateDriver();

        $driverClass = $this->config['drivers'][$this->driver];

        return app($driverClass);
    }

    /**
     * Validate driver.
     *
     * @throws Exception
     */
    protected function validateDriver()
    {
        if (empty($this->driver)) {
            throw new DriverNotFoundException('Driver not selected or default driver does not exist.');
        }

        $driverClass = $this->config['drivers'][$this->driver];

        if (empty($driverClass) || ! class_exists($driverClass)) {
            throw new DriverNotFoundException('Driver not found in config file. Try updating the package.');
        }

        $reflect = new ReflectionClass($driverClass);

        if (! $reflect->implementsInterface(UserAgentParser::class)) {
            throw new Exception("Driver must be an instance of Contracts\Driver.");
        }
    }
}
