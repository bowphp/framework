<?php
namespace Http\HttpClient;

class HttpClient
{
    /**
     * @var Resource
     */
    private $ch;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * Constructeur d'instance.
     */
    public function __construct()
    {
        $this->ch = curl_init();
        $this->parser = new Parser($this->ch);
    }

    /**
     * Make get requete
     *
     * @param string $url
     * @return Parser
     */
    public function get($url)
    {
        $this->ch = curl_init();
        $this->resetAndAssociateUrl($url);

        return $this->parser;
    }

    /**
     * make post requete
     *
     * @param string $url
     * @param array $data
     * @return Parser
     */
    public function post($url, $data)
    {
        $this->resetAndAssociateUrl($url);

        if (! curl_setopt($this->ch, CURLOPT_POST, true)) {
            $this->addFields($data);
        }

        return $this->parser;
    }

    /**
     * make put requete
     *
     * @param string $url
     * @param array $data
     * @return Parser
     */
    public function put($url, array $data = [])
    {
        $this->resetAndAssociateUrl($url);

        if (! curl_setopt($this->ch, CURLOPT_PUT, true)) {
            $this->addFields($data);
        }

        return $this->parser;
    }

    /**
     * Reset alway connection
     *
     * @param string $url
     */
    private function resetAndAssociateUrl($url)
    {
        curl_reset($this->ch);
        curl_setopt($this->ch, CURLOPT_URL, urlencode($url));
    }

    /**
     * @param array $data
     */
    private function addFields(array $data) {
        if (!empty($data)) {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }
}