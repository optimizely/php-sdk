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

use Monolog\Logger;
use Optimizely\Config\DatafileProjectConfig;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Exceptions\InvalidDatafileVersionException;
use Optimizely\Logger\NoOpLogger;
use Optimizely\ProjectConfigManager\StaticProjectConfigManager;

class StaticProjectConfigManagerTest extends \PHPUnit_Framework_TestCase
{
    private $loggerMock;
    private $errorHandlerMock;

    protected function setUp() : void
    {
        // Mock Logger
        $this->loggerMock = $this->getMockBuilder(NoOpLogger::class)
            ->setMethods(array('log'))
            ->getMock();

        $this->errorHandlerMock = $this->getMockBuilder(NoOpErrorHandler::class)
        ->setMethods(array('handleError'))
        ->getMock();
    }

    public function testStaticProjectConfigManagerWithInvalidDatafile()
    {
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::ERROR, 'Provided "datafile" has invalid schema.');

        $configManager = new StaticProjectConfigManager('Invalid datafile', false, $this->loggerMock, $this->errorHandlerMock);
        $this->assertNull($configManager->getConfig());
    }

    public function testStaticProjectConfigManagerWithUnsupportedVersionDatafile()
    {
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::ERROR, 'This version of the PHP SDK does not support the given datafile version: 5.');

        $this->errorHandlerMock->expects($this->once())
            ->method('handleError')
            ->with(new InvalidDatafileVersionException('This version of the PHP SDK does not support the given datafile version: 5.'));

        $configManager = new StaticProjectConfigManager(UNSUPPORTED_DATAFILE, false, $this->loggerMock, $this->errorHandlerMock);
        $this->assertNull($configManager->getConfig());
    }

    public function testStaticProjectConfigManagerWithValidDatafile()
    {
        $config = new DatafileProjectConfig(DATAFILE, $this->loggerMock, $this->errorHandlerMock);
        $configManager = new StaticProjectConfigManager(DATAFILE, false, $this->loggerMock, $this->errorHandlerMock);
        $this->assertEquals($config, $configManager->getConfig());
    }
}
