<?php

namespace GeneralPattern;

/**
 * Class Result
 * @package Corgi
 * @author gabor.zelei@geopal-solutions.com
 *
 * Class to collect and format results
 */
class Result
{
    const DEFAULT_METRIC_GROUP_NAME = 'Default Metric Group';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var array
     */
    private $metrics = array();

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Returns the value of a metric
     *
     * @param string $metricGroup
     * @param string $metricName
     * @param string $key
     * @return mixed
     */
    public function get($metricGroup = null, $metricName = null, $key = null)
    {
        $this->trim($metricGroup);
        $this->trim($metricName);
        $this->trim($key);

        if (empty($metricGroup) || !is_string($metricGroup)) {
            $metricGroup = self::DEFAULT_METRIC_GROUP_NAME;
        }

        if (!empty($metricName) && is_string($metricName)) {

            if (!empty($key) && is_string($key)) {
                return $this->metrics[$metricGroup][$metricName][$key];
            }

            return $this->metrics[$metricGroup][$metricName];
        }

        return $this->metrics[$metricGroup];
    }

    /**
     * Sets value to a metric
     *
     * @param string $metricGroup
     * @param string $metricName
     * @param string $key
     * @param mixed $value
     */
    public function set($metricGroup, $metricName, $key, $value)
    {
        $this->trim($metricGroup);
        $this->trim($metricName);
        $this->trim($key);

        if (empty($metricGroup) || !is_string($metricGroup)) {
            $metricGroup = self::DEFAULT_METRIC_GROUP_NAME;
        }

        $this->metrics[$metricGroup][$metricName][$key] = $value;
    }

    /**
     * Displays results as a (sorted) array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->sortedResults();
    }

    /**
     * Processes and prepares sorting directives in the config file
     * for use in the script
     *
     * @param string $metricName
     * @return array|bool
     */
    private function getSortingDetailsForMetricName($metricName)
    {
        foreach ($this->config->get('metrics') as $metric) {

            if ($metric['name'] == $metricName) {

                if (isset($metric['sort'])) {

                    switch($metric['sort']['as']) {
                        case 'natural':
                            $sortAs = SORT_NATURAL;
                            break;
                        case 'number':
                            $sortAs = SORT_NUMERIC;
                            break;
                        default:
                            $sortAs = SORT_STRING;
                            break;
                    }

                    return array(
                        'by' => in_array($metric['sort']['by'], array('name', 'value'))
                            ? $metric['sort']['by']
                            : 'value',
                        'as' => $sortAs,
                        'order' => isset($metric['sort']['order']) && ($metric['sort']['order'] == 'desc')
                            ? SORT_DESC
                            : SORT_ASC
                    );
                } else {
                    return false;
                }

            }

        }

        return false;
    }

    /**
     * Sorts results as requested in the config.json file
     *
     * @return array
     */
    private function sortedResults()
    {
        $sortedMetrics = array();

        foreach($this->metrics as $metricGroupName => $metricGroupValues) {
            $sortedMetrics[$metricGroupName] = isset($sortedMetrics[$metricGroupName])
                ? $sortedMetrics[$metricGroupName]
                : array();

            foreach ($metricGroupValues as $metricName => $metricValues) {
                $sorting = $this->getSortingDetailsForMetricName($metricName);

                if ($sorting !== false) {

                    if ($sorting['by'] == 'name') {

                        if ($sorting['order'] == SORT_ASC) {
                            ksort($metricValues, $sorting['as']);
                        } else {
                            krsort($metricValues, $sorting['as']);
                        }

                    } elseif ($sorting['by'] == 'value') {
                        array_multisort($metricValues, $sorting['as'], $sorting['order']);
                    }

                }

                $sortedMetrics[$metricGroupName][$metricName] = $metricValues;
            }

        }

        return (count($sortedMetrics) > 1) ? $this->metrics : $sortedMetrics;
    }

    /**
     * Cleans up a string variable
     *
     * @param $stringVar
     * @return string
     */
    private function trim(&$stringVar)
    {
        $toRemove = array('\\', '"');

        if (!empty($stringVar) && is_string($stringVar)) {
            $stringVar = str_ireplace($toRemove, '', $stringVar);
            return $stringVar;
        }

        return '';
    }
}
