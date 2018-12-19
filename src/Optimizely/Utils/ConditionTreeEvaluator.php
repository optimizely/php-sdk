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
    const AND_OPERATOR = 'and';
    const OR_OPERATOR = 'or';
    const NOT_OPERATOR = 'not';

    /**
     * Returns an array of supported operators.
     * 
     * @return array List of operators.
     */
    protected function getOperators()
    {
        return array(self::AND_OPERATOR, self::OR_OPERATOR, self::NOT_OPERATOR);
    }

    /**
     * Returns corresponding evaluator method name for the given operator.
     * 
     * @param  mixed  $operator  Operator to get relevant evaluator method. 
     * 
     * @return string Corresponding method to the given operator.
     */
    protected function getEvaluatorByOperatorType($operator)
    {
        $evaluatorsByOperator = array();
        $evaluatorsByOperator[self::AND_OPERATOR] = 'andEvaluator';
        $evaluatorsByOperator[self::OR_OPERATOR] = 'orEvaluator';
        $evaluatorsByOperator[self::NOT_OPERATOR] = 'notEvaluator';

        return $evaluatorsByOperator[$operator];
    }

    /**
     * Evaluates an array of conditions as if the evaluator had been applied
     * to each entry and the results AND-ed together.
     * 
     * @param array     $conditions  Audience conditions list.
     * @param callable  $leafEvaluator Method to evaluate leaf condition. 
     *
     * @return null|boolean True if all the operands evaluate to true.
     *                      False if a single operand evaluates to false.
     *                      Null if conditions couldn't be evaluated.
     */
    protected function andEvaluator(array $conditions, callable $leafEvaluator)
    {
        $sawNullResult = false;
        foreach ($conditions as $condition) {
            $result = $this->evaluate($condition, $leafEvaluator);
            
            if($result === false) {
                return false;
            }

            if($result === null) {
                $sawNullResult = true;
            }
        }

        return $sawNullResult ? null : true;
    }

    /**
     * Evaluates an array of conditions as if the evaluator had been applied
     * to each entry and the results OR-ed together.
     * 
     * @param array     $conditions  Audience conditions list.
     * @param callable  $leafEvaluator Method to evaluate leaf condition. 
     *
     * @return null|boolean True if any operand evaluates to true.
     *                    False if all operands evaluate to false.
     *                    Null if conditions couldn't be evaluated.
     */
    protected function orEvaluator(array $conditions, callable $leafEvaluator)
    {
        $sawNullResult = false;
        foreach ($conditions as $condition) {
            $result = $this->evaluate($condition, $leafEvaluator);

            if($result === true) {
                return true;
            }

            if($result === null) {
                $sawNullResult = true;
            }
        }

        return $sawNullResult ? null : false;
    }

    /**
     * Evaluates an array of conditions as if the evaluator had been applied
     * to a single entry and NOT was applied to the result.
     * 
     * @param array     $conditions  Audience conditions list.
     * @param callable  $leafEvaluator Method to evaluate leaf condition. 
     *
     * @return null|boolean True if the operand evaluates to false.
     *                      False if the operand evaluates to true.
     *                      Null if conditions is empty or couldn't be evaluated.
     */
    protected function notEvaluator(array $condition, callable $leafEvaluator)
    {
        if (empty($condition)) {
            return null;
        }

        $result = $this->evaluate($condition[0], $leafEvaluator);
        return $result === null ? null: !$result;
    }

    /**
     * Function to evaluate audience conditions against user's attributes.
     *
     * @param array     $conditions Nested array of and/or/not conditions representing the audience conditions.
     * @param callable  $leafEvaluator Method to evaluate leaf condition. 
     *
     * @return null|boolean Result of evaluating the conditions using the operator rules.
     *                      and the leaf evaluator. Null if conditions couldn't be evaluated.
     */
    public function evaluate($conditions, callable $leafEvaluator)
    {
        // When parsing audiences tree the leaf node is a string representing an audience ID.
        // When parsing conditions of a single audience the leaf node is an associative array with all keys of type string. 
        if (is_string($conditions) || Validator::doesArrayContainOnlyStringKeys($conditions)) {
            
            $leafCondition = $conditions;
            return $leafEvaluator($leafCondition);
        }
            
        if(in_array($conditions[0], $this->getOperators())) {
            $operator = array_shift($conditions);
        } else {
            $operator = self::OR_OPERATOR;
        }

        $evaluatorFunc = $this->getEvaluatorByOperatorType($operator);
        return $this->{$evaluatorFunc}($conditions, $leafEvaluator);
    }
}
