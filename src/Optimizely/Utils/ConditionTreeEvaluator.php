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

class ConditionTreeEvaluator
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

    protected function getDefaultOperators()
    {
        return array(self::AND_OPERATOR, self::OR_OPERATOR, self::NOT_OPERATOR);
    }

    protected function getEvaluatorByOperatorType($operator)
    {
        $evaluatorsByOperator = [];
        $evaluatorsByOperator[self::AND_OPERATOR] = 'andEvaluator';
        $evaluatorsByOperator[self::OR_OPERATOR] = 'orEvaluator';
        $evaluatorsByOperator[self::NOT_OPERATOR] = 'notEvaluator';

        return $evaluatorsByOperator[$operator];
    }

    /**
     * Evaluates an array of conditions as if the evaluator had been applied
     * to each entry and the results AND-ed together.
     * 
     * @param $conditions array Audience conditions list.
     * @param $userAttributes array Associative array of user attributes to values.
     *
     * @return null|boolean true/false if the user attributes match/don't match the given conditions, null if the user attributes and conditions can't be evaluated. 
     * 
     */
    public function andEvaluator($conditions, $leafEvaluator)
    {
        $sawNullResult = false;
        foreach ($conditions as $condition) {
            $result = $this->evaluate($condition, $leafEvaluator);
            
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
     * Evaluates an array of conditions as if the evaluator had been applied
     * to each entry and the results OR-ed together.
     * 
     * @param $conditions array Audience conditions list.
     * @param $userAttributes array Associative array of user attributes to values.
     *
     * @return null|boolean true/false if the user attributes match/don't match the given conditions, null if the user attributes and conditions can't be evaluated. 
     */
    public function orEvaluator($conditions, $leafEvaluator)
    {
        $sawNullResult = false;
        foreach ($conditions as $condition) {
            $result = $this->evaluate($condition, $leafEvaluator);

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
     * Evaluates an array of conditions as if the evaluator had been applied
     * to a single entry and NOT was applied to the result.
     * 
     * @param $condition array Audience conditions list consisting of single condition.
     * @param $userAttributes array Associative array of user attributes to values.
     *
     * @return null|boolean true/false if the user attributes match/don't match the given conditions, null if the user attributes and conditions can't be evaluated.
     */
    public function notEvaluator($condition, $leafEvaluator)
    {
        if (empty($condition)) {
            return null;
        }

        $result = $this->evaluate($condition[0], $leafEvaluator);
        return is_null($result) ? null: !$result;
    }

    /**
     * Function to evaluate audience conditions against user's attributes.
     *
     * @param $conditions array Nested array of and/or/not conditions representing the audience conditions.
     * @param $userAttributes array Associative array of user attributes to values.
     *
     * @return null|boolean true/false if the given user attributes match/don't match the given conditions, null if the given user attributes and conditions can't be evaluated
     */
    public function evaluate($conditions, $leafEvaluator)
    {
        if (is_array($conditions)) {

            if(in_array($conditions[0], $this->getDefaultOperators()) {
                $operator = array_shift($conditions);
            } else {
                $operator = self::OR_OPERATOR;
            }

            $evaluatorFunc = $this->getEvaluatorByOperatorType($operator);
            return $evaluatorFunc($conditions, $leafEvaluator);
        }

        $leafCondition = $conditions;
        return $leafEvaluator($leafCondition);
    }

}
