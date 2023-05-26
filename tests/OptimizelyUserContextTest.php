<?php
/**
 * Copyright 2021, 2023 Optimizely
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
use Optimizely\Decide\OptimizelyDecideOption;
use TypeError;
use Optimizely\Logger\NoOpLogger;
use Optimizely\Optimizely;
use Optimizely\OptimizelyUserContext;
use Optimizely\OptimizelyDecisionContext;
use Optimizely\OptimizelyForcedDecision;
use PHPUnit\Framework\TestCase;

class OptimizelyUserContextTest extends TestCase
{
    private $datafile;
    private $loggerMock;
    private $optimizelyObject;

    protected function setUp() : void
    {
        $this->datafile = DATAFILE;

        // Mock Logger
        $this->loggerMock = $this->getMockBuilder(NoOpLogger::class)
            ->setMethods(array('log'))
            ->getMock();


        $this->optimizelyObject = new Optimizely($this->datafile, null, $this->loggerMock);
    }
    public function testOptimizelyUserContextIsCreatedWithExpectedValues()
    {
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];
        $optUserContext = new OptimizelyUserContext($this->optimizelyObject, $userId, $attributes);

        $this->assertEquals($userId, $optUserContext->getUserId());
        $this->assertEquals($attributes, $optUserContext->getAttributes());
        $this->assertSame($this->optimizelyObject, $optUserContext->getOptimizely());
    }

    public function testOptimizelyUserContextThrowsErrorWhenNonArrayPassedAsAttributes()
    {
        $userId = 'test_user';

        try {
            $optUserContext = new OptimizelyUserContext($this->optimizelyObject, $userId, 'HelloWorld');
        } catch (Exception $exception) {
            return;
        } catch (TypeError $exception) {
            return;
        }

        $this->fail('Unexpected behavior. UserContext should have thrown an error.');
    }

    public function testSetAttribute()
    {
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];
        $optUserContext = new OptimizelyUserContext($this->optimizelyObject, $userId, $attributes);

        $this->assertEquals($attributes, $optUserContext->getAttributes());

        $optUserContext->setAttribute('color', 'red');
        $this->assertEquals([
            "browser" => "chrome",
            "color" => "red"
        ], $optUserContext->getAttributes());
    }

    public function testSetAttributeOverridesValueOfExistingKey()
    {
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];
        $optUserContext = new OptimizelyUserContext($this->optimizelyObject, $userId, $attributes);

        $this->assertEquals($attributes, $optUserContext->getAttributes());

        $optUserContext->setAttribute('browser', 'firefox');
        $this->assertEquals(["browser" => "firefox"], $optUserContext->getAttributes());
    }

    public function testSetAttributeWhenNoAttributesProvidedInConstructor()
    {
        $userId = 'test_user';
        $optUserContext = new OptimizelyUserContext($this->optimizelyObject, $userId);

        $this->assertEquals([], $optUserContext->getAttributes());

        $optUserContext->setAttribute('browser', 'firefox');
        $this->assertEquals(["browser" => "firefox"], $optUserContext->getAttributes());
    }

    public function testDecideCallsAndReturnsOptimizelyDecideAPI()
    {
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];


        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('decide'))
            ->getMock();


        $optUserContext = new OptimizelyUserContext($optimizelyMock, $userId, $attributes);

        //assert that decide is called with expected params
        $optimizelyMock->expects($this->exactly(1))
        ->method('decide')
        ->with(
            $optUserContext,
            'test_feature',
            ['DISABLE_DECISION_EVENT', 'ENABLED_FLAGS_ONLY']
        )
        ->will($this->returnValue('Mocked return value'));

        $this->assertEquals(
            'Mocked return value',
            $optUserContext->decide('test_feature', ['DISABLE_DECISION_EVENT', 'ENABLED_FLAGS_ONLY'])
        );
    }

    public function testDecideResponseUserContextNotEqualToCalledUserContext()
    {
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];
    
        $optlyObject = new Optimizely($this->datafile);

        $optUserContext = new OptimizelyUserContext($optlyObject, $userId, $attributes);
        $decision = $optUserContext->decide('test_feature', ['DISABLE_DECISION_EVENT', 'ENABLED_FLAGS_ONLY']);
        
        $this->assertEquals(
            $optUserContext->getAttributes(),
            $decision->getUserContext()->getAttributes()
        );

        $optUserContext->setAttribute("test_key", "test_value");
        
        $this->assertNotEquals(
            $optUserContext->getAttributes(),
            $decision->getUserContext()->getAttributes()
        );
    }

    public function testDecideAllCallsAndReturnsOptimizelyDecideAllAPI()
    {
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];

        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('decideAll'))
            ->getMock();


        $optUserContext = new OptimizelyUserContext($optimizelyMock, $userId, $attributes);

        //assert that decideAll is called with expected params
        $optimizelyMock->expects($this->exactly(1))
        ->method('decideAll')
        ->with(
            $optUserContext,
            ['ENABLED_FLAGS_ONLY']
        )
        ->will($this->returnValue('Mocked return value'));

        $this->assertEquals(
            'Mocked return value',
            $optUserContext->decideAll(['ENABLED_FLAGS_ONLY'])
        );
    }

    public function testDecideForKeysCallsAndReturnsOptimizelyDecideForKeysAPI()
    {
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];

        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('decideForKeys'))
            ->getMock();


        $optUserContext = new OptimizelyUserContext($optimizelyMock, $userId, $attributes);

        //assert that decideForKeys is called with expected params
        $optimizelyMock->expects($this->exactly(1))
        ->method('decideForKeys')
        ->with(
            $optUserContext,
            ['test_feature', 'test_experiment'],
            ['DISABLE_DECISION_EVENT']
        )
        ->will($this->returnValue('Mocked return value'));

        $this->assertEquals(
            'Mocked return value',
            $optUserContext->decideForKeys(['test_feature', 'test_experiment'], ['DISABLE_DECISION_EVENT'])
        );
    }

    public function testTrackEventCallsAndReturnsOptimizelyTrackAPI()
    {
        $userId = 'test_user';
        $attributes = [];
        $eventKey = "test_event";
        $eventTags = [ "revenue" => 50];

        $optimizelyMock = $this->getMockBuilder(Optimizely::class)
            ->setConstructorArgs(array($this->datafile, null, $this->loggerMock))
            ->setMethods(array('track'))
            ->getMock();


        $optUserContext = new OptimizelyUserContext($optimizelyMock, $userId, $attributes);

        //assert that track is called with expected params
        $optimizelyMock->expects($this->exactly(1))
        ->method('track')
        ->with(
            $eventKey,
            $userId,
            $attributes,
            $eventTags
        )
        ->will($this->returnValue('Mocked return value'));

        $this->assertEquals(
            'Mocked return value',
            $optUserContext->trackEvent($eventKey, $eventTags)
        );
    }

    public function testJsonSerialize()
    {
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];
        $optUserContext = new OptimizelyUserContext($this->optimizelyObject, $userId, $attributes);

        $this->assertEquals([
            'userId' => $userId,
            'attributes' => $attributes
        ], json_decode(json_encode($optUserContext), true));
    }

    // Forced decision tests

    public function testForcedDecisionValidDatafileReturnStatus()
    {
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];

        $validOptlyObject = new Optimizely($this->datafile);

        $optUserContext = new OptimizelyUserContext($validOptlyObject, $userId, $attributes);

        $context = new OptimizelyDecisionContext("flag1", "targeted_delivery");
        $decision = new OptimizelyForcedDecision("variation1");

        $setForcedDecision = $optUserContext->setForcedDecision($context, $decision);
        $this->assertTrue($setForcedDecision);

        $getForcedDecision = $optUserContext->getForcedDecision($context);
        $this->assertEquals($getForcedDecision, "variation1");

        $removeForcedDecision = $optUserContext->removeForcedDecision($context);
        $this->assertTrue($removeForcedDecision);

        $removeAllForcedDecision = $optUserContext->removeAllForcedDecisions();
        $this->assertTrue($removeAllForcedDecision);
    }

    public function testForcedDecisionFlagToDecision()
    {
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];

        $validOptlyObject = new Optimizely($this->datafile);

        $optUserContext = new OptimizelyUserContext($validOptlyObject, $userId, $attributes);

        $context = new OptimizelyDecisionContext("boolean_single_variable_feature", null);
        $decision = new OptimizelyForcedDecision("177773");

        $setForcedDecision = $optUserContext->setForcedDecision($context, $decision);
        $this->assertTrue($setForcedDecision);

        $getForcedDecision = $optUserContext->getForcedDecision($context);
        $this->assertEquals($getForcedDecision, "177773");

        $decision = $optUserContext->decide('boolean_single_variable_feature', [OptimizelyDecideOption::INCLUDE_REASONS]);
        $this->assertEquals($decision->getVariationKey(), "177773");
        $this->assertNull($decision->getRuleKey());
        $this->assertTrue($decision->getEnabled());
        $this->assertEquals($decision->getFlagKey(), "boolean_single_variable_feature");
        $this->assertEquals($decision->getUserContext()->getUserId(), $userId);
        $this->assertEquals(count($decision->getUserContext()->getAttributes()), 1);
        $this->assertEquals($decision->getReasons(), [
            'Decided by forced decision.',
            'Variation (177773) is mapped to flag (boolean_single_variable_feature) and user (test_user) in the forced decision map.'
        ]);

        // Removing forced decision to test
        $removeForcedDecision = $optUserContext->removeForcedDecision($context);
        $this->assertTrue($removeForcedDecision);

        $decision = $optUserContext->decide('boolean_single_variable_feature', [OptimizelyDecideOption::INCLUDE_REASONS]);
        $this->assertEquals($decision->getVariationKey(), "177778");
        $this->assertEquals($decision->getRuleKey(), "rollout_1_exp_3");
        $this->assertTrue($decision->getEnabled());
        $this->assertEquals($decision->getFlagKey(), "boolean_single_variable_feature");
        $this->assertEquals($decision->getUserContext()->getUserId(), $userId);
        $this->assertEquals(count($decision->getUserContext()->getAttributes()), 1);
        $this->assertEquals($decision->getReasons(), [
            "The feature flag 'boolean_single_variable_feature' is not used in any experiments.",
            'Audiences for rule 1 collectively evaluated to FALSE.',
            'User "test_user" does not meet conditions for targeting rule "1".',
            'Audiences for rule 2 collectively evaluated to FALSE.',
            'User "test_user" does not meet conditions for targeting rule "2".',
            'Audiences for rule Everyone Else collectively evaluated to TRUE.',
            'User "test_user" meets condition for targeting rule "Everyone Else".',
            'Assigned bucket 3041 to user "test_user" with bucketing ID "test_user".',
            'User "test_user" is in the traffic group of targeting rule "Everyone Else".',
            "User 'test_user' is bucketed into rollout for feature flag 'boolean_single_variable_feature'."
        ]);
    }
    public function testForcedDecisionExperimentRuleToDecision()
    {
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];

        $validOptlyObject = new Optimizely($this->datafile);

        $optUserContext = new OptimizelyUserContext($validOptlyObject, $userId, $attributes);

        $context = new OptimizelyDecisionContext("boolean_feature", 'test_experiment_2');
        $decision = new OptimizelyForcedDecision("test_variation_1");

        $setForcedDecision = $optUserContext->setForcedDecision($context, $decision);
        $this->assertTrue($setForcedDecision);

        $getForcedDecision = $optUserContext->getForcedDecision($context);
        $this->assertEquals($getForcedDecision, "test_variation_1");

        $decision = $optUserContext->decide('boolean_feature', [OptimizelyDecideOption::INCLUDE_REASONS]);
        $this->assertEquals($decision->getVariationKey(), "test_variation_1");
        $this->assertEquals($decision->getRuleKey(), "test_experiment_2");
        $this->assertTrue($decision->getEnabled());
        $this->assertEquals($decision->getFlagKey(), "boolean_feature");
        $this->assertEquals($decision->getUserContext()->getUserId(), $userId);
        $this->assertEquals(count($decision->getUserContext()->getAttributes()), 1);
        $this->assertEquals($decision->getReasons(), [
            'Decided by forced decision.',
            'Variation (test_variation_1) is mapped to flag (boolean_feature), rule (test_experiment_2) and user (test_user) in the forced decision map.',
            "The user 'test_user' is bucketed into experiment 'test_experiment_2' of feature 'boolean_feature'."
        ]);

        // Removing forced decision to test
        $removeForcedDecision = $optUserContext->removeForcedDecision($context);
        $this->assertTrue($removeForcedDecision);

        $decision = $optUserContext->decide('boolean_feature', [OptimizelyDecideOption::INCLUDE_REASONS]);
        $this->assertEquals($decision->getVariationKey(), "test_variation_2");
        $this->assertEquals($decision->getRuleKey(), "test_experiment_2");
        $this->assertTrue($decision->getEnabled());
        $this->assertEquals($decision->getFlagKey(), "boolean_feature");
        $this->assertEquals($decision->getUserContext()->getUserId(), $userId);
        $this->assertEquals(count($decision->getUserContext()->getAttributes()), 1);
        $this->assertEquals($decision->getReasons(), [
            'Audiences for experiment "test_experiment_2" collectively evaluated to TRUE.',
            'Assigned bucket 9075 to user "test_user" with bucketing ID "test_user".',
            'User "test_user" is in variation test_variation_2 of experiment test_experiment_2.',
            "The user 'test_user' is bucketed into experiment 'test_experiment_2' of feature 'boolean_feature'."
        ]);
    }
    public function testForcedDecisionRuleDeliveryRuleToDecision()
    {
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];

        $validOptlyObject = new Optimizely($this->datafile);

        $optUserContext = new OptimizelyUserContext($validOptlyObject, $userId, $attributes);
        
        $context = new OptimizelyDecisionContext("boolean_single_variable_feature", "rollout_1_exp_3");
        $decision = new OptimizelyForcedDecision("177773");

        $setForcedDecision = $optUserContext->setForcedDecision($context, $decision);
        $this->assertTrue($setForcedDecision);

        $getForcedDecision = $optUserContext->getForcedDecision($context);
        $this->assertEquals($getForcedDecision, "177773");

        $decision = $optUserContext->decide('boolean_single_variable_feature', [OptimizelyDecideOption::INCLUDE_REASONS]);
        $this->assertEquals($decision->getVariationKey(), "177773");
        $this->assertEquals($decision->getRuleKey(), "rollout_1_exp_3");
        $this->assertTrue($decision->getEnabled());
        $this->assertEquals($decision->getFlagKey(), "boolean_single_variable_feature");
        $this->assertEquals($decision->getUserContext()->getUserId(), $userId);
        $this->assertEquals(count($decision->getUserContext()->getAttributes()), 1);
        $this->assertEquals($decision->getReasons(), [
            "The feature flag 'boolean_single_variable_feature' is not used in any experiments.",
            'Audiences for rule 1 collectively evaluated to FALSE.',
            'User "test_user" does not meet conditions for targeting rule "1".',
            'Audiences for rule 2 collectively evaluated to FALSE.',
            'User "test_user" does not meet conditions for targeting rule "2".',
            'Decided by forced decision.',
            'Variation (177773) is mapped to flag (boolean_single_variable_feature), rule (rollout_1_exp_3) and user (test_user) in the forced decision map.',
            "User 'test_user' is bucketed into rollout for feature flag 'boolean_single_variable_feature'."
        ]);

        // Removing forced decision to test
        $removeForcedDecision = $optUserContext->removeForcedDecision($context);
        $this->assertTrue($removeForcedDecision);

        $decision = $optUserContext->decide('boolean_single_variable_feature', [OptimizelyDecideOption::INCLUDE_REASONS]);
        $this->assertEquals($decision->getVariationKey(), "177778");
        $this->assertEquals($decision->getRuleKey(), "rollout_1_exp_3");
        $this->assertTrue($decision->getEnabled());
        $this->assertEquals($decision->getFlagKey(), "boolean_single_variable_feature");
        $this->assertEquals($decision->getUserContext()->getUserId(), $userId);
        $this->assertEquals(count($decision->getUserContext()->getAttributes()), 1);
        $this->assertEquals($decision->getReasons(), [
            "The feature flag 'boolean_single_variable_feature' is not used in any experiments.",
            'Audiences for rule 1 collectively evaluated to FALSE.',
            'User "test_user" does not meet conditions for targeting rule "1".',
            'Audiences for rule 2 collectively evaluated to FALSE.',
            'User "test_user" does not meet conditions for targeting rule "2".',
            'Audiences for rule Everyone Else collectively evaluated to TRUE.',
            'User "test_user" meets condition for targeting rule "Everyone Else".',
            'Assigned bucket 3041 to user "test_user" with bucketing ID "test_user".',
            'User "test_user" is in the traffic group of targeting rule "Everyone Else".',
            "User 'test_user' is bucketed into rollout for feature flag 'boolean_single_variable_feature'."
        ]);
    }

    public function testForcedDecisionInvalidDeliveryRuleToDecision()
    {
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];

        $validOptlyObject = new Optimizely($this->datafile);

        $optUserContext = new OptimizelyUserContext($validOptlyObject, $userId, $attributes);
        $context = new OptimizelyDecisionContext("boolean_single_variable_feature", "rollout_1_exp_3");
        $decision = new OptimizelyForcedDecision("invalid");
        $setForcedDecision = $optUserContext->setForcedDecision($context, $decision);
        $this->assertTrue($setForcedDecision);

        $decision = $optUserContext->decide('boolean_single_variable_feature', [OptimizelyDecideOption::INCLUDE_REASONS]);
        $this->assertEquals($decision->getVariationKey(), "177778");
        $this->assertEquals($decision->getRuleKey(), "rollout_1_exp_3");
        $this->assertTrue($decision->getEnabled());
        $this->assertEquals($decision->getFlagKey(), "boolean_single_variable_feature");
        $this->assertEquals($decision->getUserContext()->getUserId(), $userId);
        $this->assertEquals(count($decision->getUserContext()->getAttributes()), 1);
        $this->assertEquals($decision->getReasons(), [
            "The feature flag 'boolean_single_variable_feature' is not used in any experiments.",
            'Audiences for rule 1 collectively evaluated to FALSE.',
            'User "test_user" does not meet conditions for targeting rule "1".',
            'Audiences for rule 2 collectively evaluated to FALSE.',
            'User "test_user" does not meet conditions for targeting rule "2".',
            'Invalid variation is mapped to flag (boolean_single_variable_feature), rule (rollout_1_exp_3) and user (test_user) in the forced decision map.',
            'Audiences for rule Everyone Else collectively evaluated to TRUE.',
            'User "test_user" meets condition for targeting rule "Everyone Else".',
            'Assigned bucket 3041 to user "test_user" with bucketing ID "test_user".',
            'User "test_user" is in the traffic group of targeting rule "Everyone Else".',
            "User 'test_user' is bucketed into rollout for feature flag 'boolean_single_variable_feature'."
            ]);
    }

    public function testForcedDecisionInvalidExperimentRuleToDecision()
    {
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];

        $validOptlyObject = new Optimizely($this->datafile);

        $optUserContext = new OptimizelyUserContext($validOptlyObject, $userId, $attributes);
        $context = new OptimizelyDecisionContext("boolean_feature", null);
        $decision = new OptimizelyForcedDecision("invalid");
        $setForcedDecision = $optUserContext->setForcedDecision($context, $decision);
        $this->assertTrue($setForcedDecision);

        $decision = $optUserContext->decide('boolean_feature', [OptimizelyDecideOption::INCLUDE_REASONS]);
        $this->assertEquals("test_variation_2", $decision->getVariationKey());
        $this->assertEquals("test_experiment_2", $decision->getRuleKey());
        $this->assertTrue($decision->getEnabled());
        $this->assertEquals("boolean_feature", $decision->getFlagKey());
        $this->assertEquals($decision->getUserContext()->getUserId(), $userId);
        $this->assertEquals(1, count($decision->getUserContext()->getAttributes()));
        $this->assertEquals([
            'Invalid variation is mapped to flag (boolean_feature) and user (test_user) in the forced decision map.',
            'Audiences for experiment "test_experiment_2" collectively evaluated to TRUE.',
            'Assigned bucket 9075 to user "test_user" with bucketing ID "test_user".',
            'User "test_user" is in variation test_variation_2 of experiment test_experiment_2.',
            "The user 'test_user' is bucketed into experiment 'test_experiment_2' of feature 'boolean_feature'."
        ], $decision->getReasons());
    }

    public function testForcedDecisionConflicts()
    {
        $featureKey = "boolean_feature";
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];

        $validOptlyObject = new Optimizely($this->datafile);

        $optUserContext = new OptimizelyUserContext($validOptlyObject, $userId, $attributes);
        $context = new OptimizelyDecisionContext($featureKey, null);
        $decision = new OptimizelyForcedDecision("test_variation_1");
        $setForcedDecision1 = $optUserContext->setForcedDecision($context, $decision);
        $this->assertTrue($setForcedDecision1);

        $context = new OptimizelyDecisionContext($featureKey, "test_experiment_2");
        $decision = new OptimizelyForcedDecision("test_variation_2");
        $setForcedDecision2 = $optUserContext->setForcedDecision($context, $decision);
        $this->assertTrue($setForcedDecision2);

        // flag-to-decision is the 1st priority

        $decision = $optUserContext->decide('boolean_feature', [OptimizelyDecideOption::INCLUDE_REASONS]);
        $this->assertEquals("test_variation_1", $decision->getVariationKey());
        $this->assertNull($decision->getRuleKey());
        $this->assertEquals([
            'Decided by forced decision.',
            'Variation (test_variation_1) is mapped to flag (boolean_feature) and user (test_user) in the forced decision map.'
        ], $decision->getReasons());
    }

    public function testGetForcedDecision()
    {
        $featureKey = "boolean_feature";
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];

        $validOptlyObject = new Optimizely($this->datafile);

        $optUserContext = new OptimizelyUserContext($validOptlyObject, $userId, $attributes);
        $contextWithNullRuleKey = new OptimizelyDecisionContext($featureKey, null);
        $decision = new OptimizelyForcedDecision("test_variation_1");
        $this->assertTrue($optUserContext->setForcedDecision($contextWithNullRuleKey, $decision));
        $this->assertEquals("test_variation_1", $optUserContext->getForcedDecision($contextWithNullRuleKey));

        $context = new OptimizelyDecisionContext($featureKey, null);
        $decision = new OptimizelyForcedDecision("test_variation_2");
        $this->assertTrue($optUserContext->setForcedDecision($context, $decision));
        $this->assertEquals("test_variation_2", $optUserContext->getForcedDecision($context));

        $context = new OptimizelyDecisionContext($featureKey, "test_experiment_2");
        $decision = new OptimizelyForcedDecision("test_variation_1");
        $this->assertTrue($optUserContext->setForcedDecision($context, $decision));
        $this->assertEquals("test_variation_1", $optUserContext->getForcedDecision($context));

        $context = new OptimizelyDecisionContext($featureKey, "test_experiment_2");
        $decision = new OptimizelyForcedDecision("test_variation_2");
        $this->assertTrue($optUserContext->setForcedDecision($context, $decision));
        $this->assertEquals("test_variation_2", $optUserContext->getForcedDecision($context));

        $this->assertEquals("test_variation_2", $optUserContext->getForcedDecision($contextWithNullRuleKey));
    }

    public function testRemoveForcedDecision()
    {
        $featureKey = "boolean_feature";
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];

        $validOptlyObject = new Optimizely($this->datafile);

        $optUserContext = new OptimizelyUserContext($validOptlyObject, $userId, $attributes);
        $contextWithFlag = new OptimizelyDecisionContext($featureKey, null);
        $decisionForFlag = new OptimizelyForcedDecision("test_variation_1");
        $contextWithRule = new OptimizelyDecisionContext($featureKey, "test_experiment_2");
        $decisionForRule = new OptimizelyForcedDecision("test_variation_2");
        $this->assertTrue($optUserContext->setForcedDecision($contextWithFlag, $decisionForFlag));
        $this->assertTrue($optUserContext->setForcedDecision($contextWithRule, $decisionForRule));

        $this->assertEquals("test_variation_1", $optUserContext->getForcedDecision($contextWithFlag));
        $this->assertEquals("test_variation_2", $optUserContext->getForcedDecision($contextWithRule));

        $this->assertTrue($optUserContext->removeForcedDecision($contextWithFlag));
        $this->assertNull($optUserContext->getForcedDecision($contextWithFlag));
        $this->assertEquals("test_variation_2", $optUserContext->getForcedDecision($contextWithRule));

        $this->assertTrue($optUserContext->removeForcedDecision($contextWithRule));
        $this->assertNull($optUserContext->getForcedDecision($contextWithRule));
        $this->assertNull($optUserContext->getForcedDecision($contextWithFlag));

        $this->assertFalse($optUserContext->removeForcedDecision($contextWithFlag));  // no more saved decisions
    }

    public function testRemoveAllForcedDecisions()
    {
        $featureKey = "boolean_feature";
        $userId = 'test_user';
        $attributes = [ "browser" => "chrome"];

        $validOptlyObject = new Optimizely($this->datafile);

        $optUserContext = new OptimizelyUserContext($validOptlyObject, $userId, $attributes);
        $this->assertTrue($optUserContext->removeAllForcedDecisions()); // no saved decisions

        $contextWithFlag = new OptimizelyDecisionContext($featureKey, null);
        $decisionForFlag = new OptimizelyForcedDecision("test_variation_1");
        $contextWithRule = new OptimizelyDecisionContext($featureKey, "test_experiment_2");
        $decisionForRule = new OptimizelyForcedDecision("test_variation_2");
        $this->assertTrue($optUserContext->setForcedDecision($contextWithFlag, $decisionForFlag));
        $this->assertTrue($optUserContext->setForcedDecision($contextWithRule, $decisionForRule));

        $this->assertEquals("test_variation_1", $optUserContext->getForcedDecision($contextWithFlag));
        $this->assertEquals("test_variation_2", $optUserContext->getForcedDecision($contextWithRule));

        $this->assertTrue($optUserContext->removeAllForcedDecisions());
        $this->assertNull($optUserContext->getForcedDecision($contextWithRule));
        $this->assertNull($optUserContext->getForcedDecision($contextWithFlag));

        $this->assertTrue($optUserContext->removeAllForcedDecisions()); // no more saved decisions
    }
}
