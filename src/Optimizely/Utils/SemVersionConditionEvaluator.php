<?php
/**
 * Copyright 2020, Optimizely
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Optimizely\Utils;

use Monolog\Logger;
use Optimizely\Enums\CommonAudienceEvaluationLogs as logs;

class SemVersionConditionEvaluator
{
    const BUILD_SEPARATOR = '+';
    const PRE_RELEASE_SEPARATOR = '-';
    const WHITESPACE_SEPARATOR = ' ';

    /**
     * @var Condition
     */
    protected $condition;

    /**
     * SemVersionConditionEvaluator constructor
     *
     * @param string $condition string for semantic version.
     * @param $logger LoggerInterface.
     */
    public function __construct($condition, $logger)
    {
        $this->condition = $condition;
        $this->logger = $logger;
    }

    /**
     * compares condition version with the provided attribute version.
     *
     * @param  object $attribute
     *
     * @return null|int 0 if user's semver attribute is equal to the semver condition value,
     *                  1 if user's semver attribute is greater than the semver condition value,
     *                  -1 if user's semver attribute is less than the semver condition value,
     *                  null if the condition value or user attribute value has an invalid type, or
     *                  if there is a mismatch between the user attribute type and the condition
     *                  value type.
     */
    public function compareVersion($targetedVersion)
    {
        $targetedVersionParts = $this->splitSemanticVersion($targetedVersion);
        if ($targetedVersionParts === null) {
            return null;
        }
        $versionParts = $this->splitSemanticVersion($this->condition);
        if ($versionParts === null) {
            return null;
        }

        for ($i = 0; $i < count($targetedVersionParts); $i++) {
            if (count($versionParts) <= $i) {
                if ($this->isPreRelease($targetedVersion)) {
                    return 1;
                }
                return -1;
            } elseif (!is_numeric($versionParts[$i])) {
                // Compare strings
                if (strcasecmp($versionParts[$i], $targetedVersionParts[$i]) < 0) {
                    return -1;
                } elseif (strcasecmp($versionParts[$i], $targetedVersionParts[$i]) > 0) {
                    return 1;
                }
            } elseif (is_numeric($targetedVersionParts[$i])) {
                // both targetedVersionParts and versionParts are digits
                if ($this->toInt($versionParts[$i]) < $this->toInt($targetedVersionParts[$i])) {
                    return -1;
                } elseif ($this->toInt($versionParts[$i]) > $this->toInt($targetedVersionParts[$i])) {
                    return 1;
                }
            } else {
                return -1;
            }
        }
        if (!$this->isPreRelease($targetedVersion) && $this->isPreRelease($this->condition)) {
            return -1;
        }
        return 0;
    }

    /**
     * Splits given version into appropriate semantic version array.
     *
     * @param  string $targetedVersion
     *
     * @return null|array   array if provided string was successfully split,
     *                      null if any issues occured.
     */
    protected function splitSemanticVersion($targetedVersion)
    {
        if (strpos($targetedVersion, self::WHITESPACE_SEPARATOR) !== false) {
            $this->logger->log(Logger::WARNING, sprintf(
                logs::ATTRIBUTE_FORMAT_INVALID,
                json_encode($this->condition),
            ));
            return null;
        }

        $targetPrefix = $targetedVersion;
        $targetSuffix = array();

        if ($this->isPreRelease($targetedVersion) || $this->isBuild($targetedVersion)) {
            $sep = self::BUILD_SEPARATOR;
            if ($this->isPreRelease($targetedVersion)) {
                $sep = self::PRE_RELEASE_SEPARATOR;
            }

            // this is going to split with the first occurrence.
            $targetParts = array_filter(explode($sep, $targetedVersion), 'strlen');

            // in the case it is neither a build or pre release it will return the
            // original string as the first element in the array
            if (count($targetParts) <= 1) {
                $this->logger->log(Logger::WARNING, sprintf(
                    logs::ATTRIBUTE_FORMAT_INVALID,
                    json_encode($this->condition),
                ));
                return null;
            }
            $targetPrefix = $targetParts[0];
            $targetSuffix = array_slice($targetParts, 1, (count($targetParts) - 1));
        }

        // Expect a version string of the form x.y.z
        $dotCount = substr_count($targetPrefix, ".");
        if ($dotCount > 2) {
            $this->logger->log(Logger::WARNING, sprintf(
                logs::ATTRIBUTE_FORMAT_INVALID,
                json_encode($this->condition),
            ));
            return null;
        }

        $targetedVersionParts = array_filter(explode(".", $targetPrefix), 'strlen');
        $targetedVersionPartsCount = count($targetedVersionParts);

        if ($targetedVersionPartsCount !== ($dotCount + 1)) {
            $this->logger->log(Logger::WARNING, sprintf(
                logs::ATTRIBUTE_FORMAT_INVALID,
                json_encode($this->condition),
            ));
            return null;
        }

        foreach ($targetedVersionParts as $val) {
            if (!is_numeric($val)) {
                $this->logger->log(Logger::WARNING, sprintf(
                    logs::ATTRIBUTE_FORMAT_INVALID,
                    json_encode($this->condition),
                ));
                return null;
            }
        }

        if ($targetSuffix !== null) {
            return array_merge($targetedVersionParts, $targetSuffix);
        }
        return $targetedVersionParts;
    }

    /**
     * converts string value to int.
     *
     * @param  string $str value to be converted to int.
     *
     * @return int converted value or 0 if value could not be converted.
     */
    private function toInt($str)
    {
        if (is_numeric($str)) {
            return intval($str);
        }
        return 0;
    }

    /**
     * checks if string contains prerelease seperator.
     *
     * @param  string $str value to be checked.
     *
     * @return bool true if string contains prerelease seperator, false otherwise.
     */
    private function isPreRelease($str)
    {
        return strpos($str, self::PRE_RELEASE_SEPARATOR) !== false;
    }

    /**
     * checks if string contains build seperator.
     *
     * @param  string $str value to be checked.
     *
     * @return bool true if string contains build seperator, false otherwise.
     */
    private function isBuild($str)
    {
        return strpos($str, self::BUILD_SEPARATOR) !== false;
    }
}
