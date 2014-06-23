<?php

namespace GeneralPattern\Features;

use GeneralPattern\File;

/**
 * Class GetCount
 * @package GeneralPattern\Features
 * @author gabor.zelei@geopal-solutions.com
 *
 * Gets string occurrences matching the regex defined in
 * config.json, and counts how many times they re-appear
 * in the logs
 */
class GetCount extends Feature
{
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
            $matches = array();

            if ($metric['match_all']) {
                $matchesFound = preg_match_all($metric['regex'], $line, $matches);
            } else {
                $matchesFound = preg_match($metric['regex'], $line, $matches);
            }

            if (($matchesFound !== false) && ($matchesFound > 0)) {
                $key = $this->cleanMetricKey($matches[0], $metric);
                $this->result->set(
                    $metricGroup,
                    $metric['name'],
                    $key,
                    ((int)$this->result->get(
                            $metricGroup,
                            $metric['name'],
                            $key
                        ) + $matchesFound)
                );
            }
        }
    }
}
