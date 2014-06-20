<?php

namespace GeneralPattern\Features;

use GeneralPattern\File;

/**
 * Class FindDuplicates
 * @package Corgi\Features
 * @author gabor.zelei@geopal-solutions.com
 *
 * Looks for duplicate entries in the logs,
 * based on regex configuration in config.json
 */
class FindDuplicates extends Feature
{
    /**
     * Static array to store processed lines
     * for later comparison
     *
     * @var array
     */
    private static $cleanedUpLines = array();

    /**
     * @param string $line
     * @param array $metric
     * @param File $file
     * @return mixed
     */
    public function processLine($line, $metric, File $file)
    {
        $metricGroup = $this->getMetricGroupName($metric);

        if ($this->includeFileInMetric($file, $metric)) {
            $cleanedUpLine = $this->cleanUpLine($line, $metric);
            $cleanedUpLineMD5 = md5($cleanedUpLine);

            if (isset(self::$cleanedUpLines[$cleanedUpLineMD5]) && $this->match($line, $metric)) {
                $key = md5($this->cleanMetricKey($cleanedUpLine, $metric));
                $linesForKey = (array)$this->result->get($metricGroup, $metric['name'], $key);

                if (self::$cleanedUpLines[$cleanedUpLineMD5][0] !== $line) {

                    if (count($linesForKey) < 1) {
                        $linesForKey[] = self::$cleanedUpLines[$cleanedUpLineMD5][0];
                    }

                    $linesForKey[] = $line;

                    $this->result->set(
                        $metricGroup,
                        $metric['name'],
                        $key,
                        $linesForKey
                    );
                }
            } else {

                if (!is_array(self::$cleanedUpLines[$cleanedUpLineMD5])) {
                    self::$cleanedUpLines[$cleanedUpLineMD5] = array();
                }

                self::$cleanedUpLines[$cleanedUpLineMD5][] = $line;
            }
        }
    }

    /**
     * Removes sub-strings that need to be ignored
     * from the line
     *
     * @param string $line
     * @param array $metric
     * @return string
     */
    private function cleanUpLine($line, $metric)
    {
        if (isset($metric['ignore'])) {
            return preg_replace($metric['ignore'], '', $line);
        } else {
            return $line;
        }
    }

    /**
     * Checks if this line matches pre-requisites
     * for processing
     *
     * @param string $line
     * @param array $metric
     * @return bool
     */
    private function match($line, $metric)
    {
        if (isset($metric['match'])) {

            foreach ((array)$metric['match'] as $regex) {

                if (preg_match($regex, $line) == 1) {
                    return true;
                }

            }

            return false;
        } else {
            return true;
        }

    }
}
