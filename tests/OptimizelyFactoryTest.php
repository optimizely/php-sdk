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
namespace Optimizely\Tests;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Optimizely\OptimizelyFactory;
use Optimizely\ProjectConfigManager\HTTPProjectConfigManager;

class OptimizelyFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->datafile = DATAFILE;
        $this->typedAudiencesDataFile = DATAFILE_WITH_TYPED_AUDIENCES;
    }

    public function testDefaultInstance()
    {
        $optimizelyClient = OptimizelyFactory::createDefaultInstance("some-sdk-key", $this->datafile);

        // client hasn't been mocked yet. Hence, config manager should return config of hardcoded
        // datafile.
        $this->assertEquals('15', $optimizelyClient->configManager->getConfig()->getRevision());

        // Mock http client to return a valid datafile
        $mock = new MockHandler([
            new Response(200, [], $this->typedAudiencesDataFile)
        ]);

        $handler = HandlerStack::create($mock);

        $client = new Client(['handler' => $handler]);
        $httpClient = new \ReflectionProperty(HTTPProjectConfigManager::class, 'httpClient');
        $httpClient->setAccessible(true);
        $httpClient->setValue($optimizelyClient->configManager, $client);

        /// Fetch datafile
        $optimizelyClient->configManager->fetch();

        $this->assertEquals('3', $optimizelyClient->configManager->getConfig()->getRevision());
    }
}
