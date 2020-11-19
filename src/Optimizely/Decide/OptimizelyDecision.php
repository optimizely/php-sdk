<?php
/**
 * Copyright 2020, Optimizely
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

namespace Optimizely\Decide;

class OptimizelyDecision
{
    private $variationKey;
    private $enabled;
    private $variables;
    private $ruleKey;
    private $flagKey;
    private $userContext;
    private $reasons;
    
    
    public function __construct(
        $variationKey = null,
        $enabled = null,
        $variables = null,
        $ruleKey = null,
        $flagKey = null,
        $userContext = null,
        $reasons = null
    ) {
        $this->variationKey = $variationKey;
        $this->enabled = $enabled === null ? false : $enabled;
        $this->variables = $variables === null ? [] : $variables;
        $this->ruleKey = $ruleKey;
        $this->flagKey = $flagKey;
        $this->userContext = $userContext;
        $this->reasons = $reasons === null ? [] : $reasons;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
