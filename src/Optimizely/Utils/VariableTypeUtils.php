<?php
/**
 * Copyright 2017, Optimizely
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

use Exception;
use Monolog\Logger;
use Optimizely\Entity\FeatureVariable;
use Optimizely\Logger\LoggerInterface;

class VariableTypeUtils
{
    public static function castStringToType($value, $variableType, LoggerInterface $logger = null)
    {
        if ($variableType == FeatureVariable::STRING_TYPE) {
            return $value;
        }

        $return_value = null;

        switch ($variableType) {
        case FeatureVariable::BOOLEAN_TYPE:
            $return_value = strtolower($value) == "true";
            break;

        case FeatureVariable::INTEGER_TYPE:
            if (ctype_digit($value)) {
                $return_value = (int) $value;
            }
            break;

        case FeatureVariable::DOUBLE_TYPE:
            if (is_numeric($value)) {
                $return_value = (float) $value;
            }
            break;
        }

        if (is_null($return_value) && $logger) {
            $logger->log(Logger::ERROR, "Unable to cast variable value '{$value}' to type '{$variableType}'.");
        }

        return $return_value;
    }
}
