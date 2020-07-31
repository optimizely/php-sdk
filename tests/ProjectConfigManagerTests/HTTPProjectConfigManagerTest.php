<?php
/**
 * Copyright 2019-2020, Optimizely
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
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Monolog\Logger;
use Optimizely\Config\DatafileProjectConfig;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Exceptions\InvalidDatafileVersionException;
use Optimizely\Logger\NoOpLogger;
use Optimizely\Notification\NotificationCenter;
use Optimizely\Notification\NotificationType;
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

        // Mock Notification Center
        $this->notificationCenterMock = $this->getMockBuilder(NotificationCenter::class)
            ->setConstructorArgs(array($this->loggerMock, $this->errorHandlerMock))
            ->setMethods(array('sendNotifications'))
            ->getMock();

        $this->url = "https://cdn.optimizely.com/datafiles/QBw9gFM8oTn7ogY9ANCC1z.json";
        $this->template = "https://cdn.optimizely.com/datafiles/%s.json";
    }

    public function testConfigManagerRetrievesProjectConfigByURL()
    {
        $configManagerMock = $this->getMockBuilder(HTTPProjectConfigManagerTester::class)
            ->setConstructorArgs(array(null, $this->url, null, true, null, false,
                                    $this->loggerMock, $this->errorHandlerMock))
            ->setMethods(array('fetchDatafile'))
            ->getMock();

        $configManagerMock->expects($this->any())
            ->method('fetchDatafile')
            ->willReturn(DATAFILE);

        $configManagerMock->handleResponse(DATAFILE);
        $config = $configManagerMock->getConfig();

        $this->assertInstanceOf(DatafileProjectConfig::class, $config);
    }

    public function testConfigManagerRetrievesProjectConfigBySDKKey()
    {
        $configManagerMock = $this->getMockBuilder(HTTPProjectConfigManagerTester::class)
            ->setConstructorArgs(array('QBw9gFM8oTn7ogY9ANCC1z', null, null, true, null, false, null, null))
            ->setMethods(array('fetchDatafile'))
            ->getMock();

        $configManagerMock->expects($this->any())
            ->method('fetchDatafile')
            ->willReturn(DATAFILE);

        $configManagerMock->handleResponse(DATAFILE);
        $config = $configManagerMock->getConfig();

        $this->assertInstanceOf(DatafileProjectConfig::class, $config);
    }

    public function testConfigManagerRetrievesProjectConfigByFormat()
    {
        $configManagerMock = $this->getMockBuilder(HTTPProjectConfigManagerTester::class)
             ->setConstructorArgs(array('QBw9gFM8oTn7ogY9ANCC1z', null, $this->template, true, null, false,
                                     $this->loggerMock, $this->errorHandlerMock))
             ->setMethods(array('fetchDatafile'))
             ->getMock();

         $configManagerMock->expects($this->any())
             ->method('fetchDatafile')
             ->willReturn(DATAFILE);

         $configManagerMock->handleResponse(DATAFILE);
         $config = $configManagerMock->getConfig();

         $this->assertInstanceOf(DatafileProjectConfig::class, $config);
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

        $configManagerMock = $this->getMockBuilder(HTTPProjectConfigManager::class)
             ->setConstructorArgs(array(null, null, null, true, DATAFILE, false,
                                     $this->loggerMock, $this->errorHandlerMock))
             ->setMethods(array('fetch'))
             ->getMock();
    }

    public function testConfigIsNullWhenNoDatafileProvidedAndfetchOnInitIsFalse()
    {
        $configManagerMock = $this->getMockBuilder(HTTPProjectConfigManager::class)
          ->setConstructorArgs(array(null, $this->url, null, false, null, false,
                                  $this->loggerMock, $this->errorHandlerMock))
          ->setMethods(array('fetch'))
          ->getMock();

        $this->assertNull($configManagerMock->getConfig());
    }

    public function testConfigNotNullWhenDatafileProvidedAndfetchOnInitFalse()
    {
        $configManagerMock = $this->getMockBuilder(HTTPProjectConfigManager::class)
           ->setConstructorArgs(array(null, $this->url, null, false, DATAFILE, true,
                                   $this->loggerMock, $this->errorHandlerMock))
           ->setMethods(array('fetch'))
           ->getMock();

        $config = DatafileProjectConfig::createProjectConfigFromDatafile(
            DATAFILE,
            false,
            $this->loggerMock,
            $this->errorHandlerMock
        );

        $this->assertEquals($config, $configManagerMock->getConfig());
    }

    public function testGetConfigReturnsProvidedDataFileWhenFetchReturnsNullWithFetchOnInitTrue()
    {
        $configManagerMock = $this->getMockBuilder(HTTPProjectConfigManager::class)
            ->setConstructorArgs(array(null, $this->url, null, true, DATAFILE, true,
                                    $this->loggerMock, $this->errorHandlerMock))
            ->setMethods(array('fetch'))
            ->getMock();

        $config = DatafileProjectConfig::createProjectConfigFromDatafile(
            DATAFILE,
            false,
            $this->loggerMock,
            $this->errorHandlerMock
        );

        $this->assertEquals($config, $configManagerMock->getConfig());
    }

    public function testGetConfigReturnsNullWhenFetchedDatafileIsNotJson()
    {
        $configManagerMock = $this->getMockBuilder(HTTPProjectConfigManager::class)
            ->setConstructorArgs(array(null, $this->url, null, true, null, false,
                                    $this->loggerMock, $this->errorHandlerMock))
            ->setMethods(array('fetchDatafile'))
            ->getMock();

        $configManagerMock->expects($this->any())
            ->method('fetchDatafile')
            ->willReturn("Dracarys");

        $this->assertNull($configManagerMock->getConfig());
    }

    public function testGetConfigReturnsProvidedDatafileWhenHttpClientReturnsInvalidFile()
    {
        $configManagerMock = $this->getMockBuilder(HTTPProjectConfigManager::class)
            ->setConstructorArgs(array(null, $this->url, null, false, DATAFILE, false,
                                    $this->loggerMock, $this->errorHandlerMock))
            ->setMethods(array('handleResponse'))
            ->getMock();

        $mock = new MockHandler([
            new Response(200, [], 'Invalid Datafile')
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $httpClient = new \ReflectionProperty(HTTPProjectConfigManager::class, 'httpClient');
        $httpClient->setAccessible(true);
        $httpClient->setValue($configManagerMock, $client);

        $configManagerMock->fetch();

        $config = DatafileProjectConfig::createProjectConfigFromDatafile(
            DATAFILE,
            false,
            $this->loggerMock,
            $this->errorHandlerMock
        );

        $this->assertEquals($config, $configManagerMock->getConfig());
    }

    public function testGetConfigReturnsProvidedDatafileWhenHttpClientReturnsUnhandledStatusCode()
    {
        $configManagerMock = $this->getMockBuilder(HTTPProjectConfigManager::class)
            ->setConstructorArgs(array(null, $this->url, null, false, DATAFILE, false,
                                    $this->loggerMock, $this->errorHandlerMock))
            ->setMethods(array('handleResponse'))
            ->getMock();

        $mock = new MockHandler([
            new Response(307, [], '')
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $httpClient = new \ReflectionProperty(HTTPProjectConfigManager::class, 'httpClient');
        $httpClient->setAccessible(true);
        $httpClient->setValue($configManagerMock, $client);

        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::ERROR, sprintf("Unexpected response when trying to fetch datafile, status code: 307. Please check your SDK key and/or datafile access token."));

        $configManagerMock->fetch();

        $config = DatafileProjectConfig::createProjectConfigFromDatafile(
            DATAFILE,
            false,
            $this->loggerMock,
            $this->errorHandlerMock
        );

        $this->assertEquals($config, $configManagerMock->getConfig());
    }

    public function testGetConfigReturnsProvidedDatafileWhenHttpClientThrows403Error()
    {
        $configManagerMock = $this->getMockBuilder(HTTPProjectConfigManager::class)
            ->setConstructorArgs(array(null, $this->url, null, false, DATAFILE, false,
                                    $this->loggerMock, $this->errorHandlerMock))
            ->setMethods(array('handleResponse'))
            ->getMock();

        $mock = new MockHandler([
            new Response(403, [], '')
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $httpClient = new \ReflectionProperty(HTTPProjectConfigManager::class, 'httpClient');
        $httpClient->setAccessible(true);
        $httpClient->setValue($configManagerMock, $client);

        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::ERROR, sprintf("Unexpected response when trying to fetch datafile, status code: 403. Please check your SDK key and/or datafile access token."));

        $configManagerMock->fetch();

        $config = DatafileProjectConfig::createProjectConfigFromDatafile(
            DATAFILE,
            false,
            $this->loggerMock,
            $this->errorHandlerMock
        );

        $this->assertEquals($config, $configManagerMock->getConfig());
    }

    public function testConfigNotUpdatedWhenDatafileIsNotModified()
    {
        $configManagerMock = $this->getMockBuilder(HTTPProjectConfigManager::class)
            ->setConstructorArgs(array(null, $this->url, null, false, null, false,
                                    $this->loggerMock, $this->errorHandlerMock))
            ->setMethods(array('handleResponse'))
            ->getMock();

        $mock = new MockHandler([
            new Response(304, [], DATAFILE)
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $httpClient = new \ReflectionProperty(HTTPProjectConfigManager::class, 'httpClient');
        $httpClient->setAccessible(true);
        $httpClient->setValue($configManagerMock, $client);

        $lastModified = new \ReflectionProperty(HTTPProjectConfigManager::class, '_lastModifiedSince');
        $lastModified->setAccessible(true);

        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::DEBUG, sprintf("Not updating ProjectConfig as datafile has not updated since %s", $lastModified->getValue($configManagerMock)));

        $config = $configManagerMock->getConfig();

        $configManagerMock->fetch();

        $configAfterFetch = $configManagerMock->getConfig();

        $this->assertEquals($config, $configAfterFetch);
    }

    public function testHandleResponseCallsConfigUpdateListenerWhenProjectConfigIsUpdated()
    {
        $configManagerMock = $this->getMockBuilder(HTTPProjectConfigManagerTester::class)
            ->setConstructorArgs(array(null, $this->url, null, true, DATAFILE_WITH_TYPED_AUDIENCES, false,
                                     $this->loggerMock, $this->errorHandlerMock, $this->notificationCenterMock))
            ->setMethods(array('fetchDatafile'))
            ->getMock();

        $configManagerMock->expects($this->any())
            ->method('fetchDatafile')
            ->willReturn(DATAFILE);

        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(Logger::DEBUG, 'Received new datafile and updated config. Old revision number: "3". New revision number: "15".');

        $this->notificationCenterMock->expects($this->once())
            ->method('sendNotifications')
            ->with(NotificationType::OPTIMIZELY_CONFIG_UPDATE);

        $configManagerMock->handleResponse(DATAFILE);

        $config = DatafileProjectConfig::createProjectConfigFromDatafile(
            DATAFILE_WITH_TYPED_AUDIENCES,
            false,
            $this->loggerMock,
            $this->errorHandlerMock
        );

        $this->assertInstanceOf(DatafileProjectConfig::class, $configManagerMock->getConfig());
        $this->assertNotEquals($config, $configManagerMock->getConfig());
    }

    public function testGetUrlReturnsURLWhenProvidedURLIsNonEmptyString()
    {
        $configManagerMock = $this->getMockBuilder(HTTPProjectConfigManagerTester::class)
            ->setConstructorArgs(array(null, $this->url, null, true, DATAFILE_WITH_TYPED_AUDIENCES, false,
                                     $this->loggerMock, $this->errorHandlerMock))
            ->setMethods(array('fetch'))
            ->getMock();

        $this->errorHandlerMock->expects($this->never())
           ->method('handleError');

        $url = $configManagerMock->getUrl(null, $this->url, null);

        $this->assertEquals($url, $this->url);
    }

    public function testGetUrlReturnsURLWhenSdkKeyAndTemplateAreNonEmptyString()
    {
        $url_template = "https://custom/datafiles/%s.json";

        $configManagerMock = $this->getMockBuilder(HTTPProjectConfigManagerTester::class)
            ->setConstructorArgs(array('sdk_key', null, $url_template, false, DATAFILE, false,
                                    $this->loggerMock, $this->errorHandlerMock))
            ->setMethods(array('fetch'))
            ->getMock();

        $this->errorHandlerMock->expects($this->never())
           ->method('handleError');

        $url = $configManagerMock->getUrl('sdk_key', null, $url_template);

        $this->assertEquals($url, 'https://custom/datafiles/sdk_key.json');
    }

    public function testGetUrlReturnsURLWhenSdkKeyAndTemplateAndAccessTokenAreNonEmptyString()
    {
        $url_template = "https://custom/datafiles/%s.json";

        $configManagerMock = $this->getMockBuilder(HTTPProjectConfigManagerTester::class)
            ->setConstructorArgs(array('sdk_key', null, $url_template, false, DATAFILE, false,
                                    $this->loggerMock, $this->errorHandlerMock, null, 'some_token'))
            ->setMethods(array('fetch'))
            ->getMock();

        $this->errorHandlerMock->expects($this->never())
           ->method('handleError');

        $url = $configManagerMock->getUrl('sdk_key', null, $url_template);

        $this->assertEquals($url, 'https://custom/datafiles/sdk_key.json');
    }

    public function testGetUrlReturnsURLUsingDefaultTemplateWhenTemplateIsEmptyString()
    {
        $configManagerMock = $this->getMockBuilder(HTTPProjectConfigManagerTester::class)
            ->setConstructorArgs(array('sdk_key', null, null, false, DATAFILE, false,
                                    $this->loggerMock, $this->errorHandlerMock))
            ->setMethods(array('fetch'))
            ->getMock();

        $this->errorHandlerMock->expects($this->never())
           ->method('handleError');

        $url = $configManagerMock->getUrl('sdk_key', null, null);

        $this->assertEquals($url, 'https://cdn.optimizely.com/datafiles/sdk_key.json');
    }

    public function testGetUrlReturnsAuthDatafileURLWhenTemplateIsEmptyAndTokenIsProvided()
    {
        $configManagerMock = $this->getMockBuilder(HTTPProjectConfigManagerTester::class)
            ->setConstructorArgs(array('sdk_key', null, null, false, DATAFILE, false,
                                    $this->loggerMock, $this->errorHandlerMock, null, 'some_token'))
            ->setMethods(array('fetch'))
            ->getMock();

        $this->errorHandlerMock->expects($this->never())
           ->method('handleError');

        $url = $configManagerMock->getUrl('sdk_key', null, null);

        $this->assertEquals($url, 'https://config.optimizely.com/datafiles/auth/sdk_key.json');
    }

    public function testHandleResponseReturnsFalseForSameDatafilesRevisions()
    {
        $configManagerMock = $this->getMockBuilder(HTTPProjectConfigManagerTester::class)
            ->setConstructorArgs(array(null, $this->url, null, false, DATAFILE, false,
                                    $this->loggerMock, $this->errorHandlerMock, $this->notificationCenterMock))
            ->setMethods(array('fetch'))
            ->getMock();

        $config = $configManagerMock->getConfig();
        $datafile = json_decode(DATAFILE, true);

        $this->loggerMock->expects($this->never())
            ->method('log');

        $this->notificationCenterMock->expects($this->never())
            ->method('sendNotifications');

        // handleResponse returns False when new Datafile's revision is equal
        // to previous revision.
        $this->assertSame($config->getRevision(), $datafile['revision']);
        $this->assertFalse($configManagerMock->handleResponse(DATAFILE));
    }

    public function testAuthTokenInRequestHeaderWhenTokenIsProvided()
    {
        $configManager = new HTTPProjectConfigManager(
            'sdk_key',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            'access_token'
        );

        // Mock http client to return a valid datafile
        $mock = new MockHandler([
            new Response(200, [], null)
        ]);

        $container = [];
        $history = Middleware::history($container);
        $handler = HandlerStack::create($mock);
        $handler->push($history);

        $client = new Client(['handler' => $handler]);
        $httpClient = new \ReflectionProperty(HTTPProjectConfigManager::class, 'httpClient');
        $httpClient->setAccessible(true);
        $httpClient->setValue($configManager, $client);

        // Fetch datafile
        $configManager->fetch();

        // assert that https call is made to mock as expected.
        $transaction = $container[0];
        $this->assertEquals(
            'https://config.optimizely.com/datafiles/auth/sdk_key.json',
            $transaction['request']->getUri()
        );

        // assert that headers include authorization access token
        $this->assertEquals(
            'Bearer access_token',
            $transaction['request']->getHeaders()['Authorization'][0]
        );
    }
}
