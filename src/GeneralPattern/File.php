<?php

namespace GeneralPattern;

use GeneralPattern\Exceptions\InvalidFileException;

/**
 * Class File
 * @package Corgi
 * @author gabor.zelei@geopal-solutions.com
 *
 * Corgi file handler class
 */
class File
{
    /**
     * @var string
     */
    private $path;

    /**
     * @param string|null $filePath
     */
    public function __construct($filePath = null)
    {
        $this->path = $filePath;
    }

    /**
     * Collect garbage once we are done
     */
    public function __destruct()
    {
        gc_collect_cycles();
    }

    /**
     * @return string
     */
    public function getContents()
    {
        try {
            return $this->readContents();
        } catch (InvalidFileException $e) {
            Log::get()->write(Log::LEVEL_WARNING, sprintf(Log::MSG_INVALID_FILE, $this->getPath()));
            return null;
        }
    }

    /**
     * @param string $filePath
     * @param Config $config
     * @param array|null $filesArray
     * @return array
     */
    public static function getFilesFromDirectory($filePath, Config $config, &$filesArray = null)
    {
        $filesArray = is_array($filesArray) ? $filesArray : array();
        $logFileExtension = $config->get('file_extension');
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($filePath));

        foreach ($iterator as $fileInfo) {
            /**
             * @var \RecursiveDirectoryIterator $fileInfo
             */
            if (is_null($logFileExtension) || ($fileInfo->getExtension() == $logFileExtension)) {
                $childPath = $fileInfo->getPathname();

                if (is_file($childPath) && self::isReadable($childPath)) {
                    $filesArray[md5($childPath)] = new File($childPath);
                }
            }
        }

        return $filesArray;
    }

    /**
     * @return null|string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string|null $filePath
     * @return bool
     */
    public static function isDirectory($filePath = null)
    {
        return (self::isReadable($filePath) && is_dir($filePath));
    }

    /**
     * @param string|null $filePath
     * @return bool
     */
    public static function isReadable($filePath = null)
    {
        return (!is_null($filePath) && file_exists($filePath) && is_readable($filePath) && (filesize($filePath) > 0));
    }

    /**
     * @param string|null $filePath
     * @return bool
     */
    public static function isWritable($filePath = null)
    {
        return (!is_null($filePath) && (is_writable($filePath) || is_writable(dirname($filePath))));
    }

    /**
     * @throws Exceptions\InvalidFileException
     */
    private function readContents()
    {
        if (self::isReadable($this->path)) {
            return @file_get_contents($this->path);
        } else {
            throw new InvalidFileException($this->path);
        }
    }
}
