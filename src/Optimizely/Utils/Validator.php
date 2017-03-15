<?php
/**
 * Copyright 2016, Optimizely
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
use Optimizely\Entity\Experiment;
use Optimizely\ProjectConfig;


class Validator
{
    /**
     * @param $datafile string JSON string representing the project.
     *
     * @return boolean representing whether schema is valid or not.
     */
    public static function validateJsonSchema($datafile)
    {
        $jsonSchemaObject = json_decode(file_get_contents(__DIR__.'/schema.json'));
        $datafileJson = json_decode($datafile);

        $jsonValidator = new JsonSchema\Validator;
        $jsonValidator->check($datafileJson, $jsonSchemaObject);
        return $jsonValidator->isValid() ?  true : false;
    }

    /**
     * @param $attributes mixed Attributes of the user.
     *
     * @return boolean representing whether attributes are valid or not.
     */
    public static function areAttributesValid($attributes)
    {
        return is_array($attributes) && count(array_filter(array_keys($attributes), 'is_int')) == 0;
    }

    /**
     * @param $eventTags mixed Event tags to be validated.
     *
     * @return boolean representing whether event tags are valid or not.
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

        // Return false if there is audience, but no user attributes.
        if (empty($userAttributes)) {
            return false;
        }

        // Return true if conditions for any audience are met.
        $conditionEvaluator = new ConditionEvaluator();
        forEach ($audienceIds as $audienceId) {
            $audience = $config->getAudience($audienceId);
            $result = $conditionEvaluator->evaluate($audience->getConditionsList(), $userAttributes);
            if ($result) {
                return true;
            }
        }

        return false;
    }
}
