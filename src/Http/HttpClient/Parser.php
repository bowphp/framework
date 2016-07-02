<?php
namespace Http\HttpClient;

class Parser
{
    /**
     * @var Resource
     */
    private $ch;

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
        if (! $this->retournTransfertToRaw()) {
            return null;
        }

        return $this->execute();
    }

    /**
     * Retourne la reponse en json
     *
     * @return string
     */
    public function toJson()
    {
        if (! $this->retournTransfert()) {
            return json_encode(["error" => true, "message" => "Connat get information"]);
        }

        $data = $this->execute();
        $this->close();
        return json_encode($data);
    }

    /**
     * Retourne la reponse sous forme de tableau
     *
     * @return array|mixed
     */
    public function toArray()
    {
        if (! $this->retournTransfert()) {
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
        if (! curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true)) {
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
            if (! curl_setopt($this->ch, CURLOPT_BINARYTRANSFER, true)) {
                $this->close();
                return false;
            }
        }

        return true;
    }

    /**
     * @return mixed
     */
    private function execute()
    {
        $data = curl_exec($this->ch);
        $this->close();

        return $data;
    }

    /**
     * Close connection
     */
    private function close()
    {
        curl_close($this->ch);
    }
}