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
namespace Optimizely\Tests;

use Exception;
use Monolog\Logger;
use Optimizely\Config\DatafileProjectConfig;
use Optimizely\DecisionService\DecisionService;
use Optimizely\DecisionService\FeatureDecision;
use Optimizely\Entity\FeatureVariable;
use Optimizely\Enums\DecisionNotificationTypes;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Event\LogEvent;
use Optimizely\Exceptions\InvalidAttributeException;
use Optimizely\Exceptions\InvalidDatafileVersionException;
use Optimizely\Exceptions\InvalidEventTagException;
use Optimizely\Exceptions\InvalidInputException;
use Optimizely\Logger\NoOpLogger;
use Optimizely\Notification\NotificationCenter;
use Optimizely\Notification\NotificationType;
use Optimizely\ProjectConfigManager\HTTPProjectConfigManager;
use Optimizely\ProjectConfigManager\StaticProjectConfigManager;
use TypeError;
use Optimizely\ErrorHandler\DefaultErrorHandler;
use Optimizely\Event\Builder\EventBuilder;
use Optimizely\Logger\DefaultLogger;
use Optimizely\Optimizely;

use Optimizely\OptimizelyConfig\OptimizelyConfigService;

class OptimizelyConfigServiceTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->datafile = DATAFILE;
        $this->loggerMock = $this->getMockBuilder(NoOpLogger::class)
            ->setMethods(array('log'))
            ->getMock();
    }

    public function testOptimizelyConfigService()
    {
        $this->projectConfig = new DatafileProjectConfig(
            $this->datafile, $this->loggerMock, new NoOpErrorHandler()
        );

        $optService = new OptimizelyConfigService($this->projectConfig);
        $optConfig = $optService->getConfig();

        $result = $optConfig;
        print(json_encode($result));

    }
}