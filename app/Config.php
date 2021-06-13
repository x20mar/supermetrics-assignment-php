<?php

const CONFIG_FILE = 'config/config.json';

class Config
{
    public $clientHostname;
    public $clientId;
    public $email;
    public $name;

    /**
     * Reads and loads config into Class
     *
     * @return Config
     */
    public static function loadConfig()
    {
        try {
            $configData =
                file_get_contents(CONFIG_FILE);
            $data = json_decode($configData, true);
            $config = new Config();
            foreach ($data as $key => $value) {
                if (property_exists(__CLASS__, $key)) {
                    $config->$key = $value;
                }
            }
            return $config;
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
        }
        return null;
    }
}
