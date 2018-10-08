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


    public static function getMatchTypes()
    {
        return array(EXACT_MATCH_TYPE, EXISTS_MATCH_TYPE, GREATER_THAN_MATCH_TYPE,
         LESS_THAN_MATCH_TYPE, SUBSTRING_MATCH_TYPE);
    }


    public static function getEvaluatorByMatchType($matchType)
    {
        $evaluatorsByMatchType = array();

        $evaluatorsByMatchType[EXACT_MATCH_TYPE] = 'exactEvaluator';
        $evaluatorsByMatchType[EXISTS_MATCH_TYPE] = 'existsEvaluator';
        $evaluatorsByMatchType[GREATER_THAN_MATCH_TYPE] = 'greaterThanEvaluator';
        $evaluatorsByMatchType[LESS_THAN_MATCH_TYPE] = 'lessThanEvaluator';
        $evaluatorsByMatchType[SUBSTRING_MATCH_TYPE] = 'substringEvaluator';

        return $evaluatorsByMatchType[$matchType];
    }

    /**
     * @param $conditions array Audience conditions list.
     * @param $userAttributes array Associative array of user attributes to values.
     *
     * @return boolean True if all conditions evaluate to True.
     */
    private function andEvaluator($conditions, $userAttributes)
    {
        foreach ($conditions as $condition) {
            $result = $this->evaluate($condition, $userAttributes);
            if (!$result) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $conditions array Audience conditions list.
     * @param $userAttributes array Associative array of user attributes to values.
     *
     * @return boolean True if any one of the conditions evaluate to True.
     */
    private function orEvaluator($conditions, $userAttributes)
    {
        foreach ($conditions as $condition) {
            $result = $this->evaluate($condition, $userAttributes);
            if ($result) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $condition array Audience conditions list consisting of single condition.
     * @param $userAttributes array Associative array of user attributes to values.
     *
     * @return boolean True if the condition evaluates to False.
     */
    private function notEvaluator($condition, $userAttributes)
    {
        if (count($condition) != 1) {
            return false;
        }

        return !$this->evaluate($condition[0], $userAttributes);
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
            switch ($conditions[0]) {
            case self::AND_OPERATOR:
                array_shift($conditions);
                return $this->andEvaluator($conditions, $userAttributes);
            case self::OR_OPERATOR:
                array_shift($conditions);
                return $this->orEvaluator($conditions, $userAttributes);
            case self::NOT_OPERATOR:
                array_shift($conditions);
                return $this->notEvaluator($conditions, $userAttributes);
            default:
                return false;
            }
        }

        $conditionName = $conditions->{'name'};
        if (!isset($userAttributes[$conditionName])) {
            return false;
        }
        return $userAttributes[$conditionName] == $conditions->{'value'};
    }

    private function isFinite($value)
    {
        if(is_numeric($value) ) {

            if(is_string($value) || is_nan($value) || is_infinite($value)) {
                return false;
            }

            return true;
        }

        return false;
    }

    private function isValueValidForExactConditions($value)
    {
        if(is_string($value) || is_bool($value) || isFinite($value)) {
            return true;
        }

        return false;
    }

    public function exactEvaluator($condition, $userAttributes)
    {
        $conditionName = $conditions->{'name'};
        $conditionValue = $conditions->{'value'};
        $conditionValueType = gettype($conditionValue);

        $userValue = $userAttributes[$conditionName];
        $userValueType = gettype($userValue);

        if(!isValueValidForExactConditions($userValue) ||
            !isValueValidForExactConditions($conditionValue)) ||
            $conditionValueType !== $userValueType) {
                return null;
            }

        return $conditionValue === $userValue;
    }

    public function existsEvaluator($condition, $userAttributes) 
    {
        $conditionName = $conditions->{'name'};
        return isset($userAttributes[$conditionName]);
    }

    public function greaterThanEvaluator($condition, $userAttributes)
    {
        $conditionName = $conditions->{'name'};
        $conditionValue = $conditions->{'value'};
        $userValue = $userAttributes[$conditionName];

        if(!isFinite($userValue) || !isFinite($conditionValue)) {
            return null;
        }

        return $userValue > $conditionValue;
    }

    public function lessThanEvaluator($condition, $userAttributes)
    {
        $conditionName = $conditions->{'name'};
        $conditionValue = $conditions->{'value'};
        $userValue = $userAttributes[$conditionName];

        if(!isFinite($userValue) || !isFinite($conditionValue)) {
            return null;
        }

        return $userValue < $conditionValue;
    }

    public function substringEvaluator($condition, $userAttributes)
    {
        $conditionName = $conditions->{'name'};
        $conditionValue = $conditions->{'value'};
        $userValue = $userAttributes[$conditionName];

        if(!is_string($userValue) || !is_string($conditionValue)) {
            return null;
        }

        return strpos($userValue, $conditionValue) !== false;
    }
}
