<?php
namespace Allegro\REST;

class Resource
{

    /**
     * Resource constructor.
     * @param string $id
     * @param Resource $parent
     */
    public function __construct($id, Resource $parent)
    {
        $this->id = $id;
        $this->parent = $parent;
    }

    /**
     * @return mixed
     */
    public function getAccessToken()
    {
        return $this->parent->getAccessToken();
    }

    /**
     * @return mixed
     */
    public function getApiKey()
    {
        return $this->parent->getApiKey();
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->parent->getUri() . '/' . $this->id;
    }

    /**
     * @return Commands
     */
    public function commands()
    {
        return new Commands($this);
    }

    /**
     * @param null|array $data
     * @param array $additionalHeaders
     * @return bool|string
     */
    public function get($data = null, $additionalHeaders = [])
    {
        $uri = $this->getUri();

        if ($data !== null) {
            $uri .= '?';
            $uri .= http_build_query($data);
        }

        return $this->sendApiRequest($uri, 'GET', [], $additionalHeaders);
    }

    /**
     * @param array $data
     * @param array $additionalHeaders
     * @return bool|string
     */
    public function put($data, $additionalHeaders = [])
    {
        return $this->sendApiRequest($this->getUri(), 'PUT', $data, $additionalHeaders);
    }

    /**
     * @param array $data
     * @param array $additionalHeaders
     * @return bool|string
     */
    public function post($data, $additionalHeaders = [])
    {
        return $this->sendApiRequest($this->getUri(), 'POST', $data, $additionalHeaders);
    }

    /**
     * @param null|array $data
     * @param array $additionalHeaders
     * @return bool|string
     */
    public function delete($data = null, $additionalHeaders = [])
    {
        $uri = $this->getUri();

        if ($data !== null) {
            $uri .= '?';
            $uri .= http_build_query($data);
        }

        return $this->sendApiRequest($uri, 'DELETE', [], $additionalHeaders);
    }

    public function __get($name)
    {
        return new Resource($name, $this);
    }

    public function __call($name, $args)
    {
        $id = array_shift($args);
        $collection = new Resource($name, $this);
        return new Resource($id, $collection);
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $data
     * @param array $additionalHeaders
     * @return bool|string
     */
    protected function sendApiRequest($url, $method, $data = array(), $additionalHeaders = [])
    {
        $token = $this->getAccessToken();
        $key = $this->getApiKey();

        $defaultHeaders = array(
            "Authorization" => "Bearer $token",
            "Api-Key" => "$key",
            "Content-Type" => "application/vnd.allegro.public.v1+json",
            "Accept" => "application/vnd.allegro.public.v1+json"
        );

        $mergedHeaders = array_merge($defaultHeaders, $additionalHeaders);

        function connectKeyAndValue(&$arrayValue, $arrayKey, $conector)
        {
            $arrayValue = $arrayKey . $conector . $arrayValue;
        }

        $headers = array_walk($mergedHeaders, 'connectKeyAndValue', ': ');

        $data = json_encode($data);

        return $this->sendHttpRequest($url, $method, array_values($headers), $data);
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $headers
     * @param string $data
     * @return bool|string
     */
    protected function sendHttpRequest($url, $method, $headers = array(), $data = '')
    {
        $options = array(
            'http' => array(
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $data,
                'ignore_errors' => true
            )
        );

        $context = stream_context_create($options);

        return file_get_contents($url, false, $context);
    }

    /**
     * @var string
     */
    private $id;

    /**
     * @var Resource
     */
    private $parent;
}
