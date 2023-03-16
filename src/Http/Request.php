<?php

namespace Bow\Http;

use Bow\Auth\Authentication;
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
     * @static self
     */
    private static $instance;

    /**
     * All request input
     *
     * @var array
     */
    private $input = [];

    /**
     * Define the bag instance
     *
     * @var array
     */
    private $bag = [];

    /**
     * Request constructor
     *
     * @return mixed
     */
    private function __construct()
    {
        if ($this->getHeader('content-type') == 'application/json') {
            $data = json_decode(file_get_contents("php://input"), true);
            $this->input = array_merge((array) $data, $_GET);
        } else {
            $data = [];

            if ($this->isPut()) {
                parse_str(file_get_contents("php://input"), $data);
            } elseif ($this->isPost()) {
                $data = $_POST;
            }

            $this->input = array_merge($data, $_GET);
        }

        foreach ($this->input as $key => $value) {
            if (!is_array($value) && strlen($value) == 0) {
                $value = null;
            }

            $this->input[$key] = $value;
        }
    }

    /**
     * Singletons loader
     *
     * @return Request
     */
    public static function getInstance()
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
     * @return mixed
     */
    public function has($key)
    {
        return isset($this->input[$key]);
    }

    /**
     * Get all input value
     *
     * @return array
     */
    public function all()
    {
        return $this->input;
    }

    /**
     * Get uri send by client.
     *
     * @return string
     */
    public function path()
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
    public function hostname()
    {
        return $_SERVER['HTTP_HOST'];
    }

    /**
     * Get url sent by client.
     *
     * @return string
     */
    public function url()
    {
        return $this->origin() . $this->path();
    }

    /**
     * Origin the name of the server + the scheme
     *
     * @return string
     */
    public function origin()
    {
        return $this->scheme() . '://' . $this->hostname();
    }

    /**
     * Get request scheme
     *
     * @return string
     */
    private function scheme()
    {
        return strtolower($_SERVER['REQUEST_SCHEME'] ?? 'http');
    }

    /**
     * Get path sent by client.
     *
     * @return string
     */
    public function time()
    {
        return $_SESSION['REQUEST_TIME'];
    }

    /**
     * Returns the method of the request.
     *
     * @return string
     */
    public function method()
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
    public function isPost()
    {
        return $this->method() == 'POST';
    }

    /**
     * Check if the query is of type GET
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->method() == 'GET';
    }

    /**
     * Check if the query is of type PUT
     *
     * @return bool
     */
    public function isPut()
    {
        return $this->method() == 'PUT' || $this->get('_method') == 'PUT';
    }

    /**
     * Check if the query is DELETE
     *
     * @return bool
     */
    public function isDelete()
    {
        return $this->method() == 'DELETE' || $this->get('_method') == 'DELETE';
    }

    /**
     * Load the factory for FILES
     *
     * @param string $key
     * @return UploadFile|Collection
     */
    public function file($key)
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
            $file = [
                'name' => $name,
                'type' => $files['type'][$key],
                'size' => $files['size'][$key],
                'error' => $files['error'][$key],
                'tmp_name' => $files['tmp_name'][$key],
            ];
            $collect[] = new UploadFile($file);
        }

        return new Collection($collect);
    }

    /**
     * Check if file exists
     *
     * @param mixed $file
     * @return bool
     */
    public static function hasFile($file)
    {
        return isset($_FILES[$file]);
    }

    /**
     * Get previous request data
     *
     * @param  mixed $key
     * @return mixed
     */
    public function old($key)
    {
        $old = Session::getInstance()->get('__bow.old', []);

        return $old[$key] ?? null;
    }

    /**
     * Check if we are in the case of an AJAX request.
     *
     * @return boolean
     */
    public function isAjax()
    {
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            return false;
        }

        $xhr_obj = Str::lower($_SERVER['HTTP_X_REQUESTED_WITH']);

        if ($xhr_obj == 'xmlhttprequest' || $xhr_obj == 'activexobject') {
            return true;
        }

        return false;
    }

    /**
     * Check if a url matches with the pattern
     *
     * @param  string $match Un regex
     * @return int
     */
    public function is($match)
    {
        return preg_match('@' . $match . '@', $this->path());
    }

    /**
     * Get client address
     *
     * @return string
     */
    public function ip()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Get client port
     *
     * @return string
     */
    public function port()
    {
        return $_SERVER['REMOTE_PORT'];
    }

    /**
     * Get the source of the current request.
     *
     * @return string
     */
    public function referer()
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
    public function locale()
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
    public function lang()
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
    public function protocol()
    {
        return $this->scheme();
    }

    /**
     * Check the protocol of the request
     *
     * @param string $protocol
     * @return mixed
     */
    public function isProtocol($protocol)
    {
        return $this->scheme() == $protocol;
    }

    /**
     * Check if the secure protocol
     *
     * @return mixed
     */
    public function isSecure()
    {
        return $this->isProtocol('https');
    }

    /**
     * Get Request header
     *
     * @param  string $key
     * @return bool|string
     */
    public function getHeader($key)
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
    public function hasHeader($key)
    {
        return isset($_SERVER[strtoupper($key)]);
    }

    /**
     * Get session information
     *
     * @return Session
     */
    public function session()
    {
        return session();
    }

    /**
     * Get auth user information
     *
     * @param string|null $guard
     * @return Authentication|null
     */
    public function user($guard = null)
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

        if (! is_array($exceptions)) {
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

        if (! is_array($ignores)) {
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
     * Set the value in request bag
     *
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function setBag($name, $value)
    {
        $this->bag[$name] = $value;
    }

    /**
     * Get the value in request bag
     *
     * @return mixed
     */
    public function getBag($name)
    {
        return $this->bag[$name] ?? null;
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
