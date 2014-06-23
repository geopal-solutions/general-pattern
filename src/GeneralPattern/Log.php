<?php

namespace GeneralPattern;

/**
 * Class Log
 * @package GeneralPattern
 */
class Log
{
    const LEVEL_ERROR = 'ERR';
    const LEVEL_INFO = 'INF';
    const LEVEL_WARNING = 'WRN';
    const MSG_ALL_FILES_PROCESSED = 'All files have been processed.';
    const MSG_PROCESSING_FILE = "Processing file:\t%s\t(%s)";
    const MSG_INVALID_FILE = "Invalid file:\t%s";
    const MSG_MEMORY_CONSUMPTION = "Memory consumption:\t%s";
    const MSG_NO_VALID_FILES_FOUND = 'No valid input files have been found!';
    const MSG_NO_MATCHES_FOUND = 'Nothing was found in the logs that matches your search patterns. Exiting.';
    const MSG_WRITING_OUTPUT_TO_FILE = "Writing output file:\t%s";

    const TIME_FORMAT = 'Y-m-d H:i:s e';

    /**
     * @var Log
     */
    private static $instance = null;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var String
     */
    private $logFile;

    /**
     * Neuter constructor for singleton
     */
    private function __construct()
    {
    }

    /**
     * Neuter clone method for singleton
     */
    private function __clone()
    {
    }

    /**
     * @return Log
     */
    public static function get()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Log();
        }

        return self::$instance;
    }

    /**
     * Logs the current date and time
     */
    public function logTime()
    {
        $this->write(self::LEVEL_INFO, date(self::TIME_FORMAT));
    }

    /**
     * @param Config $config
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
        $logFilePath = $this->config->get('log_file');

        if (File::isWritable($logFilePath)) {
            $this->logFile = $logFilePath;
        }
    }

    /**
     * @param string $logLevel
     * @param string $logMessage
     */
    public function write($logLevel, $logMessage)
    {
        if ($this->config->get('logging') == true) {

            if (!empty($logMessage) && is_string($logMessage)) {
                $logEntry = implode("\t", array('[' . $logLevel . ']', $logMessage, PHP_EOL));
            } else {
                $logEntry = null;
            }

            if (!is_null($logEntry)) {

                if (!is_null($this->logFile)) {
                    file_put_contents($this->logFile, $logEntry, FILE_APPEND);
                }

                echo $logEntry;
            }

        }
    }
}
