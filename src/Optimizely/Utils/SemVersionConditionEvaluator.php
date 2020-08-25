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

    /**
     * @var Condition
     */
    protected $condition;

    /**
     * CustomAttributeConditionEvaluator constructor
     *
     * @param string $condition string for semantic version.
     * @param $logger LoggerInterface.
     */
    public function __construct($condition, $logger)
    {
        $this->condition = $condition;
        $this->logger = $logger;
    }

    public function splitSemanticVersion($targetedVersion)
    {
        if (!preg_match('/\S/', $targetedVersion)) {
            $this->logger->log(Logger::WARNING, sprintf(
                logs::ATTRIBUTE_FORMAT_INVALID,
                json_encode($this->condition),
            ));
            return null;
        }
    }
}
