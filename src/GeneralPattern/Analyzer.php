<?php

namespace GeneralPattern;

use GeneralPattern\Exceptions\InvalidFileException;

/**
 * Class Analyzer
 * @package GeneralPattern
 * @author gabor.zelei@geopal-solutions.com
 *
 * Runs analysis on files
 */
class Analyzer
{
    const FEATURES_NAMESPACE = 'Features';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var string
     */
    private $eol;

    /**
     * @var array
     */
    private $files;

    /**
     * @var int
     */
    private $filesProcessed = 0;

    /**
     * @var int
     */
    private $filesToProcess = 0;

    /**
     * @var string
     */
    private $nameSpace;

    /**
     * @var Result
     */
    private $result;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        error_reporting(E_ERROR | E_WARNING | E_PARSE);
        $this->config = $config;

        $this->eol = $this->config->get('input_log_eol_character');
        $this->eol = is_null($this->eol) ? PHP_EOL : $this->eol;

        $reflection = new \ReflectionClass($this);
        $this->nameSpace = implode('\\', array($reflection->getNamespaceName(), self::FEATURES_NAMESPACE));
    }

    /**
     * Adds a file to the analyzer
     *
     * @param File|string|array $file
     * @throws Exceptions\InvalidFileException
     */
    public function addFile($file)
    {
        if (is_array($file)) {

            foreach ($file as $filePath) {
                if (is_string($filePath) || ($file instanceof File)) {
                    $this->addFile($filePath);
                }
            }

        } elseif (is_string($file)) {

            if (File::isDirectory($file)) {
                File::getFilesFromDirectory($file, $this->config, $this->files);
            } else {
                $this->files[] = new File($file);
            }

        } elseif ($file instanceof File) {
            $this->files[] = $file;
        } else {
            throw new InvalidFileException($file);
        }
    }

    /**
     * Runs the analysis and returns result
     *
     * @return Result
     */
    public function run()
    {
        $this->filesToProcess = count($this->files);
        $this->result = new Result($this->config);

        foreach ($this->files as $file) {
            /**
             * @var File $file
             */
            $this->analyzeFile($file);
        }

        return $this->result;
    }

    /**
     * Runs analysis on a file
     *
     * @param File $file
     */
    private function analyzeFile(File $file)
    {
        Log::get()->write(
            Log::LEVEL_INFO,
            sprintf(Log::MSG_PROCESSING_FILE, $file->getPath(), $this->getProgressPercentage() . ' %')
        );

        try {
            foreach (explode($this->eol, $file->getContents()) as $line) {

                if (!is_null($line)) {
                    $this->analyzeLine($line, $file);
                }

            }

            $this->clearFileFromMemory($file);
            $this->filesProcessed++;
        } catch (InvalidFileException $e) {
            Log::get()->write(Log::LEVEL_WARNING, sprintf(Log::MSG_INVALID_FILE, $e->getMessage()));
        }

    }

    /**
     * Runs analysis on a line in a file
     *
     * @param string $line
     * @param File $file
     */
    private function analyzeLine($line, File $file)
    {
        $featureMap = (array)$this->config->get('feature_map');

        foreach ((array)$this->config->get('metrics') as $metric) {
            $className = $featureMap[$metric['type']];
            $classNameWithNamespace = $this->nameSpace . '\\' . $className;

            if (!empty($className) && class_exists($classNameWithNamespace)) {
                $feature = new $classNameWithNamespace($this->config, $this->result);
                call_user_func_array(array($feature, 'processLine'), array($line, $metric, $file));
            }
        }
    }

    /**
     * Removes a file from memory
     *
     * @param File $file
     */
    private function clearFileFromMemory(File $file)
    {
        unset($this->files[md5($file->getPath())]);
    }

    /**
     * @return int|string
     */
    private function getProgressPercentage()
    {
        $percent = intval(($this->filesProcessed / $this->filesToProcess) * 100);

        if ($percent < 10) {
            $percent = '0' . $percent;
        }

        return $percent;
    }
}
