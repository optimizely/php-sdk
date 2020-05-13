<?php
/**
 * Copyright 2019-2020 Optimizely
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

class DecisionNotificationTypes
{
    const AB_TEST = "ab-test";
    const FEATURE = "feature";
    const FEATURE_TEST = "feature-test";
    const FEATURE_VARIABLE = "feature-variable";
    const ALL_FEATURE_VARIABLES = "all-feature-variables";
}
