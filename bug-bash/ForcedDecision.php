<?php

namespace Optimizely\BugBash;

require_once '../vendor/autoload.php';
require_once '../bug-bash/_bug-bash-autoload.php';

use Optimizely\Decide\OptimizelyDecideOption;
use Optimizely\Optimizely;
use Optimizely\OptimizelyDecisionContext;
use Optimizely\OptimizelyFactory;
use Optimizely\OptimizelyForcedDecision;
use Optimizely\OptimizelyUserContext;

// 1. Change this SDK key to your project's SDK Key
const SDK_KEY = '<your-sdk-key>';

// 2. Add a Targeted Delivery to your project, then flag key here
const TARGETED_DELIVERY_FLAG_KEY = '<your-delivery-flag-key>';

// 3. Add a Targeted Delivery to your project, then flag key here
const AB_EXPERIMENT_FLAG_KEY = '<your-experiment-flag-key>';

// 4. Uncomment each scenario 1 by 1 modifying the contents of the method
// to test additional scenarios.

$test = new ForcedDecisionTests();
$test->forcedDecisionForAFlag();
// $test->forcedDecisionForExperiment();
// $test->forcedDecisionForDeliveryRule();
// $test->getForcedDecision();
// $test->removeSingleForcedDecisions();
// $test->removeAllForcedDecisions();

// 5. Change the current folder into the bug-bash directory if you've not already
// cd bug-bash/

// 6. Run the following command to execute the uncommented tests above:
// php ForcedDecision.php

// https://docs.developers.optimizely.com/feature-experimentation/docs/forced-decision-methods-php
class ForcedDecisionTests
{
    // set a forced decision for a flag
    public function forcedDecisionForAFlag(): void
    {
        $maybeARandomFlagKey = "maybe_a_random_flag_key";
        $decisionContext = new OptimizelyDecisionContext($maybeARandomFlagKey, "some_rule_key");
        $forceDecision = new OptimizelyForcedDecision("some_variation_key");
        $this->userContext->setForcedDecision($decisionContext, $forceDecision);

        // code under test
        $decision = $this->userContext->decide($maybeARandomFlagKey, [OptimizelyDecideOption::INCLUDE_REASONS]);

        $this->printDecision($decision, "Check your expected decision properties for this flag");
    }

    // set a forced decision for an ab-test experiment rule
    public function forcedDecisionForExperiment(): void
    {
        $decisionContext = new OptimizelyDecisionContext(AB_EXPERIMENT_FLAG_KEY, "some_rule_key");
        $forceDecision = new OptimizelyForcedDecision("some_variation_key");
        $this->userContext->setForcedDecision($decisionContext, $forceDecision);

        // code under test
        $decision = $this->userContext->decide(AB_EXPERIMENT_FLAG_KEY, [OptimizelyDecideOption::INCLUDE_REASONS]);

        $this->printDecision($decision, "Check your expected decision properties for this experiment rule");
    }

    // set a forced variation for a delivery rule
    public function forcedDecisionForDeliveryRule(): void
    {
        $decisionContext = new OptimizelyDecisionContext(TARGETED_DELIVERY_FLAG_KEY, "some_rule_key");
        $forceDecision = new OptimizelyForcedDecision("some_variation_key");
        $this->userContext->setForcedDecision($decisionContext, $forceDecision);

        // code under test
        $decision = $this->userContext->decide(TARGETED_DELIVERY_FLAG_KEY, [OptimizelyDecideOption::INCLUDE_REASONS]);

        $this->printDecision($decision, "Check your expected decision properties for this delivery rule");
    }

    // get forced variations
    public function getForcedDecision(): void
    {
        $decisionContext = new OptimizelyDecisionContext(AB_EXPERIMENT_FLAG_KEY, "some_rule_key");
        $forceDecision = new OptimizelyForcedDecision("some_variation_key");
        $this->userContext->setForcedDecision($decisionContext, $forceDecision);

        $retrievedForcedDecision = $this->userContext->getForcedDecision($decisionContext); // code under test

        print ">>> $this->outputTag Get Forced Variation = $retrievedForcedDecision";
    }

    // remove forced variations
    public function removeSingleForcedDecisions(): void
    {
        $forcedDecisionThatWillBeRemoved = new OptimizelyForcedDecision("some_variation_key");
        $forcedDecisionThatShouldBeKept = new OptimizelyForcedDecision("some_other_variation_key");
        $this->userContext->setForcedDecision(
            new OptimizelyDecisionContext(AB_EXPERIMENT_FLAG_KEY, "some_rule_key"),
            $forcedDecisionThatWillBeRemoved
        );
        $this->userContext->setForcedDecision(
            new OptimizelyDecisionContext(TARGETED_DELIVERY_FLAG_KEY, "some_rule_key"),
            $forcedDecisionThatShouldBeKept
        );

        $this->userContext->removeForcedDecision($forcedDecisionThatWillBeRemoved); // code under test
        $removed = $this->userContext->getForcedDecision($forcedDecisionThatWillBeRemoved);
        $kept = $this->userContext->getForcedDecision($forcedDecisionThatShouldBeKept);

        // This forced decision should return null because we just removed it
        print ">>> $this->outputTag Removed forced decision should be null: $removed";

        // This forced decision should return since we did not remove it
        print ">>> $this->outputTag Kept forced decision not be null = $kept";
    }

    public function removeAllForcedDecisions(): void
    {
        $forcedDecisionThatShouldBeRemoved = new OptimizelyForcedDecision("some_variation_key");
        $forcedDecisionThatShouldAlsoBeRemoved = new OptimizelyForcedDecision("some_other_variation_key");
        $this->userContext->setForcedDecision(
            new OptimizelyDecisionContext(AB_EXPERIMENT_FLAG_KEY, "some_rule_key"),
            $forcedDecisionThatShouldBeRemoved
        );
        $this->userContext->setForcedDecision(
            new OptimizelyDecisionContext(TARGETED_DELIVERY_FLAG_KEY, "some_rule_key"),
            $forcedDecisionThatShouldAlsoBeRemoved
        );

        $this->userContext->removeAllForcedDecisions(); // code under test
        $wasRemoved = $this->userContext->getForcedDecision($forcedDecisionThatShouldBeRemoved);
        $alsoRemoved = $this->userContext->getForcedDecision($forcedDecisionThatShouldAlsoBeRemoved);

        // This forced decision should return null because we just removed it
        print ">>> $this->outputTag Forced decision should be null since it was removed: $wasRemoved";

        // This forced decision should return since we did not remove it
        print ">>> $this->outputTag Second forced decision should also be null since it too was removed = $alsoRemoved";
    }

    private Optimizely $optimizelyClient;
    private string $userId;
    private ?OptimizelyUserContext $userContext;
    private string $outputTag = "Forced Decision";

    public function __construct()
    {
        $this->optimizelyClient = OptimizelyFactory::createDefaultInstance(SDK_KEY);

        $this->userId = 'user-' . mt_rand(10, 99);
        $attributes = ['eats_vegetables' => true, 'age' => 7, 'favorite_vegetable' => 'turnips'];
        $this->userContext = $this->optimizelyClient->createUserContext($this->userId, $attributes);
    }

    private function printDecision($decision, $message): void
    {
        $enabled = $decision->getEnabled() ? "true" : "false";

        print ">>> [$this->outputTag] $message: 
            enabled: $enabled, 
            flagKey: {$decision->getFlagKey()}, 
            ruleKey: {$decision->getRuleKey()}, 
            variationKey: {$decision->getVariationKey()}, 
            variables: " . print_r($decision->getVariables(), true) . ", 
            reasons: " . print_r($decision->getReasons(), true) . "\r\n";
    }
}
