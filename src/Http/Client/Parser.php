<?php

namespace Bow\Http\Client;

class Parser
{
    /**
     * The error message
     *
     * @var string
     */
    private $error;

    /**
     * The error number
     *
     * @var int
     */
    private $errno;

    /**
     * Curl instance
     *
     * @var Resource
     */
    private $ch;

    /**
     * The header
     *
     * @var array
     */
    private $header;

    /**
     * Flag
     *
     * @var bool
     */
    private $executed;

    /**
     * The attachment collection
     *
     * @var array
     */
    private $attach = [];

    /**
     * Parser constructor.
     *
     * @param $ch
     */
    public function __construct(& $ch)
    {
        $this->ch = $ch;
    }

    /**
     * Get raw content
     *
     * @return mixed|null
     * @throws
     */
    public function raw()
    {
        if (!$this->returnTransfertToRaw()) {
            return null;
        }

        return $this->execute();
    }

    /**
     * Get response content
     *
     * @return mixed|null
     * @throws
     */
    public function getContent()
    {
        if (!$this->returnTransfertToPlain()) {
            return null;
        }

        return $this->execute();
    }

    /**
     * Get response content as json
     *
     * @param  array $default
     * @return string
     * @throws
     */
    public function toJson(array $default = null)
    {
        if (!$this->returnTransfertToPlain()) {
            if (is_array($default)) {
                return json_encode($default);
            }

            return false;
        }

        $data = $this->raw();

        return json_encode($data);
    }

    /**
     * Get response content as array
     *
     * @return array|mixed
     * @throws
     */
    public function toArray()
    {
        if (!$this->returnTransfert()) {
            $this->close();

            return ["error" => true, "message" => "Connat get information"];
        }

        return $this->execute();
    }

    /**
     * Set Curl CURLOPT_RETURNTRANSFER option
     *
     * @return bool
     */
    private function returnTransfert()
    {
        if (!curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true)) {
            $this->close();

            return false;
        }

        return true;
    }

    /**
     * Set Curl CURLOPT_BINARYTRANSFER option
     *
     * @return bool
     */
    private function returnTransfertToRaw()
    {
        if ($this->returnTransfert()) {
            if (!curl_setopt($this->ch, CURLOPT_BINARYTRANSFER, true)) {
                $this->close();

                return false;
            }
        }

        return true;
    }

    /**
     * Set Curl CURLOPT_TRANSFERTEXT option
     *
     * @return bool
     */
    private function returnTransfertToPlain()
    {
        if ($this->returnTransfert()) {
            if (!curl_setopt($this->ch, CURLOPT_TRANSFERTEXT, true)) {
                $this->close();

                return false;
            }
        }

        return true;
    }

    /**
     * Execute request
     *
     * @return mixed
     * @throws \Exception
     */
    private function execute()
    {
        $data = curl_exec($this->ch);

        if ($data === false) {
            $this->close();

            throw new \Exception('Impossible to pass the result.');
        }

        $this->error = curl_error($this->ch);

        $this->errno = curl_errno($this->ch);

        $this->header = curl_getinfo($this->ch);

        $this->executed = true;

        $this->close();

        return $data;
    }

    /**
     * Get the response headers
     *
     * @return array
     * @throws
     */
    public function getHeaders()
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->header;
    }

    /**
     * Get the response code
     *
     * @return string
     * @throws
     */
    public function getCode()
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->header['http_code'];
    }

    /**
     * Get the response executing time
     *
     * @return string
     * @throws
     */
    public function getExecutionTime()
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->header['total_time'];
    }

    /**
     * Get the request connexion time
     *
     * @return string
     * @throws
     */
    public function getConnexionTime()
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->header['connect_time'];
    }

    /**
     * Get the response upload size
     *
     * @return string
     * @throws
     */
    public function getUploadSize()
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->header['size_upload'];
    }

    /**
     * Get the request upload speed
     *
     * @return string
     * @throws
     */
    public function getUploadSpeed()
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->header['speed_upload'];
    }

    /**
     * Get the download size
     *
     * @return string
     * @throws
     */
    public function getDownloadSize()
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->header['size_download'];
    }

    /**
     * Get the downlad speed
     *
     * @return string
     * @throws
     */
    public function getDownloadSpeed()
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->header['speed_download'];
    }

    /**
     * Get error message
     *
     * @return string
     * @throws
     */
    public function getErrorMessage()
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->error;
    }

    /**
     * Get error code
     *
     * @return int
     * @throws
     */
    public function getErrorNumber()
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->errno;
    }

    /**
     * Get the response content type
     *
     * @return string
     * @throws
     */
    public function getContentType()
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->header['content_type'];
    }

    /**
     * Add attach file
     *
     * @param string $attach
     *
     * @return void
     */
    public function addAttach($attach)
    {
        $this->attach = array_merge($this->attach, $attach);
    }

    /**
     * Get attach files
     *
     * @return array
     */
    public function getAttach()
    {
        return $this->attach;
    }

    /**
     * Set attach files
     *
     * @param array $attachs
     *
     * @return void
     */
    public function setAttach(array $attachs)
    {
        $this->attach = $attachs;
    }

    /**
     * Close connection
     *
     * @return void
     */
    private function close()
    {
        curl_close($this->ch);
    }
}
