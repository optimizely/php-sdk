<?php
/**
 * Copyright 2018, Optimizely
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

use Optimizely\Utils\Validator;

class CustomAttributeConditionEvaluator
{
    const CUSTOM_ATTRIBUTE_CONDITION_TYPE = 'custom_attribute';

    const EXACT_MATCH_TYPE = 'exact';
    const EXISTS_MATCH_TYPE = 'exists';
    const GREATER_THAN_MATCH_TYPE = 'gt';
    const LESS_THAN_MATCH_TYPE = 'lt';
    const SUBSTRING_MATCH_TYPE = 'substring';

    /**
     * @var UserAttributes
    */
    protected $userAttributes;

    /**
     * CustomAttributeConditionEvaluator constructor
     * 
     * @param array $userAttributes Associative array of user attributes to values.
     */
    public function __construct(array $userAttributes)
    {
        $this->userAttributes = $userAttributes;
    }

    /**
     * Gets the supported match types for condition evaluation.
     * 
     * @return array List of supported match types.
     */
    protected function getMatchTypes()
    {
        return array(self::EXACT_MATCH_TYPE, self::EXISTS_MATCH_TYPE, self::GREATER_THAN_MATCH_TYPE,
         self::LESS_THAN_MATCH_TYPE, self::SUBSTRING_MATCH_TYPE);
    }

    /**
     * Gets the evaluator method name for the given match type.
     * 
     * @param  string $matchType Match type for which to get evaluator. 
     * 
     * @return string Corresponding evaluator method name.
     */
    protected function getEvaluatorByMatchType($matchType)
    {
        $evaluatorsByMatchType = array();
        $evaluatorsByMatchType[self::EXACT_MATCH_TYPE] = 'exactEvaluator';
        $evaluatorsByMatchType[self::EXISTS_MATCH_TYPE] = 'existsEvaluator';
        $evaluatorsByMatchType[self::GREATER_THAN_MATCH_TYPE] = 'greaterThanEvaluator';
        $evaluatorsByMatchType[self::LESS_THAN_MATCH_TYPE] = 'lessThanEvaluator';
        $evaluatorsByMatchType[self::SUBSTRING_MATCH_TYPE] = 'substringEvaluator';

        return $evaluatorsByMatchType[$matchType];
    }

    /**
     * Checks if the given input is a valid value for exact condition evaluation.
     * 
     * @param  $value Input to check.
     * @return boolean true if given input is a string/boolean/finite number, false otherwise.
     */
    protected function isValueValidForExactConditions($value)
    {
        if(is_string($value) || is_bool($value) || Validator::isFiniteNumber($value)) {
            return true;
        }

        return false;
    }

    /**
     * Evaluate the given exact match condition for the given user attributes.
     * 
     * @param  object $condition
     * 
     * @return null|boolean true if the user attribute value is equal (===) to the condition value,
     *                      false if the user attribute value is not equal (!==) to the condition value,
     *                      null if the condition value or user attribute value has an invalid type, or
     *                      if there is a mismatch between the user attribute type and the condition
     *                      value type
     */
    protected function exactEvaluator($condition)
    {
        $conditionName = $condition->{'name'};
        $conditionValue = $condition->{'value'};
        $userValue = isset($this->userAttributes[$conditionName]) ? $this->userAttributes[$conditionName]: null;

        if(!$this->isValueValidForExactConditions($userValue) ||
            !$this->isValueValidForExactConditions($conditionValue) ||
            !Validator::areValuesSameType($conditionValue, $userValue)) {
                return null;
            }

        return $conditionValue == $userValue;
    }

    /**
     * Evaluate the given exists match condition for the given user attributes.
     * 
     * @param  object $condition
     * 
     * @return null|boolean true if both:
     *                           1) the user attributes have a value for the given condition, and
     *                           2) the user attribute value is not null.
     *                      false otherwise.
     */
    protected function existsEvaluator($condition) 
    {
        $conditionName = $condition->{'name'};
        return isset($this->userAttributes[$conditionName]);
    }

    /**
     * Evaluate the given greater than match condition for the given user attributes.
     * 
     * @param  object $condition
     * 
     * @return boolean true if the user attribute value is greater than the condition value,
     *                 false if the user attribute value is less than or equal to the condition value,
     *                 null if the condition value isn't a number or the user attribute value
     *                 isn't a number
     */
    protected function greaterThanEvaluator($condition)
    {
        $conditionName = $condition->{'name'};
        $conditionValue = $condition->{'value'};
        $userValue = isset($this->userAttributes[$conditionName]) ? $this->userAttributes[$conditionName]: null;

        if(!Validator::isFiniteNumber($userValue) || !Validator::isFiniteNumber($conditionValue)) {
            return null;
        }

        return $userValue > $conditionValue;
    }

    /**
     * Evaluate the given less than match condition for the given user attributes.
     * 
     * @param  object $condition
     * 
     * @return boolean true if the user attribute value is less than the condition value,
     *                 false if the user attribute value is greater than or equal to the condition value,
     *                 null if the condition value isn't a number or the user attribute value
     *                 isn't a number
     */
    protected function lessThanEvaluator($condition)
    {
        $conditionName = $condition->{'name'};
        $conditionValue = $condition->{'value'};
        $userValue = isset($this->userAttributes[$conditionName]) ? $this->userAttributes[$conditionName]: null;

        if(!Validator::isFiniteNumber($userValue) || !Validator::isFiniteNumber($conditionValue)) {
            return null;
        }

        return $userValue < $conditionValue;
    }

     /**
     * Evaluate the given substring than match condition for the given user attributes.
     * 
     * @param  object $condition
     * 
     * @return boolean true if the condition value is a substring of the user attribute value,
     *                 false if the condition value is not a substring of the user attribute value,
     *                 null if the condition value isn't a string or the user attribute value
     *                 isn't a string
     */
    protected function substringEvaluator($condition)
    {
        $conditionName = $condition->{'name'};
        $conditionValue = $condition->{'value'};
        $userValue = isset($this->userAttributes[$conditionName]) ? $this->userAttributes[$conditionName]: null;

        if(!is_string($userValue) || !is_string($conditionValue)) {
            return null;
        }

        return strpos($userValue, $conditionValue) !== false;
    }

    /**
     * Function to evaluate audience conditions against user's attributes.
     *
     * @param array $leafCondition  Condition to be evaluated. 
     *
     * @return null|boolean true/false if the given user attributes match/don't match the given conditions, 
     * null if the given user attributes and conditions can't be evaluated.
     */
    public function evaluate($leafCondition)
    {
        if($leafCondition->{'type'} !== self::CUSTOM_ATTRIBUTE_CONDITION_TYPE) {
            return null;
        }

        if(!isset($leafCondition->{'match'})) {
            $conditionMatch = self::EXACT_MATCH_TYPE;
        } else {
            $conditionMatch = $leafCondition->{'match'};
        }

        if(!in_array($conditionMatch, $this->getMatchTypes())) {
            return null;
        }

        $evaluatorForMatch = $this->getEvaluatorByMatchType($conditionMatch);
        return $this->$evaluatorForMatch($leafCondition);
    }
}
