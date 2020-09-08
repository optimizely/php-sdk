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
     * compares targeted version with the provided user version.
     *
     * @param  object $targetedVersion
     * @param  object $userVersion
     * @param  object $logger
     *
     * @return null|int 0 if user's semver attribute is equal to the semver condition value,
     *                  1 if user's semver attribute is greater than the semver condition value,
     *                  -1 if user's semver attribute is less than the semver condition value,
     *                  null if the condition value or user attribute value has an invalid type, or
     *                  if there is a mismatch between the user attribute type and the condition
     *                  value type.
     */
    public static function compareVersion($targetedVersion, $userVersion, $logger)
    {
        $targetedVersionParts = self::splitSemanticVersion($targetedVersion, $logger);
        if ($targetedVersionParts === null) {
            return null;
        }
        $userVersionParts = self::splitSemanticVersion($userVersion, $logger);
        if ($userVersionParts === null) {
            return null;
        }
        $userVersionPartsCount = count($userVersionParts);
        $isPreReleaseTargetedVersion = self::isPreRelease($targetedVersion);
        $isPreReleaseUserVersion = self::isPreRelease($userVersion);

        // Up to the precision of targetedVersion, expect version to match exactly.
        for ($i = 0; $i < count($targetedVersionParts); $i++) {
            if ($userVersionPartsCount <= $i) {
                if ($isPreReleaseTargetedVersion) {
                    return 1;
                }
                return -1;
            } elseif (!is_numeric($userVersionParts[$i])) {
                // Compare strings
                if (strcasecmp($userVersionParts[$i], $targetedVersionParts[$i]) < 0) {
                    if ($isPreReleaseTargetedVersion && !$isPreReleaseUserVersion) {
                        return 1;
                    }
                    return -1;
                } elseif (strcasecmp($userVersionParts[$i], $targetedVersionParts[$i]) > 0) {
                    if (!$isPreReleaseTargetedVersion && $isPreReleaseUserVersion) {
                        return -1;
                    }
                    return 1;
                }
            } elseif (is_numeric($targetedVersionParts[$i])) {
                // both targetedVersionParts and versionParts are digits
                if (intval($userVersionParts[$i]) < intval($targetedVersionParts[$i])) {
                    return -1;
                } elseif (intval($userVersionParts[$i]) > intval($targetedVersionParts[$i])) {
                    return 1;
                }
            } else {
                return -1;
            }
        }
        if (!$isPreReleaseTargetedVersion && $isPreReleaseUserVersion) {
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
    private static function splitSemanticVersion($version, $logger)
    {
        if (strpos($version, self::WHITESPACE_SEPARATOR) !== false) {
            $logger->log(Logger::WARNING, sprintf(
                logs::ATTRIBUTE_FORMAT_INVALID
            ));
            return null;
        }

        $targetPrefix = $version;
        $targetSuffix = array();

        $separator = null;
        if (self::isPreRelease($version)) {
            $separator = self::PRE_RELEASE_SEPARATOR;
        } elseif (self::isBuild($version)) {
            $separator = self::BUILD_SEPARATOR;
        }

        if ($separator !== null) {
            $targetParts = explode($separator, $version, 2);
            if (count($targetParts) <= 1) {
                $logger->log(Logger::WARNING, sprintf(
                    logs::ATTRIBUTE_FORMAT_INVALID
                ));
                return null;
            }
            $targetPrefix = $targetParts[0];
            $targetSuffix = array_slice($targetParts, 1, (count($targetParts) - 1));
        }

        // Expect a version string of the form x.y.z
        $dotCount = substr_count($targetPrefix, ".");
        if ($dotCount > 2) {
            $logger->log(Logger::WARNING, sprintf(
                logs::ATTRIBUTE_FORMAT_INVALID
            ));
            return null;
        }

        $targetedVersionParts = array_filter(explode(".", $targetPrefix), 'strlen');
        $targetedVersionPartsCount = count($targetedVersionParts);

        if ($targetedVersionPartsCount !== ($dotCount + 1)) {
            $logger->log(Logger::WARNING, sprintf(
                logs::ATTRIBUTE_FORMAT_INVALID
            ));
            return null;
        }

        foreach ($targetedVersionParts as $val) {
            if (!is_numeric($val)) {
                $logger->log(Logger::WARNING, sprintf(
                    logs::ATTRIBUTE_FORMAT_INVALID
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
     * Checks if given string is a prerelease version.
     *
     * @param  string $version value to be checked.
     *
     * @return bool true if given version is a prerelease.
     */
    private static function isPreRelease($version)
    {
        //check if string contains prerelease seperator before build separator
        $preReleasePos = strpos($version, self::PRE_RELEASE_SEPARATOR);
        if ($preReleasePos === false) {
            return false;
        }

        $buildPos = strpos($version, self::BUILD_SEPARATOR);
        if ($buildPos === false) {
            return true;
        }
        return $buildPos > $preReleasePos;
    }

    /**
     * Checks if given string is a build version.
     *
     * @param  string $version value to be checked.
     *
     * @return bool true if given version is a build.
     */
    private static function isBuild($version)
    {
        // checks if string contains build seperator before prerelease separator
        $buildPos = strpos($version, self::BUILD_SEPARATOR);
        if ($buildPos === false) {
            return false;
        }

        $preReleasePos = strpos($version, self::PRE_RELEASE_SEPARATOR);
        if ($preReleasePos == false) {
            return true;
        }
        return $preReleasePos > $buildPos;
    }
}
