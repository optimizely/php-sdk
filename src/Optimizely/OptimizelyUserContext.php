<?php
/**
 * Copyright 2021, Optimizely
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

namespace Optimizely;

use Optimizely\Logger\LoggerInterface;
use Optimizely\Logger\NoOpLogger;

class OptimizelyUserContext implements \JsonSerializable
{
    private $optimizelyClient;
    private $userId;
    private $attributes;
    private $forcedDecisions;

    public function __construct(Optimizely $optimizelyClient, $userId, array $attributes = [])
    {
        $this->optimizelyClient = $optimizelyClient;
        $this->userId = $userId;
        $this->attributes = $attributes;
    }

    public function setForcedDecision($flagKey, $variationKey, $ruleKey = null)
    {
        $index = $this->findExisitingRuleAndFlagKey($flagKey, $ruleKey);
        if ($index != -1) {
            $this->forcedDecisions[$index]->variationKey = $variationKey;
        } else {
            array_push($this->forcedDecisions, new ForcedDecision($flagKey, $ruleKey, $variationKey));
        }
        return true;
    }

    public function getForcedDecision($flagKey, $ruleKey = null)
    {
        return $this->findForcedDecision($flagKey, $ruleKey);
    }

    public function findValidatedForcedDecision($flagKey, $ruleKey, array $options = [])
    {
        $decideReasons = [];
        $variationKey = $this->findForcedDecision($flagKey, $ruleKey);
        if ($variationKey != null) {
            $variation = ($this->optimizelyClient)->getFlagVariationByKey($flagKey, $variationKey);
            $message = sprintf('Variation "%s" is mapped to "%s" and user "%s" in the forced decision map.', $variationKey, $flagKey, $this->userId);

            $decideReasons[] = $message;
            return [$variation, $decideReasons];
        } else {
            $message = sprintf('Invalid variation is mapped to "%s" and user "%s" in the forced decision map.', $flagKey, $this->userId);

            $decideReasons[] = $message;
        }
        return [null, $decideReasons];
    }
    private function findExistingRuleAndFlagKey($flagKey, $ruleKey)
    {
        if ($this->forcedDecisions) {
            for ($index = 0; $index < count($this->forcedDecisions); $index++) {
                if ($this->forcedDecisions[$index]->getFlagKey() == $flagKey &&  $this->forcedDecisions[$index]->getRuleKey() == $ruleKey) {
                    return $index;
                }
            }
        }
        return -1;
    }

    private function findForcedDecision($flagKey, $ruleKey)
    {
        if ($this->forcedDecisions && count($this->forcedDecisions) == 0) {
            return null;
        }

        $index = $this->findExistingRuleAndFlagKey($flagKey, $ruleKey);

        if ($index != -1) {
            return $this->forcedDecisions[$index]->getVariationKey();
        }

        return null;
    }
    protected function copy()
    {
        return new OptimizelyUserContext($this->optimizelyClient, $this->userId, $this->attributes);
    }

    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function decide($key, array $options = [])
    {
        return $this->optimizelyClient->decide($this->copy(), $key, $options);
    }

    public function decideForKeys(array $keys, array $options = [])
    {
        return $this->optimizelyClient->decideForKeys($this->copy(), $keys, $options);
    }

    public function decideAll(array $options = [])
    {
        return $this->optimizelyClient->decideAll($this->copy(), $options);
    }

    public function trackEvent($eventKey, array $eventTags = [])
    {
        $eventTags = $eventTags ?: null;
        return $this->optimizelyClient->track($eventKey, $this->userId, $this->attributes, $eventTags);
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getOptimizely()
    {
        return $this->optimizelyClient;
    }

    public function jsonSerialize()
    {
        return [
            'userId' => $this->userId,
            'attributes' => $this->attributes
        ];
    }
}
class ForcedDecision
{
    private $flagKey;
    private $ruleKey;
    public $variationKey;

    public function __construct($flagKey, $ruleKey, $variationKey)
    {
        $this->flagKey = $flagKey;
        $this->ruleKey = $ruleKey;
        $this->variationKey = $variationKey;
    }

    /**
     * @return mixed
     */
    public function getFlagKey()
    {
        return $this->flagKey;
    }

    /**
     * @return mixed
     */
    public function getRuleKey()
    {
        return $this->ruleKey;
    }

    /**
     * @return mixed
     */
    public function getVariationKey()
    {
        return $this->variationKey;
    }
}
