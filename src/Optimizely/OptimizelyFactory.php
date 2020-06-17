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
namespace Optimizely;

use Optimizely\Optimizely;
use Optimizely\ProjectConfigManager\HTTPProjectConfigManager;

/**
 * Class OptimizelyFactory
 *
 * @package Optimizely
 */
class OptimizelyFactory
{
    public static function createDefaultInstance(
        $sdkKey,
        $fallbackDatafile = null,
        $datafileAccessToken = null
    ) {
        $configManager = new HTTPProjectConfigManager(
            $sdkKey,
            null,
            null,
            null,
            $fallbackDatafile,
            null,
            null,
            null,
            null,
            $datafileAccessToken
        );

        return new Optimizely(
            null,
            null,
            null,
            null,
            null,
            null,
            $configManager,
            null,
            null
        );
    }
}
