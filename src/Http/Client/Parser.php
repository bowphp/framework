<?php
namespace Bow\Http\Client;

class Parser
{
    /**
     * @var string
     */
    private $error;

    /**
     * @var int
     */
    private $errno;

    /**
     * @var Resource
     */
    private $ch;

    /**
     * @var array
     */
    private $header;

    /**
     * @var bool
     */
    private $executed;

    /**
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
     * Retourne des données brutes
     *
     * @return mixed|null
     */
    public function raw()
    {
        if (!$this->retournTransfertToRaw()) {
            return null;
        }

        return $this->execute();
    }

    /**
     * Retourne la reponse en json
     *
     * @param  array $default
     * @return string
     */
    public function toJson(array $default = null)
    {
        if (!$this->retournTransfertToPlain()) {
            if (is_array($default)) {
                return json_encode($default);
            }
            return false;
        }

        $data = $this->execute();
        return json_encode($data);
    }

    /**
     * Retourne la reponse sous forme de tableau
     *
     * @return array|mixed
     */
    public function toArray()
    {
        if (!$this->retournTransfert()) {
            return ["error" => true, "message" => "Connat get information"];
        }

        return $this->execute();
    }

    /**
     * Retourne response
     *
     * @return bool
     */
    private function retournTransfert()
    {
        if (!curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true)) {
            $this->close();
            return false;
        }

        return true;
    }

    /**
     * Retourne response
     *
     * @return bool
     */
    private function retournTransfertToRaw()
    {
        if ($this->retournTransfert()) {
            if (!curl_setopt($this->ch, CURLOPT_BINARYTRANSFER, true)) {
                $this->close();
                return false;
            }
        }

        return true;
    }

    /**
     * Retourne response
     *
     * @return bool
     */
    private function retournTransfertToPlain()
    {
        if ($this->retournTransfert()) {
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
        ob_start();
        $data = curl_exec($this->ch);

        if ($data === false) {
            $this->close();
            ob_end_flush();
            throw new \Exception('Impossible de passer le resultat.');
        }

        $this->error = curl_error($this->ch);
        $this->errno = curl_errno($this->ch);

        $this->header = curl_getinfo($this->ch);

        $this->executed = true;

        $this->close();
        $data = ob_get_clean();

        return $data;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->header;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        if (!$this->executed) {
            $this->execute();
        }
        return $this->header['http_code'];
    }

    /**
     * @return string
     */
    public function getExecutionTime()
    {
        if (!$this->executed) {
            $this->execute();
        }
        return $this->header['total_time'];
    }

    /**
     * @return string
     */
    public function getConnexionTime()
    {
        if (!$this->executed) {
            $this->execute();
        }
        return $this->header['connect_time'];
    }

    /**
     * @return string
     */
    public function getUploadSize()
    {
        if (!$this->executed) {
            $this->execute();
        }
        return $this->header['size_upload'];
    }

    /**
     * @return string
     */
    public function getUploadSpeed()
    {
        if (!$this->executed) {
            $this->execute();
        }
        return $this->header['speed_upload'];
    }

    /**
     * @return string
     */
    public function getDownloadSize()
    {
        if (!$this->executed) {
            $this->execute();
        }
        return $this->header['size_download'];
    }

    /**
     * @return string
     */
    public function getDownloadSpeed()
    {
        if (!$this->executed) {
            $this->execute();
        }
        return $this->header['speed_download'];
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        if (!$this->executed) {
            $this->execute();
        }
        return $this->error;
    }

    /**
     * @return int
     */
    public function getErrorNumber()
    {
        if (!$this->executed) {
            $this->execute();
        }
        return $this->errno;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        if (!$this->executed) {
            $this->execute();
        }
        return $this->header['content_type'];
    }

    /**
     * @param $attach
     */
    public function addAttach($attach)
    {
        $this->attach = array_merge($this->attach, $attach);
    }

    /**
     * @return array
     */
    public function getAttach()
    {
        return $this->attach;
    }

    /**
     * @param $attachs
     */
    public function setAttach($attachs)
    {
        $this->attach = $attachs;
    }

    /**
     * Close connection
     */
    private function close()
    {
        curl_close($this->ch);
    }
}
