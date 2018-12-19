<?php
/**
 * Copyright 2016-2018, Optimizely
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
        if(!is_array($attributes)){
            return false;
        }

        if (empty($attributes)){
            return true;
        }
        // At least one key string to be an associative array.
        return count(array_filter(array_keys($attributes), 'is_string')) > 0;
    }

    /**
     * @param $attributeKey The key to validate.
     * @param $attributeValue The value to validate.
     *
     * @return boolean Representing whether attribute's key and value are
     * valid for event payload or not.
     */
    public static function isAttributeValid($attributeKey, $attributeValue)
    {
        $validTypes = array('boolean', 'double', 'integer', 'string');
        return is_string($attributeKey) && in_array(gettype($attributeValue), $validTypes);
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
     *
     * @return boolean Representing whether user meets audience conditions to be in experiment or not.
     */
    public static function isUserInExperiment($config, $experiment, $userAttributes)
    {
        $audienceIds = $experiment->getAudienceIds();

        // Return true if experiment is not targeted to any audience.
        if (empty($audienceIds)) {
            return true;
        }

        if ($userAttributes == null) {
            $userAttributes = [];
        }

        $customAttrCondEval = new CustomAttributeConditionEvaluator($userAttributes);
        $evaluateCustomAttr = function($leafCondition) use ($customAttrCondEval) {
            return $customAttrCondEval->evaluate($leafCondition);
        };

        // Return true if conditions for any audience are met.
        $conditionTreeEvaluator = new ConditionTreeEvaluator();
        foreach ($audienceIds as $audienceId) {
            $audience = $config->getAudience($audienceId);
            $result = $conditionTreeEvaluator->evaluate($audience->getConditionsList(), $evaluateCustomAttr);
            if ($result) {
                return true;
            }
        }

        return false;
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
     * Checks if the given input is a number and is not one of NAN, INF, -INF.
     * 
     * @param  $value Input to check.
     * 
     * @return boolean true if given input is a number but not +/-Infinity or NAN, false otherwise.
     */
    public static function isFiniteNumber($value)
    {
        if(!is_numeric($value) ) {
            return false;
        }

        if(is_string($value) || is_nan($value) || is_infinite($value)) {
            return false;
        }

        return true;
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

        if(in_array($firstValType, $numberTypes) && in_array($secondValType, $numberTypes)) {
            return True;
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
        if(empty($arr)) {
            return false;
        }

        return count(array_filter(array_keys($arr), 'is_string')) == count(array_keys($arr));
    }
}
