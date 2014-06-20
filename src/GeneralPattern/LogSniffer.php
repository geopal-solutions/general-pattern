<?php

namespace GeneralPattern;

use GeneralPattern\Exceptions\InvalidInputException;

/**
 * Class Corgi
 * @package Corgi
 * @author gabor.zelei@geopal-solutions.com
 *
 * Corgi log analyzer main class
 */
class LogSniffer
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Result
     */
    private $result;

    public function __construct(Config $config)
    {
        $this->config = $config;

        if (is_null($this->config->get('files'))) {
            throw new InvalidInputException();
        }

        Log::get()->setConfig($this->config);

        ini_set('memory_limit', is_null($this->config->get('memory')) ? '1G' : $this->config->get('memory'));
        set_time_limit(
            is_null($this->config->get('max_execution_time')) ? 7200 : $this->config->get('mac_execution_time')
        );
    }

    /**
     * @param Config $config
     * @return LogSniffer
     * @throws \Exception|Exceptions\InvalidInputException
     */
    public static function create(Config $config)
    {
        try {
            return new LogSniffer($config);
        } catch (InvalidInputException $e) {
            throw $e;
        }
    }

    /**
     * Runs the analyzer on the target files
     *
     * @return $this
     */
    public function run()
    {
        Log::get()->write(Log::LEVEL_INFO, date('Y-m-d H:i:s e'));

        $filePaths = $this->config->get('files');

        if (is_null($filePaths) || (is_array($filePaths) && (count($filePaths) < 1))) {
            Log::get()->write(LOG::LEVEL_ERROR, Log::MSG_NO_VALID_FILES_FOUND);
        }

        $analyzer = new Analyzer($this->config);
        $analyzer->addFile($filePaths);
        $this->result = $analyzer->run();
        unset($analyzer);

        Log::get()->write(Log::LEVEL_INFO, Log::MSG_ALL_FILES_PROCESSED);
        Log::get()->write(Log::LEVEL_INFO, date('Y-m-d H:i:s e'));
        Log::get()->write(Log::LEVEL_INFO, sprintf(Log::MSG_MEMORY_CONSUMPTION, $this->getPeakMemory()));

        return $this;
    }

    /**
     * Saves output in a file or returns it as a json string
     *
     * @return int|string
     */
    public function getResult()
    {
        $outputFilePath = $this->config->get('output_file');
        $resultArray = $this->result->toArray();

        $this->result = null;

        if (count($resultArray) > 0) {
            Log::get()->write(Log::LEVEL_INFO, sprintf(Log::MSG_WRITING_OUTPUT_TO_FILE, $outputFilePath));

            $jsonResult = $this->prettyJson($resultArray);

            if (File::isWritable($outputFilePath)) {
                return file_put_contents($outputFilePath, $jsonResult);
            } else {
                return $jsonResult;
            }
        }

        Log::get()->write(Log::LEVEL_INFO, Log::MSG_NO_MATCHES_FOUND);
        return null;
    }

    /**
     * Returns peak memory usage
     *
     * @return string
     */
    private function getPeakMemory()
    {
        return number_format(memory_get_peak_usage() / 1048576, 2) . ' MB';
    }

    /**
     * Prettifies JSON string
     * Source: http://snipplr.com/view.php?codeview&id=60559
     *
     * @param array $jsonArray
     * @return string
     */
    private function prettyJson($jsonArray)
    {

        if (PHP_VERSION_ID > 50400) {
            // PHP 5.4.0+ supports prettifying output in json_encode
            return json_encode($jsonArray, JSON_PRETTY_PRINT);
        } else {
            $jsonString = json_encode($jsonArray);
            unset($jsonArray);
        }

        $result      = '';
        $pos         = 0;
        $strLen      = strlen($jsonString);
        $indentStr   = '    ';
        $newLine     = PHP_EOL;
        $prevChar    = '';
        $outOfQuotes = true;

        for ($i=0; $i<=$strLen; $i++) {

            // Grab the next character in the string.
            $char = substr($jsonString, $i, 1);

            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;

                // If this character is the end of an element,
                // output a new line and indent the next line.
            } else if(($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos --;
                for ($j=0; $j<$pos; $j++) {
                    $result .= $indentStr;
                }
            }

            // Add the character to the result string.
            $result .= $char;

            // If the last character was the beginning of an element,
            // output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos ++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            $prevChar = $char;
        }

        return $result;
    }
}
