<?php

namespace GeneralPattern;

class Config
{
    /**
     * @var array
     */
    private $config;

    /**
     * @param string $configFilePath
     */
    public function __construct($configFilePath)
    {
        if (File::isReadable($configFilePath)) {
            $this->config = json_decode(file_get_contents($configFilePath), true);
        }

        if (is_null($this->config) || !is_array($this->config)) {
            $this->config = array();
        }
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        if (!is_null($key) && is_string($key) && isset($this->config[$key])) {
            return $this->config[$key];
        } else {
            return null;
        }
    }
}
