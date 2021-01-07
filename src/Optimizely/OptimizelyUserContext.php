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

class OptimizelyUserContext implements \JsonSerializable
{
    private $optimizelyClient;
    private $userId;
    private $attributes;
    
    
    public function __construct(Optimizely $optimizelyClient, $userId, array $attributes = [])
    {
        $this->optimizelyClient = $optimizelyClient;
        $this->userId = $userId;
        $this->attributes = $attributes;
    }

    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function decide($key, array $options = [])
    {
        return $this->optimizelyClient->decide($this, $key, $options);
    }

    public function decideForKeys(array $keys, array $options = [])
    {
        return $this->optimizelyClient->decideForKeys($this, $keys, $options);
    }

    public function decideAll(array $options = [])
    {
        return $this->optimizelyClient->decideAll($this, $options);
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
