<?php
/**
 * Copyright 2019, Optimizely
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

namespace Optimizely\Enums;

class AudienceEvaluationLogs
{
    const AUDIENCE_EVALUATION_RESULT = "Audience \"%s\" evaluated as %s.";
    const AUDIENCE_EVALUATION_RESULT_COMBINED = "Audiences for experiment \"%s\" collectively evaluated as %s.";
    const EVALUATING_AUDIENCES = "Evaluating audiences for experiment \"%s\": \"%s\".";
    const EVALUATING_AUDIENCE_WITH_CONDITIONS = "Starting to evaluate audience \"%s\" with conditions: \"%s\".";
    const MISMATCH_TYPE = "Audience condition %s evaluated as UNKNOWN because the value for user attribute \"%s\" is \"%s\" while expected is \"%s\".";
    const MISSING_ATTRIBUTE_VALUE = "Audience condition %s evaluated as UNKNOWN because no value was passed for user attribute \"%s\".";
    const NO_AUDIENCE_ATTACHED = "No Audience attached to experiment \"%s\". Evaluated as True.";
    const UNEXPECTED_TYPE = "Audience condition %s evaluated as UNKNOWN because the value for user attribute \"%s\" is inapplicable: \"%s\".";
    const UNKNOWN_CONDITION_TYPE = "Audience condition \"%s\" has an unknown condition type.";
    const UNKNOWN_MATCH_TYPE = "Audience condition \"%s\" uses an unknown match type.";
    const USER_ATTRIBUTES = "User attributes: \"%s\".";
}
