<?php
/**
 * Copyright 2016, 2018, Optimizely
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

class ConditionEvaluator
{
    /**
     * const string Representing AND operator.
     */
    const AND_OPERATOR = 'and';

    /**
     * const string Representing OR operator.
     */
    const OR_OPERATOR = 'or';

    /**
     * const string Representing NOT operator.
     */
    const NOT_OPERATOR = 'not';


    const CUSTOM_ATTRIBUTE_CONDITION_TYPE = 'custom_attribute';

    const EXACT_MATCH_TYPE = 'exact';
    const EXISTS_MATCH_TYPE = 'exists';
    const GREATER_THAN_MATCH_TYPE = 'gt';
    const LESS_THAN_MATCH_TYPE = 'lt';
    const SUBSTRING_MATCH_TYPE = 'substring';

    public function getSupportedOperators()
    {
        return array(self::AND_OPERATOR, self::OR_OPERATOR, self::NOT_OPERATOR);
    }

    public function getMatchTypes()
    {
        return array(self::EXACT_MATCH_TYPE, self::EXISTS_MATCH_TYPE, self::GREATER_THAN_MATCH_TYPE,
         self::LESS_THAN_MATCH_TYPE, self::SUBSTRING_MATCH_TYPE);
    }


    public function getEvaluatorByMatchType($matchType)
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
     * @param $conditions array Audience conditions list.
     * @param $userAttributes array Associative array of user attributes to values.
     *
     * @return boolean True if all conditions evaluate to True.
     */
    public function andEvaluator($conditions, $userAttributes)
    {
        $sawNullResult = false;
        foreach ($conditions as $condition) {
            $result = $this->evaluate($condition, $userAttributes);
            
            if($result === false) {
                return false;
            }

            if(is_null($result)) {
                $sawNullResult = true;
            }
        }

        return $sawNullResult ? null : true;
    }

    /**
     * @param $conditions array Audience conditions list.
     * @param $userAttributes array Associative array of user attributes to values.
     *
     * @return boolean True if any one of the conditions evaluate to True.
     */
    public function orEvaluator($conditions, $userAttributes)
    {
        $sawNullResult = false;
        foreach ($conditions as $condition) {
            $result = $this->evaluate($condition, $userAttributes);

            if($result === true) {
                return true;
            }

            if(is_null($result)) {
                $sawNullResult = true;
            }
        }

        return $sawNullResult ? null : false;
    }

    /**
     * @param $condition array Audience conditions list consisting of single condition.
     * @param $userAttributes array Associative array of user attributes to values.
     *
     * @return boolean True if the condition evaluates to False.
     */
    public function notEvaluator($condition, $userAttributes)
    {
        if (empty($condition)) {
            return null;
        }

        $result = $this->evaluate($condition[0], $userAttributes);
        return is_null($result) ? null: !$result;
    }

    /**
     * Function to evaluate audience conditions against user's attributes.
     *
     * @param $conditions array Nested array of and/or/not conditions representing the audience conditions.
     * @param $userAttributes array Associative array of user attributes to values.
     *
     * @return boolean Representing if audience conditions are satisfied or not.
     */
    public function evaluate($conditions, $userAttributes)
    {
        if (is_array($conditions)) {

            $operator = array_shift($conditions);

            switch ($operator) {
                case self::AND_OPERATOR:
                    return $this->andEvaluator($conditions, $userAttributes); 
                case self::NOT_OPERATOR:
                    return $this->notEvaluator($conditions, $userAttributes);
                default:
                    return $this->orEvaluator($conditions, $userAttributes);
            }
        }

        $leafCondition = $conditions;

        if($leafCondition->{'type'} !== self::CUSTOM_ATTRIBUTE_CONDITION_TYPE) {
            return null;
        }

        $conditionMatch = null;
        if(!isset($leafCondition->{'match'})) {
            $conditionMatch = self::EXACT_MATCH_TYPE;
        }

        if(!in_array($conditionMatch, $this->getMatchTypes())) {
            return null;
        }

        $evaluatorForMatch = $this->getEvaluatorByMatchType($conditionMatch);
        return $this->$evaluatorForMatch($leafCondition, $userAttributes);
    }

    public function isFinite($value)
    {
        if(is_numeric($value) ) {

            if(is_string($value) || is_nan($value) || is_infinite($value)) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function isValueValidForExactConditions($value)
    {
        if(is_string($value) || is_bool($value) || $this->isFinite($value)) {
            return true;
        }

        return false;
    }

    public function exactEvaluator($condition, $userAttributes)
    {
        $conditionName = $condition->{'name'};
        $conditionValue = $condition->{'value'};
        $conditionValueType = gettype($conditionValue);

        $userValue = isset($userAttributes[$conditionName]) ? $userAttributes[$conditionName]: null;
        $userValueType = gettype($userValue);

        if(!$this->isValueValidForExactConditions($userValue) ||
            !$this->isValueValidForExactConditions($conditionValue) ||
            $conditionValueType !== $userValueType) {
                return null;
            }

        return $conditionValue === $userValue;
    }

    public function existsEvaluator($condition, $userAttributes) 
    {
        $conditionName = $condition->{'name'};
        return isset($userAttributes[$conditionName]);
    }

    public function greaterThanEvaluator($condition, $userAttributes)
    {
        $conditionName = $condition->{'name'};
        $conditionValue = $condition->{'value'};
        $userValue = $userAttributes[$conditionName];

        if(!$this->isFinite($userValue) || !$this->isFinite($conditionValue)) {
            return null;
        }

        return $userValue > $conditionValue;
    }

    public function lessThanEvaluator($condition, $userAttributes)
    {
        $conditionName = $condition->{'name'};
        $conditionValue = $condition->{'value'};
        $userValue = $userAttributes[$conditionName];

        if(!$this->isFinite($userValue) || !$this->isFinite($conditionValue)) {
            return null;
        }

        return $userValue < $conditionValue;
    }

    public function substringEvaluator($condition, $userAttributes)
    {
        $conditionName = $condition->{'name'};
        $conditionValue = $condition->{'value'};
        $userValue = $userAttributes[$conditionName];

        if(!is_string($userValue) || !is_string($conditionValue)) {
            return null;
        }

        return strpos($userValue, $conditionValue) !== false;
    }
}
