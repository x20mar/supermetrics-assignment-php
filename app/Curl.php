<?php

class HttpMethod
{
    const Get = 'GET';
    const Post = 'POST';
    const Put = 'PUT';
    const Delete = 'DELETE';
}

class Curl
{
    /**
     * Calls API using curl
     *
     * @param string $method HTTP Method
     * @param string $url URL of endpoint (not including hostname)
     * @param Config $config Config data
     * @param array|null $data array of params to be used
     * @return mixed JSON Decoded results
     */
    public static function call(string $method, string $url, Config $config, $data = null)
    {
        $curl = curl_init();

        switch ($method) {
            case HttpMethod::Post:
                curl_setopt($curl, CURLOPT_POST, 1);
                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                }
                break;
            case HttpMethod::Put:
                curl_setopt($curl, CURLOPT_PUT, 1);
                break;
            default:
                if ($data) {
                    $url = sprintf("%s?%s", $url, http_build_query($data));
                }
        }

        $url = $config->clientHostname . $url;
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);

        curl_close($curl);

        return json_decode($result);
    }
}
