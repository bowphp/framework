<?php

declare(strict_types=1);

namespace Bow\Http;

use Bow\Auth\Authentication;
use Bow\Http\Exception\BadRequestException;
use Bow\Session\Session;
use Bow\Support\Collection;
use Bow\Support\Str;
use Bow\Validation\Validate;
use Bow\Validation\Validator;

class Request
{
    /**
     * The Request instance
     *
     * @static Request
     */
    private static ?Request $instance = null;

    /**
     * All request input
     *
     * @var array
     */
    private array $input = [];

    /**
     * Define the bags instance
     *
     * @var array
     */
    private array $bags = [];

    /**
     * Define the request id
     *
     * @var string
     */
    private string $id;

    /**
     * Define the request captured
     *
     * @var bool
     */
    private bool $capture = false;

    /**
     * Request constructor
     *
     * @return mixed
     */
    public function capture()
    {
        if ($this->capture) {
            return;
        }

        $data = [];
        $this->id = "req_" . sha1(uniqid() . time());

        if ($this->getHeader('content-type') == 'application/json') {
            try {
                $data = json_decode(file_get_contents("php://input"), true, 1024, JSON_THROW_ON_ERROR);
            } catch (\Throwable $e) {
                throw new BadRequestException(
                    "The request json payload is invalid: " . $e->getMessage(),
                );
            }
            $this->input = array_merge((array) $data, $_GET);
        } else {
            $data = $_POST ?? [];
            if ($this->isPut()) {
                parse_str(file_get_contents("php://input"), $data);
            }
            $this->input = array_merge((array) $data, $_GET);
        }

        foreach ($this->input as $key => $value) {
            if (is_string($value) && strlen($value) == 0) {
                $value = null;
            }

            $this->input[$key] = $value;
        }

        $this->capture = true;
    }

    /**
     * Set the request id
     *
     * @param string|int $id
     * @return void
     */
    public function setId(string|int $id): void
    {
        $this->id = $id;
    }

    /**
     * Get the request ID
     *
     * @return string|int
     */
    public function getId(): string|int
    {
        return $this->id;
    }

    /**
     * Alias of getId
     *
     * @return string|int
     */
    public function id(): string|int
    {
        return $this->id;
    }

    /**
     * Singletons loader
     *
     * @return Request
     */
    public static function getInstance(): Request
    {
        if (static::$instance === null) {
            static::$instance = new Request();
        }

        return static::$instance;
    }

    /**
     * Check if key is exists
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->input[$key]);
    }

    /**
     * Get all input value
     *
     * @return array
     */
    public function all(): array
    {
        return $this->input;
    }

    /**
     * Get uri send by client.
     *
     * @return string
     */
    public function path(): string
    {
        $position = strpos($_SERVER['REQUEST_URI'], '?');

        if ($position) {
            $uri = substr($_SERVER['REQUEST_URI'], 0, $position);
        } else {
            $uri = $_SERVER['REQUEST_URI'];
        }

        return $uri;
    }

    /**
     * Get the host name of the server.
     *
     * @return string
     */
    public function hostname(): string
    {
        return $_SERVER['HTTP_HOST'];
    }

    /**
     * Get url sent by client.
     *
     * @return string
     */
    public function url(): string
    {
        return $this->origin() . $this->path();
    }

    /**
     * Origin the name of the server + the scheme
     *
     * @return string
     */
    public function origin(): string
    {
        return $this->scheme() . '://' . $this->hostname();
    }

    /**
     * Get request scheme
     *
     * @return string
     */
    private function scheme(): string
    {
        return strtolower($_SERVER['REQUEST_SCHEME'] ?? 'http');
    }

    /**
     * Get path sent by client.
     *
     * @return string
     */
    public function time(): string
    {
        return $_SESSION['REQUEST_TIME'];
    }

    /**
     * Returns the method of the request.
     *
     * @return string
     */
    public function method(): ?string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? null;

        if ($method !== 'POST') {
            return $method;
        }

        if (array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if (in_array($_SERVER['HTTP_X_HTTP_METHOD'], ['PUT', 'DELETE'])) {
                $method = $_SERVER['HTTP_X_HTTP_METHOD'];
            }
        }

        return $method;
    }

    /**
     * Check if the query is POST
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->method() == 'POST';
    }

    /**
     * Check if the query is of type GET
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->method() == 'GET';
    }

    /**
     * Check if the query is of type PUT
     *
     * @return bool
     */
    public function isPut(): bool
    {
        return $this->method() == 'PUT' || $this->get('_method') == 'PUT';
    }

    /**
     * Check if the query is DELETE
     *
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->method() == 'DELETE' || $this->get('_method') == 'DELETE';
    }

    /**
     * Load the factory for FILES
     *
     * @param string $key
     * @return UploadFile|Collection|null
     */
    public function file(string $key): UploadFile|Collection|null
    {
        if (!isset($_FILES[$key])) {
            return null;
        }

        if (!is_array($_FILES[$key]['name'])) {
            return new UploadFile($_FILES[$key]);
        }

        $files = $_FILES[$key];
        $collect = [];

        foreach ($files['name'] as $key => $name) {
            $collect[] = new UploadFile([
                'name' => $name,
                'type' => $files['type'][$key],
                'size' => $files['size'][$key],
                'error' => $files['error'][$key],
                'tmp_name' => $files['tmp_name'][$key],
            ]);
        }

        return new Collection($collect);
    }

    /**
     * Check if file exists
     *
     * @param mixed $file
     * @return bool
     */
    public static function hasFile($file): bool
    {
        return isset($_FILES[$file]);
    }

    /**
     * Get previous request data
     *
     * @param  string $key
     * @param  mixed $fullback
     * @return mixed
     */
    public function old(string $key, mixed $fullback): mixed
    {
        $old = Session::getInstance()->get('__bow.old', []);

        return $old[$key] ?? $fullback;
    }

    /**
     * Check if we are in the case of an AJAX request.
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            return false;
        }

        $xhr_obj = Str::lower($_SERVER['HTTP_X_REQUESTED_WITH']);

        if ($xhr_obj == 'xmlhttprequest' || $xhr_obj == 'activexobject') {
            return true;
        }

        $content_type = $this->getHeader("content-type");

        if ($content_type && str_contains($content_type, "application/json")) {
            return true;
        }

        return false;
    }

    /**
     * Check if a url matches with the pattern
     *
     * @param  string $match
     * @return bool
     */
    public function is($match): bool
    {
        return (bool) preg_match('@' . addcslashes($match, "/*{()}[]$^") . '@', $this->path());
    }

    /**
     * Check if a url matches with the pattern
     *
     * @param  string $match
     * @return bool
     */
    public function isReferer($match): bool
    {
        return (bool) preg_match('@' . addcslashes($match, "/*{()}[]$^") . '@', $this->referer());
    }

    /**
     * Get client address
     *
     * @return ?string
     */
    public function ip(): ?string
    {
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
     * Get client port
     *
     * @return string
     */
    public function port(): ?string
    {
        return $_SERVER['REMOTE_PORT'] ?? null;
    }

    /**
     * Get the source of the current request.
     *
     * @return string
     */
    public function referer(): string
    {
        return $_SERVER['HTTP_REFERER'] ?? '/';
    }

    /**
     * Get the request locale.
     *
     * The local is the original language of the client
     * e.g fr => locale = fr_FR
     * e.g en => locale [ en_US, en_EN]
     *
     * @return string|null
     */
    public function locale(): ?string
    {
        $accept_language = $this->getHeader('accept_language');

        $tmp = explode(';', $accept_language)[0];

        preg_match('/^([a-z]+(?:-|_)?[a-z]+)/i', $tmp, $match);

        return end($match);
    }

    /**
     * Get request lang.
     *
     * @return string
     */
    public function lang(): ?string
    {
        $accept_language = $this->getHeader('accept_language');

        $language = explode(',', explode(';', $accept_language)[0])[0];

        preg_match('/([a-z]+)/', $language, $match);

        return end($match);
    }

    /**
     * Get request protocol
     *
     * @return mixed
     */
    public function protocol(): string
    {
        return $this->scheme();
    }

    /**
     * Check the protocol of the request
     *
     * @param string $protocol
     * @return mixed
     */
    public function isProtocol($protocol): bool
    {
        return $this->scheme() == $protocol;
    }

    /**
     * Check if the secure protocol
     *
     * @return mixed
     */
    public function isSecure(): bool
    {
        return $this->isProtocol('https');
    }

    /**
     * Get Request header
     *
     * @param  string $key
     * @return array
     */
    public function getHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (preg_match('/^http_/i', $key)) {
                $key = str_replace("http_", "", strtolower($key));
                $headers[$key] = $value;
            }
        }

        return $headers;
    }

    /**
     * Get Request header
     *
     * @param  string $key
     * @return ?string
     */
    public function getHeader($key): ?string
    {
        $key = str_replace('-', '_', strtoupper($key));

        if ($this->hasHeader($key)) {
            return $_SERVER[$key];
        }

        if ($this->hasHeader('HTTP_' . $key)) {
            return $_SERVER['HTTP_' . $key];
        }

        return null;
    }

    /**
     * Check if a header exists.
     *
     * @param  string $key
     * @return bool
     */
    public function hasHeader($key): bool
    {
        return isset($_SERVER[strtoupper($key)]);
    }

    /**
     * Get the client user agent
     *
     * @return ?string
     */
    public function userAgent(): ?string
    {
        return $this->getHeader('USER_AGENT');
    }

    /**
     * Get session information
     *
     * @return Session
     */
    public function session(): Session
    {
        return session();
    }

    /**
     * Get auth user information
     *
     * @param string|null $guard
     * @return Authentication|null
     */
    public function user(?string $guard = null): ?Authentication
    {
        return auth($guard)->user();
    }

    /**
     * Get cookie
     *
     * @param string $property
     * @return mixed
     */
    public function cookie($property = null)
    {
        return cookie($property);
    }

    /**
     * Retrieve a value or a collection of values.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $value = $this->input[$key] ?? $default;

        if (is_callable($value)) {
            return $value();
        }

        return $value;
    }

    /**
     * Retrieves the values contained in the exception table
     *
     * @param array $exceptions
     * @return array
     */
    public function only($exceptions)
    {
        $data = [];

        if (!is_array($exceptions)) {
            $exceptions = func_get_args();
        }

        foreach ($exceptions as $exception) {
            if (isset($this->input[$exception])) {
                $data[$exception] = $this->input[$exception];
            }
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function ignore($ignores)
    {
        $data = $this->input;

        if (!is_array($ignores)) {
            $ignores = func_get_args();
        }

        foreach ($ignores as $ignore) {
            if (isset($data[$ignore])) {
                unset($data[$ignore]);
            }
        }

        return $data;
    }

    /**
     * Validate incoming data
     *
     * @param  array $rule
     * @return Validate
     */
    public function validate(array $rule)
    {
        return Validator::make($this->input, $rule);
    }

    /**
     * Set the shared value in request bags
     *
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function setBag($name, $value)
    {
        $this->bags[$name] = $value;
    }

    /**
     * Get the shared value in request bags
     *
     * @return mixed
     */
    public function getBag(string $name)
    {
        return $this->bags[$name] ?? null;
    }

    /**
     * Set the shared value in request bags
     *
     * @param array $bags
     * @return mixed
     */
    public function setBags(array $bags)
    {
        $this->bags = $bags;
    }

    /**
     * Get the shared value in request bags
     *
     * @return array
     */
    public function getBags()
    {
        return $this->bags;
    }

    /**
     * __call
     *
     * @param $property
     * @return mixed
     */
    public function __get($property)
    {
        return $this->get($property);
    }
}
