<?php

namespace GeneralPattern\Features;

use GeneralPattern\Config;
use GeneralPattern\File;
use GeneralPattern\Result;

/**
 * Class Feature
 * @package GeneralPattern\Features
 * @author gabor.zelei@geopal-solutions.com
 *
 * Base class for Features
 */
abstract class Feature
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Result
     */
    protected $result;

    /**
     * Method to analyze a line in a log file based on
     * metrics configuration
     *
     * @param string $line
     * @param array $metric
     * @param File $file
     * @return mixed
     */
    abstract public function processLine($line, $metric, File $file);

    /**
     * @param Config $config
     * @param Result $result
     */
    public function __construct(Config &$config, Result &$result)
    {
        $this->config =& $config;
        $this->result =& $result;
    }

    /**
     * Removes unnecessary parts of a dynamically generated metric key
     * See the clean_up option for metrics in config.json
     *
     * @param string $key
     * @param array $metric
     * @return string
     */
    protected function cleanMetricKey($key, $metric)
    {
        if (isset($metric['clean_up'])) {
            $key = str_replace($metric['clean_up'], '', $key);
        }

        return $key;
    }

    /**
     * Returns true, if the input file should be included
     * in the results for the input metric.
     *
     * Returns false otherwise
     *
     * @param File $file
     * @param array $metric
     * @return bool
     */
    protected function includeFileInMetric(File $file, $metric)
    {
        return (($metric['in_files'] == '*') ||
            (is_array($metric['in_files']) &&
                in_array($file->getPath(), $metric['in_files']))
        );
    }

    /**
     * Determines if the metric group name for a given metric is valid
     *
     * @param array $metric
     * @return string
     */
    protected function getMetricGroupName($metric)
    {
        $metricGroupName = isset($metric['group_under']) ? $metric['group_under'] : null;

        if (!is_null($metricGroupName) && !empty($metricGroupName)) {

            foreach ((array)$this->config->get('metrics') as $metric) {

                if ($metric['name'] = $metricGroupName) {
                    return $metricGroupName;
                }

            }

        }

        return '';
    }
}
