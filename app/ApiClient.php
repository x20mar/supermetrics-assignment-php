<?php

const PostPaginationHardLimit = 15;

class ApiClient
{
    private $config;
    private $authToken;

    /**
     * Get Config
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set Config
     *
     * @param Config $config
     * @return void
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Get Auth Token
     *
     * @return string
     */
    public function getAuthToken()
    {
        return $this->authToken;
    }

    /**
     * Set Auth Token
     *
     * @param string $authToken
     * @return void
     */
    public function setAuthToken(string $authToken)
    {
        $this->authToken = $authToken;
    }

    /**
     * Requests and stores API Token
     *
     * @return void
     */
    public function register()
    {
        try {
            $data = array('client_id' => $this->getConfig()->clientId, 'email' => $this->getConfig()->email, 'name' => $this->getConfig()->name);
            $request = Curl::call(HttpMethod::Post, '/register', $this->getConfig(), $data);

            if ($request->data && $request->data->sl_token) {
                $this->setAuthToken($request->data->sl_token);
            } else {
                throw new Error('Unable to extract Auth Token');
            }
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
        }
    }

    /**
     * Loops through posts API call pagination to get all posts. A hard limit of 15 pages is set
     *
     * @return array
     */
    public function getPosts()
    {
        $posts = [];

        try {

            for ($page = 1; $page <= PostPaginationHardLimit; $page++) {

                $url = '/posts?sl_token=' . $this->getAuthToken() . '&page=' . $page;
                $request = Curl::call(HttpMethod::Get, $url, $this->getConfig());
                if ($request->data && $request->data->page) {
                    $actualPage = $request->data->page;
                    if ($actualPage !== $page) {
                        // no new page fetched
                        break;
                    }

                    if ($request->data->posts) {
                        $posts = array_merge($posts, $request->data->posts);
                    }
                } else {
                    throw new Error('Unable to determine page number');
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
        }

        return $posts;
    }

    /**
     * Creates an API Client
     *
     * @param Config $config
     * @return ApiClient
     */
    public static function createConnection(Config $config)
    {
        $client = new ApiClient();
        $client->setConfig($config);
        return $client;
    }
}
