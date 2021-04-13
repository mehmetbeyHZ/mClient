<?php


namespace MClient;


use InvalidArgumentException;
use JsonException;
use RuntimeException;

class HttpInterface
{
    protected Request $_parent;
    protected array $_cookies = [];
    protected ?string $curl_error = null;

    /**
     * @var string|false|null
     */
    protected ?string $requestResponse = null;

    /**
     * Response Headers
     * @var array
     */
    protected array $requestResponseHeaders = [];
    /**
     * HttpInterface constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->_parent = $request;
        if (!$this->_parent->hasUri())
        {
            throw new InvalidArgumentException('Please set uri');
        }
        $curl = curl_init();
        $options = [
            CURLOPT_URL => $this->_parent->getRequestUri(),
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_FOLLOWLOCATION => TRUE,
            CURLOPT_HEADER         => TRUE,
        ];

        if ($this->_parent->hasHeader()):
            $options[CURLOPT_HTTPHEADER] = $this->_parent->getRequestHeaders();
        endif;


        if ($this->_parent->hasPosts()) :
            $options[CURLOPT_POST] = TRUE;
            $options[CURLOPT_POSTFIELDS] = $this->_parent->getRequestPosts();
        endif;

        if ($this->_parent->hasExtraCurlOptions()):
            foreach ($this->_parent->_curl as $key => $value){
                $options[$key] = $value;
            }
        endif;
        curl_setopt_array($curl,$options);
        $resp = curl_exec($curl);
        if (curl_errno($curl)) {
            $this->curl_error = curl_error($curl);
        }
        $header_len = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($resp, 0, $header_len);
        $header = $this->getHeadersFromResponse($header);
        $resp = (substr($resp, $header_len));
        curl_close($curl);

        $this->requestResponse = $resp;
        $this->requestResponseHeaders = $header;
    }


    /**
     * @return array
     */
    public function getResponseHeaders() : array
    {
        return $this->requestResponseHeaders;
    }

    /**
     * @return bool
     */
    public function hasCurlError() : bool
    {
        return $this->curl_error !== null;
    }

    /**
     * @return string
     */
    public function getCurlError() : string
    {
        return $this->curl_error;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function getHeaderLine($key)
    {
        if (is_array($this->requestResponseHeaders) && isset($this->requestResponseHeaders[$key])) {
            return $this->requestResponseHeaders[$key];
        }
        return null;
    }

    /**
     * @return string
     */
    public function getResponse() : string
    {
        return $this->requestResponse;
    }


    /**
     * @param bool $assoc
     * @return mixed
     */
    public function getDecodedResponse($assoc = true)
    {
        if (!$this->requestResponse)
        {
            throw new RuntimeException('No Response From Server');
        }
        return json_decode($this->requestResponse, $assoc, 512);
    }

    /**
     * @param $response
     * @return array
     */
    protected function getHeadersFromResponse($response) : array
    {
        $headers = [];

        $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));

        foreach (explode("\r\n", $header_text) as $i => $line) {
            if ($i === 0) {
                $headers['http_code'] = $line;
            } else {
                [$key, $value] = explode(': ', $line);

                $headers[$key] = $value;

                if (strtolower($key) === 'set-cookie')
                {
                    $this->_cookies[] = $value;
                }

            }
        }

        return $headers;
    }



    /**
     * @param string $key
     * @return array|mixed|null
     */
    public function getCookies($key = '')
    {
        return (new HttpCookies($this->_cookies))->getCookie($key);
    }

}