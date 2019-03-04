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
    const AUDIENCE_EVALUATION_RESULT = "Audience \"%s\" evaluated to %s.";
    const AUDIENCE_EVALUATION_RESULT_COMBINED = "Audiences for experiment \"%s\" collectively evaluated to %s.";
    const EVALUATING_AUDIENCES_COMBINED = "Evaluating audiences for experiment \"%s\": %s.";
    const EVALUATING_AUDIENCE = "Starting to evaluate audience \"%s\" with conditions: %s.";
    const INFINITE_ATTRIBUTE_VALUE = "Audience condition %s evaluated to UNKNOWN because the number value for user attribute \"%s\" is not in the range [-2^53, +2^53].";
    const MISSING_ATTRIBUTE_VALUE = "Audience condition %s evaluated to UNKNOWN because no value was passed for user attribute \"%s\".";
    const NULL_ATTRIBUTE_VALUE = "Audience condition %s evaluated to UNKNOWN because a null value was passed for user attribute \"%s\".";
    const UNEXPECTED_TYPE = "Audience condition %s evaluated to UNKNOWN because a value of type \"%s\" was passed for user attribute \"%s\".";

    const UNKNOWN_CONDITION_TYPE = "Audience condition %s uses an unknown condition type. You may need to upgrade to a newer release of the Optimizely SDK.";
    const UNKNOWN_CONDITION_VALUE = "Audience condition %s has an unsupported condition value. You may need to upgrade to a newer release of the Optimizely SDK.";
    const UNKNOWN_MATCH_TYPE = "Audience condition %s uses an unknown match type. You may need to upgrade to a newer release of the Optimizely SDK.";
}
