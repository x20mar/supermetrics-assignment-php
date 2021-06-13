<?php

class App
{
    public function runCommand(array $argv)
    {
        $config = Config::loadConfig();
        $client = ApiClient::createConnection($config);
        $client->register();
        $posts = $client->getPosts();

        $results = DataProcessor::process($posts);
        echo $results;
        echo "\n";
    }
}
