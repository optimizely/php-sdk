<?php
/**
 * Copyright 2016-2019, Optimizely
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

use JsonSchema;
use Monolog\Logger;
use Optimizely\Entity\Experiment;
use Optimizely\Enums\AudienceEvaluationLogs;
use Optimizely\Logger\LoggerInterface;
use Optimizely\ProjectConfig;
use Optimizely\Utils\ConditionTreeEvaluator;
use Optimizely\Utils\CustomAttributeConditionEvaluator;

class Validator
{
    /**
     * @param $datafile string JSON string representing the project.
     *
     * @return boolean Representing whether schema is valid or not.
     */
    public static function validateJsonSchema($datafile, LoggerInterface $logger = null)
    {
        $data = json_decode($datafile);

        // Validate
        $validator = new JsonSchema\Validator;
        $validator->check($data, (object)['$ref' => 'file://' . __DIR__.'/schema.json']);

        if ($validator->isValid()) {
            return true;
        } else {
            if ($logger) {
                $logger->log(Logger::DEBUG, "JSON does not validate. Violations:\n");
                ;
                foreach ($validator->getErrors() as $error) {
                    $logger->log(Logger::DEBUG, "[%s] %s\n", $error['property'], $error['message']);
                }
            }

            return false;
        }
    }

    /**
     * @param $attributes mixed Attributes of the user.
     *
     * @return boolean Representing whether attributes are valid or not.
     */
    public static function areAttributesValid($attributes)
    {
        if (!is_array($attributes)) {
            return false;
        }

        if (empty($attributes)) {
            return true;
        }
        // At least one key string to be an associative array.
        return count(array_filter(array_keys($attributes), 'is_string')) > 0;
    }

    /**
     * @param $value The value to validate.
     *
     * @return boolean Representing whether attribute's value is
     * a number and not NAN, INF, -INF or greater than absolute limit of 2^53.
     */
    public static function isFiniteNumber($value)
    {
        if (!(is_int($value) || is_float($value))) {
            return false;
        }

        if (is_nan($value) || is_infinite($value)) {
            return false;
        }

        if (abs($value) > pow(2, 53)) {
            return false;
        }

        return true;
    }

    /**
     * @param $attributeKey The key to validate.
     * @param $attributeValue The value to validate.
     *
     * @return boolean Representing whether attribute's key and value are
     * valid for event payload or not. Valid attribute key must be a string.
     * Valid attribute value can be a string, bool, or a finite number.
     */
    public static function isAttributeValid($attributeKey, $attributeValue)
    {
        if (!is_string($attributeKey)) {
            return false;
        }

        if (is_string($attributeValue) || is_bool($attributeValue)) {
            return true;
        }

        if (is_int($attributeValue) || is_float($attributeValue)) {
            return Validator::isFiniteNumber($attributeValue);
        }

        return false;
    }

    /**
     * @param $eventTags mixed Event tags to be validated.
     *
     * @return boolean Representing whether event tags are valid or not.
     */
    public static function areEventTagsValid($eventTags)
    {
        return is_array($eventTags) && count(array_filter(array_keys($eventTags), 'is_int')) == 0;
    }

    /**
     * @param $config ProjectConfig Configuration for the project.
     * @param $experiment Experiment Entity representing the experiment.
     * @param $userAttributes array Attributes of the user.
     *  @param $logger LoggerInterface.
     *
     * @return boolean Representing whether user meets audience conditions to be in experiment or not.
     */
    public static function isUserInExperiment($config, $experiment, $userAttributes, $logger)
    {
        $audienceConditions = $experiment->getAudienceConditions();
        if ($audienceConditions === null) {
            $audienceConditions = $experiment->getAudienceIds();
        }

        $logger->log(Logger::DEBUG, sprintf(
            AudienceEvaluationLogs::EVALUATING_AUDIENCES_COMBINED,
            $experiment->getKey(),
            json_encode($audienceConditions)
        ));

        // Return true if experiment is not targeted to any audience.
        if (empty($audienceConditions)) {
            $logger->log(Logger::INFO, sprintf(
                AudienceEvaluationLogs::AUDIENCE_EVALUATION_RESULT_COMBINED,
                $experiment->getKey(), 'True'
            ));
            return true;
        }

        if ($userAttributes === null) {
            $userAttributes = [];
        }

        $customAttrCondEval = new CustomAttributeConditionEvaluator($userAttributes, $logger);
        $evaluateCustomAttr = function ($leafCondition) use ($customAttrCondEval) {
            return $customAttrCondEval->evaluate($leafCondition);
        };

        $evaluateAudience = function ($audienceId) use ($config, $evaluateCustomAttr, $logger) {
            $audience = $config->getAudience($audienceId);
            if ($audience === null) {
                return null;
            }
            
            $logger->log(Logger::DEBUG, sprintf(
                AudienceEvaluationLogs::EVALUATING_AUDIENCE,
                $audienceId,
                json_encode($audience->getConditionsList())
            ));

            $conditionTreeEvaluator = new ConditionTreeEvaluator();
            $result = $conditionTreeEvaluator->evaluate($audience->getConditionsList(), $evaluateCustomAttr);
            $resultStr = $result === null ? 'UNKNOWN' : strtoupper(var_export($result, true));

            $logger->log(Logger::INFO, sprintf(
                AudienceEvaluationLogs::AUDIENCE_EVALUATION_RESULT,
                $audienceId,
                $resultStr
            ));

            return $result;
        };

        $conditionTreeEvaluator = new ConditionTreeEvaluator();
        $evalResult = $conditionTreeEvaluator->evaluate($audienceConditions, $evaluateAudience);
        $evalResult = $evalResult || false;

        $logger->log(Logger::INFO, sprintf(
            AudienceEvaluationLogs::AUDIENCE_EVALUATION_RESULT_COMBINED,
            $experiment->getKey(),
            ucfirst(var_export($evalResult, true))
        ));

        return $evalResult;
    }

    /**
     * Checks that if there are more than one experiment IDs
     * in the feature flag, they must belong to the same mutex group
     *
     * @param ProjectConfig $config      The project config to verify against
     * @param FeatureFlag   $featureFlag The feature to validate
     *
     * @return boolean True if feature flag is valid
     */
    public static function isFeatureFlagValid($config, $featureFlag)
    {
        $experimentIds = $featureFlag->getExperimentIds();

        if (empty($experimentIds)) {
            return true;
        }
        if (sizeof($experimentIds) == 1) {
            return true;
        }

        $groupId = $config->getExperimentFromId($experimentIds[0])->getGroupId();
        foreach ($experimentIds as $id) {
            $experiment = $config->getExperimentFromId($id);
            $grpId = $experiment->getGroupId();
            if ($groupId != $grpId) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if the provided value is a non-empty string
     *
     * @param $value The value to validate
     *
     * @return boolean True if $value is a non-empty string
     */
    public static function validateNonEmptyString($value)
    {
        if (is_string($value) && $value!='') {
            return true;
        }

        return false;
    }

    /**
     * Method to verify that both values belong to same type.
     * Float/Double and Integer are considered similar.
     *
     * @param  mixed  $firstVal
     * @param  mixed  $secondVal
     *
     * @return bool   True if values belong to similar types. Otherwise, False.
     */
    public static function areValuesSameType($firstVal, $secondVal)
    {
        $firstValType = gettype($firstVal);
        $secondValType = gettype($secondVal);
        $numberTypes = array('double', 'integer');

        if (in_array($firstValType, $numberTypes) && in_array($secondValType, $numberTypes)) {
            return true;
        }

        return $firstValType == $secondValType;
    }

    /**
     * Returns true only if given input is an array with all of it's keys of type string.
     * @param  mixed $arr
     * @return bool  True if array contains all string keys. Otherwise, false.
     */
    public static function doesArrayContainOnlyStringKeys($arr)
    {
        if (!is_array($arr) || empty($arr)) {
            return false;
        }

        return count(array_filter(array_keys($arr), 'is_string')) == count(array_keys($arr));
    }
}
