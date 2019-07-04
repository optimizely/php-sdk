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
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Monolog\Logger;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Exceptions\InvalidDatafileVersionException;
use Optimizely\Logger\NoOpLogger;
use Optimizely\ProjectConfig;
use Optimizely\ProjectConfigManager\HTTPProjectConfigManager;

class HTTPProjectConfigManagerTest extends \PHPUnit_Framework_TestCase
{
    private $loggerMock;
    private $errorHandlerMock;
    private $url;
    private $template;

    public function setUp()
    {
        // Mock Logger
        $this->loggerMock = $this->getMockBuilder(NoOpLogger::class)
            ->setMethods(array('log'))
            ->getMock();

        // Mock Error Handler
        $this->errorHandlerMock = $this->getMockBuilder(NoOpErrorHandler::class)
        ->setMethods(array('handleError'))
        ->getMock();

        $this->url = "https://cdn.optimizely.com/datafiles/QBw9gFM8oTn7ogY9ANCC1z.json";
        $this->template = "https://cdn.optimizely.com/datafiles/%s.json";
    }

    public function testConfigManagerRetrievesProjectConfigByURL()
    {
        $configManager = new HTTPProjectConfigManager(
            null,
            $this->url,
            null,
            true,
            null,
            false,
            $this->loggerMock,
            $this->errorHandlerMock
        );

        $config = $configManager->getConfig();
        $this->assertInstanceOf(ProjectConfig::class, $config);
    }

    public function testConfigManagerRetrievesProjectConfigBySDKKey()
    {
        $configManager = new HTTPProjectConfigManager(
            'QBw9gFM8oTn7ogY9ANCC1z',
            null,
            null,
            true,
            null,
            false,
            $this->loggerMock,
            $this->errorHandlerMock
        );

        $config = $configManager->getConfig();
        $this->assertInstanceOf(ProjectConfig::class, $config);
    }

    public function testConfigManagerRetrievesProjectConfigByFormat()
    {
        $configManager = new HTTPProjectConfigManager(
            'QBw9gFM8oTn7ogY9ANCC1z',
            null,
            $this->template,
            true,
            null,
            false,
            $this->loggerMock,
            $this->errorHandlerMock
        );

        $config = $configManager->getConfig();
        $this->assertInstanceOf(ProjectConfig::class, $config);
    }

    /**
    * @expectedException Exception
    *
    */
    public function testConfigManagerThrowsErrorWhenBothSDKKeyAndURLNotProvided()
    {
        $this->errorHandlerMock->expects($this->once())
            ->method('handleError')
            ->with(new Exception("One of the SDK key or URL must be provided."));

        $configManager = new HTTPProjectConfigManager(
            null,
            null,
            null,
            true,
            DATAFILE,
            false,
            $this->loggerMock,
            $this->errorHandlerMock
        );
    }

    public function testConfigIsNullWhenNoDatafileProvidedAndfetchOnInitIsFalse()
    {
        $configManager = new HTTPProjectConfigManager(
            null,
            $this->url,
            null,
            false,
            null,
            false,
            $this->loggerMock,
            $this->errorHandlerMock
        );

        $this->assertNull($configManager->getConfig());
    }

    public function testConfigNotNullWhenDatafileProvidedAndfetchOnInitFalse()
    {
        $configManager = new HTTPProjectConfigManager(
            null,
            $this->url,
            null,
            false,
            DATAFILE,
            false,
            $this->loggerMock,
            $this->errorHandlerMock
        );

        $config = ProjectConfig::createProjectConfigFromDatafile(
            DATAFILE,
            false,
            $this->loggerMock,
            $this->errorHandlerMock
        );

        $this->assertEquals($config, $configManager->getConfig());
    }

    public function testGetConfigReturnsProvidedDataFileWhenFetchReturnsNullWithFetchOnInitTrue()
    {
        $configManagerMock = $this->getMockBuilder(HTTPProjectConfigManager::class)
            ->setConstructorArgs(array(null, $this->url, null, true, DATAFILE, true,
                                    $this->loggerMock, $this->errorHandlerMock))
            ->setMethods(array('fetch'))
            ->getMock();

        $config = ProjectConfig::createProjectConfigFromDatafile(
            DATAFILE,
            false,
            $this->loggerMock,
            $this->errorHandlerMock
        );

        $this->assertEquals($config, $configManagerMock->getConfig());
    }

    public function testGetConfigReturnsProvidedDatafileWhenHttpClientReturnsInvalidFile()
    {
        $configManager = new HTTPProjectConfigManager(
            null,
            $this->url,
            null,
            false,
            DATAFILE,
            false,
            $this->loggerMock,
            $this->errorHandlerMock
        );

        $expectedOptions = [
            'headers' => null,
            'timeout' => 10,
            'connect_timeout' => 10
        ];

        $mock = new MockHandler([
            new Response(307, [], 'Invalid Datafile')
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $httpClient = new \ReflectionProperty(HTTPProjectConfigManager::class, 'httpClient');
        $httpClient->setAccessible(true);
        $httpClient->setValue($configManager, $client);

        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::ERROR, sprintf("Unexpected response when trying to fetch datafile, status code: 307"));

        $configManager->fetch();

        $config = ProjectConfig::createProjectConfigFromDatafile(
            DATAFILE,
            false,
            $this->loggerMock,
            $this->errorHandlerMock
        );

        $this->assertEquals($config, $configManager->getConfig());
    }

    public function testGetConfigReturnsUpdatedDatafileWhenHttpClientReturnsValidDatafile()
    {
        $configManager = new HTTPProjectConfigManager(
            null,
            $this->url,
            null,
            true,
            DATAFILE,
            false,
            $this->loggerMock,
            $this->errorHandlerMock
        );

        $config = ProjectConfig::createProjectConfigFromDatafile(
            DATAFILE,
            false,
            $this->loggerMock,
            $this->errorHandlerMock
        );

         $this->assertInstanceOf(ProjectConfig::class, $configManager->getConfig());
        $this->assertNotEquals($config, $configManager->getConfig());
    }

    public function testConfigNotUpdatedWhenDatafileIsNotModified()
    {
        $configManager = new HTTPProjectConfigManager(
            null,
            $this->url,
            null,
            true,
            null,
            false,
            $this->loggerMock,
            $this->errorHandlerMock
        );

        $lastModified = new \ReflectionProperty(HTTPProjectConfigManager::class, '_lastModifiedSince');
        $lastModified->setAccessible(true);

        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::DEBUG, sprintf("Not updating ProjectConfig as datafile has not updated since %s", $lastModified->getValue($configManager)));

        $config = $configManager->getConfig();
        $configManager->fetch();
        $configAfterFetch = $configManager->getConfig();

        $this->assertEquals($config, $configAfterFetch);
    }
}
