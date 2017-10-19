<?php
/**
 * Copyright 2017, Optimizely
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
use Optimizely\Bucketer;
use Optimizely\DecisionService\DecisionService;
use Optimizely\Entity\Experiment;
use Optimizely\Entity\Variation;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Logger\NoOpLogger;
use Optimizely\Optimizely;
use Optimizely\ProjectConfig;
use Optimizely\UserProfile\UserProfileServiceInterface;


class DecisionServiceTest extends \PHPUnit_Framework_TestCase
{
    private $bucketerMock;
    private $config;
    private $decisionService;
    private $loggerMock;
    private $testUserId;
    private $userProvideServiceMock;

    public function setUp()
    {
        $this->testUserId = 'testUserId';
        $this->testUserIdWhitelisted = 'user1';
        $this->experimentKey = 'test_experiment';
        $this->testBucketingIdControl = 'testBucketingIdControl!';  // generates bucketing number 3741
        $this->testBucketingIdVariation = '123456789'; // generates bucketing number 4567
        $this->variationKeyControl = 'control';
        $this->variationKeyVariation = 'variation';
        $this->testUserAttributes = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely',
            'location' => 'San Francisco'
        ];

        // Mock Logger
        $this->loggerMock = $this->getMockBuilder(NoOpLogger::class)
            ->setMethods(array('log'))
            ->getMock();
        $this->config = new ProjectConfig(DATAFILE, $this->loggerMock, new NoOpErrorHandler());

        // Mock bucketer
        $this->bucketerMock = $this->getMockBuilder(Bucketer::class)
            ->setConstructorArgs(array($this->loggerMock))
            ->setMethods(array('bucket'))
            ->getMock();

        // Mock user profile service implementation
        $this->userProvideServiceMock = $this->getMockBuilder(UserProfileServiceInterface::class)
            ->getMock();
    }

    public function testGetVariationReturnsNullWhenExperimentIsNotRunning()
    {
        $this->bucketerMock->expects($this->never())
            ->method('bucket');

        $pausedExperiment = $this->config->getExperimentFromKey('paused_experiment');

        $this->decisionService = new DecisionService($this->loggerMock, $this->config);
        $bucketer = new \ReflectionProperty(DecisionService::class, '_bucketer');
        $bucketer->setAccessible(true);
        $bucketer->setValue($this->decisionService, $this->bucketerMock);

        $variation = $this->decisionService->getVariation($pausedExperiment, $this->testUserId);

        $this->assertNull($variation);
    }

    public function testGetVariationBucketsUserWhenExperimentIsRunning()
    {
        $expectedVariation = new Variation('7722370027', 'control');
        $this->bucketerMock->expects($this->once())
            ->method('bucket')
            ->willReturn($expectedVariation);

        $runningExperiment = $this->config->getExperimentFromKey('test_experiment');

        $this->decisionService = new DecisionService($this->loggerMock, $this->config);
        $bucketer = new \ReflectionProperty(DecisionService::class, '_bucketer');
        $bucketer->setAccessible(true);
        $bucketer->setValue($this->decisionService, $this->bucketerMock);

        $variation = $this->decisionService->getVariation($runningExperiment, $this->testUserId, $this->testUserAttributes);

        $this->assertEquals(
            $expectedVariation,
            $variation
        );
    }

    public function testGetVariationReturnsWhitelistedVariation()
    {
        $expectedVariation = new Variation('7722370027', 'control');
        $runningExperiment = $this->config->getExperimentFromKey('test_experiment');

        $callIndex = 0;
        $this->bucketerMock->expects($this->never())
            ->method('bucket');
         $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "user1" is not in the forced variation map.');              
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'User "user1" is forced in variation "control" of experiment "test_experiment".');

        $this->decisionService = new DecisionService($this->loggerMock, $this->config);
        $bucketer = new \ReflectionProperty(DecisionService::class, '_bucketer');
        $bucketer->setAccessible(true);
        $bucketer->setValue($this->decisionService, $this->bucketerMock);

        $variation = $this->decisionService->getVariation($runningExperiment, 'user1');

        $this->assertEquals(
            $expectedVariation,
            $variation
        );
    }

    public function testGetVariationReturnsWhitelistedVariationForGroupedExperiment()
    {
        $expectedVariation = new Variation('7722260071', 'group_exp_1_var_1',[
                [
                  "id" => "155563",
                  "value" => "groupie_1_v1"
                ]
          ]);
        $runningExperiment = $this->config->getExperimentFromKey('group_experiment_1');

        $callIndex = 0;
        $this->bucketerMock->expects($this->never())
            ->method('bucket');
         $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "user1" is not in the forced variation map.');                   
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'User "user1" is forced in variation "group_exp_1_var_1" of experiment "group_experiment_1".');

        $this->decisionService = new DecisionService($this->loggerMock, $this->config);
        $bucketer = new \ReflectionProperty(DecisionService::class, '_bucketer');
        $bucketer->setAccessible(true);
        $bucketer->setValue($this->decisionService, $this->bucketerMock);

        $variation = $this->decisionService->getVariation($runningExperiment, 'user1');

        $this->assertEquals(
            $expectedVariation,
            $variation
        );
    }

    public function testGetVariationBucketsWhenForcedVariationsIsEmpty()
    {
        $expectedVariation = new Variation('7722370027', 'control');
        $this->bucketerMock->expects($this->once())
            ->method('bucket')
            ->willReturn($expectedVariation);

        $runningExperiment = $this->config->getExperimentFromKey('test_experiment');

        // empty out the forcedVariations property
        $experiment = new \ReflectionProperty(Experiment::class, '_forcedVariations');
        $experiment->setAccessible(true);
        $experiment->setValue($runningExperiment, array());

        $this->decisionService = new DecisionService($this->loggerMock, $this->config);
        $bucketer = new \ReflectionProperty(DecisionService::class, '_bucketer');
        $bucketer->setAccessible(true);
        $bucketer->setValue($this->decisionService, $this->bucketerMock);

        $variation = $this->decisionService->getVariation($runningExperiment, 'user1', $this->testUserAttributes);

        $this->assertEquals(
            $expectedVariation,
            $variation
        );
    }

    public function testGetVariationBucketsWhenWhitelistedVariationIsInvalid()
    {
        $expectedVariation = new Variation('7722370027', 'control');
        $this->bucketerMock->expects($this->once())
            ->method('bucket')
            ->willReturn($expectedVariation);

        $runningExperiment = $this->config->getExperimentFromKey('test_experiment');

        // modify the forcedVariation to point to invalid variation
        $experiment = new \ReflectionProperty(Experiment::class, '_forcedVariations');
        $experiment->setAccessible(true);
        $experiment->setValue($runningExperiment, [
            'user_1' => 'invalid'
        ]);

        $this->decisionService = new DecisionService($this->loggerMock, $this->config);
        $bucketer = new \ReflectionProperty(DecisionService::class, '_bucketer');
        $bucketer->setAccessible(true);
        $bucketer->setValue($this->decisionService, $this->bucketerMock);

        $variation = $this->decisionService->getVariation($runningExperiment, 'user1', $this->testUserAttributes);

        $this->assertEquals(
            $expectedVariation,
            $variation
        );
    }

    public function testGetVariationBucketsUserWhenUserIsNotWhitelisted()
    {
        $expectedVariation = new Variation('7722370027', 'control');
        $this->bucketerMock->expects($this->once())
            ->method('bucket')
            ->willReturn($expectedVariation);

        $runningExperiment = $this->config->getExperimentFromKey('test_experiment');

        $this->decisionService = new DecisionService($this->loggerMock, $this->config);
        $bucketer = new \ReflectionProperty(DecisionService::class, '_bucketer');
        $bucketer->setAccessible(true);
        $bucketer->setValue($this->decisionService, $this->bucketerMock);

        $variation = $this->decisionService->getVariation($runningExperiment, 'not_whitelisted_user', $this->testUserAttributes);

        $this->assertEquals(
            $expectedVariation,
            $variation
        );
    }

    public function testGetVariationReturnsNullIfUserDoesNotMeetAudienceConditions()
    {
        $this->bucketerMock->expects($this->never())
            ->method('bucket');

        $runningExperiment = $this->config->getExperimentFromKey('test_experiment');

        $this->decisionService = new DecisionService($this->loggerMock, $this->config);
        $bucketer = new \ReflectionProperty(DecisionService::class, '_bucketer');
        $bucketer->setAccessible(true);
        $bucketer->setValue($this->decisionService, $this->bucketerMock);

        $variation = $this->decisionService->getVariation($runningExperiment, $this->testUserId); // no matching attributes

        $this->assertNull($variation);
    }

    public function testGetVariationReturnsStoredVariationIfAvailable()
    {
        $userId = 'not_whitelisted_user';
        $runningExperiment = $this->config->getExperimentFromKey('test_experiment');
        $expectedVariation = new Variation('7722370027', 'control');

        $callIndex = 0;
        $this->bucketerMock->expects($this->never())
            ->method('bucket');
         $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, 'User "not_whitelisted_user" is not in the forced variation map.');  
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'Returning previously activated variation "control" of experiment "test_experiment" for user "not_whitelisted_user" from user profile.');

        $storedUserProfile = array(
            'user_id' => $userId,
            'experiment_bucket_map' => array(
                '7716830082' => array(
                    'variation_id' => '7722370027'
                )
            )
        );
        $this->userProvideServiceMock->expects($this->once())
            ->method('lookup')
            ->willReturn($storedUserProfile);

        $this->decisionService = new DecisionService($this->loggerMock, $this->config, $this->userProvideServiceMock);
        $bucketer = new \ReflectionProperty(DecisionService::class, '_bucketer');
        $bucketer->setAccessible(true);
        $bucketer->setValue($this->decisionService, $this->bucketerMock);

        $variation = $this->decisionService->getVariation($runningExperiment, $userId);
        $this->assertEquals($expectedVariation, $variation);
    }

    public function testGetVariationBucketsIfNoStoredVariation()
    {
        $userId = $this->testUserId;
        $runningExperiment = $this->config->getExperimentFromKey('test_experiment');
        $expectedVariation = new Variation('7722370027', 'control');

        $callIndex = 0;
        $this->bucketerMock->expects($this->once())
            ->method('bucket')
            ->willReturn($expectedVariation);
         $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('User "%s" is not in the forced variation map.', $userId));  
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'No previously activated variation of experiment "test_experiment" for user "testUserId" found in user profile.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'Saved variation "control" of experiment "test_experiment" for user "testUserId".');

        $storedUserProfile = array(
            'user_id' => $userId,
            'experiment_bucket_map' => array()
        );
        $this->userProvideServiceMock->expects($this->once())
            ->method('lookup')
            ->willReturn($storedUserProfile);

        $this->userProvideServiceMock->expects($this->once())
            ->method('save')
            ->with(array(
                'user_id' => $userId,
                'experiment_bucket_map' => array(
                    '7716830082' => array(
                        'variation_id' => '7722370027'
                    )
                )
            ));

        $this->decisionService = new DecisionService($this->loggerMock, $this->config, $this->userProvideServiceMock);
        $bucketer = new \ReflectionProperty(DecisionService::class, '_bucketer');
        $bucketer->setAccessible(true);
        $bucketer->setValue($this->decisionService, $this->bucketerMock);

        $variation = $this->decisionService->getVariation($runningExperiment, $userId, $this->testUserAttributes);
        $this->assertEquals($expectedVariation, $variation);
    }

    public function testGetVariationBucketsIfStoredVariationIsInvalid()
    {
        $userId = $this->testUserId;
        $runningExperiment = $this->config->getExperimentFromKey('test_experiment');
        $expectedVariation = new Variation('7722370027', 'control');

        $callIndex = 0;
        $this->bucketerMock->expects($this->once())
            ->method('bucket')
            ->willReturn($expectedVariation);
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('User "%s" is not in the forced variation map.', $userId));  
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'User "testUserId" was previously bucketed into variation with ID "invalid" for experiment "test_experiment", but no matching variation was found for that user. We will re-bucket the user.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'Saved variation "control" of experiment "test_experiment" for user "testUserId".');

        $storedUserProfile = array(
            'user_id' => $userId,
            'experiment_bucket_map' => array(
                '7716830082' => array(
                    'variation_id' => 'invalid'
                )
            )
        );
        $this->userProvideServiceMock->expects($this->once())
            ->method('lookup')
            ->willReturn($storedUserProfile);

        $this->userProvideServiceMock->expects($this->once())
            ->method('save')
            ->with(array(
                'user_id' => $userId,
                'experiment_bucket_map' => array(
                    '7716830082' => array(
                        'variation_id' => '7722370027'
                    )
                )
            ));

        $this->decisionService = new DecisionService($this->loggerMock, $this->config, $this->userProvideServiceMock);
        $bucketer = new \ReflectionProperty(DecisionService::class, '_bucketer');
        $bucketer->setAccessible(true);
        $bucketer->setValue($this->decisionService, $this->bucketerMock);

        $variation = $this->decisionService->getVariation($runningExperiment, $userId, $this->testUserAttributes);
        $this->assertEquals($expectedVariation, $variation);
    }

    public function testGetVariationBucketsIfUserProfileServiceLookupThrows()
    {
        $userId = $this->testUserId;
        $runningExperiment = $this->config->getExperimentFromKey('test_experiment');
        $expectedVariation = new Variation('7722370027', 'control');

        $callIndex = 0;
        $this->bucketerMock->expects($this->once())
            ->method('bucket')
            ->willReturn($expectedVariation);
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('User "%s" is not in the forced variation map.', $userId)); 
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::ERROR, 'The User Profile Service lookup method failed: I am error.');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'Saved variation "control" of experiment "test_experiment" for user "testUserId".');

        $storedUserProfile = array(
            'user_id' => $userId,
            'experiment_bucket_map' => array(
                '7716830082' => array(
                    'variation_id' => 'invalid'
                )
            )
        );
        $this->userProvideServiceMock
            ->method('lookup')
            ->will($this->throwException(new Exception('I am error')));

        $this->userProvideServiceMock->expects($this->once())
            ->method('save')
            ->with(array(
                'user_id' => $userId,
                'experiment_bucket_map' => array(
                    '7716830082' => array(
                        'variation_id' => '7722370027'
                    )
                )
            ));

        $this->decisionService = new DecisionService($this->loggerMock, $this->config, $this->userProvideServiceMock);
        $bucketer = new \ReflectionProperty(DecisionService::class, '_bucketer');
        $bucketer->setAccessible(true);
        $bucketer->setValue($this->decisionService, $this->bucketerMock);

        $variation = $this->decisionService->getVariation($runningExperiment, $userId, $this->testUserAttributes);
        $this->assertEquals($expectedVariation, $variation);
    }

    public function testGetVariationBucketsIfUserProfileServiceSaveThrows()
    {
        $userId = $this->testUserId;
        $runningExperiment = $this->config->getExperimentFromKey('test_experiment');
        $expectedVariation = new Variation('7722370027', 'control');

        $callIndex = 0;
        $this->bucketerMock->expects($this->once())
            ->method('bucket')
            ->willReturn($expectedVariation);
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::DEBUG, sprintf('User "%s" is not in the forced variation map.', $userId)); 
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::INFO, 'No user profile found for user with ID "testUserId".');
        $this->loggerMock->expects($this->at($callIndex++))
            ->method('log')
            ->with(Logger::WARNING, 'Failed to save variation "control" of experiment "test_experiment" for user "testUserId".');

        $storedUserProfile = array(
            'user_id' => $userId,
            'experiment_bucket_map' => array(
                '7716830082' => array(
                    'variation_id' => 'invalid'
                )
            )
        );
        $this->userProvideServiceMock->expects($this->once())
            ->method('lookup')
            ->willReturn(null);

        $this->userProvideServiceMock
            ->method('save')
            ->with($this->throwException(new Exception()));

        $this->decisionService = new DecisionService($this->loggerMock, $this->config, $this->userProvideServiceMock);
        $bucketer = new \ReflectionProperty(DecisionService::class, '_bucketer');
        $bucketer->setAccessible(true);
        $bucketer->setValue($this->decisionService, $this->bucketerMock);

        $variation = $this->decisionService->getVariation($runningExperiment, $userId, $this->testUserAttributes);
        $this->assertEquals($expectedVariation, $variation);
    }

    public function testGetVariationUserWithSetForcedVariation()
    {
        $experimentKey = 'test_experiment';
        $pausedExperimentKey = 'paused_experiment';
        $userId = 'test_user';
        $bucketedVariationKey = 'control';

        $optlyObject = new Optimizely(DATAFILE, new ValidEventDispatcher(), $this->loggerMock);

        $userAttributes = [
            'device_type' => 'iPhone',
            'location' => 'San Francisco'
        ];

        $optlyObject->activate($experimentKey, $userId, $userAttributes);

        // confirm normal bucketing occurs before setting the forced variation
        $forcedVariationKey = $optlyObject->getVariation($experimentKey, $userId, $userAttributes);
        $this->assertEquals($bucketedVariationKey, $forcedVariationKey);

        // test valid experiment
        $this->assertTrue($optlyObject->setForcedVariation($experimentKey, $userId, $forcedVariationKey), sprintf('Set variation to "%s" failed.', $forcedVariationKey));
        $forcedVariationKey = $optlyObject->getVariation($experimentKey, $userId, $userAttributes);
        $this->assertEquals($forcedVariationKey, $forcedVariationKey);

        // clear forced variation and confirm that normal bucketing occurs
        $this->assertTrue($optlyObject->setForcedVariation($experimentKey, $userId, null), sprintf('Set variation to "%s" failed.', $forcedVariationKey));
        $forcedVariationKey = $optlyObject->getVariation($experimentKey, $userId, $userAttributes);
        $this->assertEquals($bucketedVariationKey, $forcedVariationKey);

        // check that a paused experiment returns null
        $this->assertTrue($optlyObject->setForcedVariation($pausedExperimentKey, $userId, 'variation'), sprintf('Set variation to "%s" failed.', $forcedVariationKey));
        $forcedVariationKey = $optlyObject->getVariation($pausedExperimentKey, $userId, $userAttributes);
        $this->assertNull($forcedVariationKey);
    }

    public function testGetVariationWithBucketingId()
    {
        $pausedExperimentKey = 'paused_experiment';
        $userId = 'test_user';

        $userAttributesWithBucketingId = [
            'device_type' => 'iPhone',
            'company' => 'Optimizely',
            'location' => 'San Francisco',
            RESERVED_ATTRIBUTE_KEY_BUCKETING_ID => $this->testBucketingIdVariation
        ];

        $invalidUserAttributesWithBucketingId = [
            'company' => 'Optimizely',
            RESERVED_ATTRIBUTE_KEY_BUCKETING_ID => $this->testBucketingIdControl
        ];

        $optlyObject = new Optimizely(DATAFILE, new ValidEventDispatcher(), $this->loggerMock);

        // confirm normal bucketing occurs before setting the bucketing ID
        $variationKey = $optlyObject->getVariation($this->experimentKey, $userId, $this->testUserAttributes);
        $this->assertEquals($this->variationKeyControl, $variationKey);

        // confirm valid bucketing with bucketing ID set in attributes
        $variationKey = $optlyObject->getVariation($this->experimentKey, $userId, $userAttributesWithBucketingId);
        $this->assertEquals($this->variationKeyVariation, $variationKey);

        // check invalid audience with bucketing ID
        $variationKey = $optlyObject->getVariation($this->experimentKey, $userId, $invalidUserAttributesWithBucketingId);
        $this->assertEquals(null, $variationKey);

        // check null audience with bucketing Id
        $variationKey = $optlyObject->getVariation($this->experimentKey, $userId, null);
        $this->assertEquals(null, $variationKey);

        // test that an experiment that's not running returns a null variation
        $variationKey = $optlyObject->getVariation($pausedExperimentKey, $userId, $userAttributesWithBucketingId);
        $this->assertEquals(null, $variationKey);

        // check forced variation
        $this->assertTrue($optlyObject->setForcedVariation($this->experimentKey, $userId, $this->variationKeyControl), sprintf('Set variation to "%s" failed.', $this->variationKeyControl));
        $variationKey = $optlyObject->getVariation($this->experimentKey, $userId, $userAttributesWithBucketingId);
        $this->assertEquals( $this->variationKeyControl, $variationKey);

        // check whitelisted variation
        $variationKey = $optlyObject->getVariation($this->experimentKey, $this->testUserIdWhitelisted, $userAttributesWithBucketingId);
        $this->assertEquals( $this->variationKeyControl, $variationKey);

        // check user profile
        $storedUserProfile = array(
            'user_id' => $userId,
            'experiment_bucket_map' => array(
                '7716830082' => array(
                    'variation_id' => '7722370027'
                )
            )
        );
        $this->userProvideServiceMock
            ->method('lookup')
            ->willReturn($storedUserProfile);

        $this->decisionService = new DecisionService($this->loggerMock, $this->config, $this->userProvideServiceMock);
        $bucketer = new \ReflectionProperty(DecisionService::class, '_bucketer');
        $bucketer->setAccessible(true);
        $bucketer->setValue($this->decisionService, $this->bucketerMock);

        $variationKey = $optlyObject->getVariation($this->experimentKey, $userId, $userAttributesWithBucketingId);
        $this->assertEquals($this->variationKeyControl, $variationKey, sprintf('Variation "%s" does not match expected user profile variation "%s".', $variationKey, $this->variationKeyControl));

    }
}
