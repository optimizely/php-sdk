<?php

namespace Optimizely\BugBash;

require_once '../vendor/autoload.php';
require_once '../bug-bash/_bug-bash-autoload.php';

use Monolog\Logger;
use Optimizely\Decide\OptimizelyDecideOption;
use Optimizely\Logger\DefaultLogger;
use Optimizely\Notification\NotificationType;
use Optimizely\Optimizely;
use Optimizely\OptimizelyFactory;
use Optimizely\OptimizelyUserContext;

// 1. Change this SDK key to your project's SDK Key
const SDK_KEY = '<your-sdk-key>';

// 2. Check that you have 3+ flag keys in your project and add them here
const FLAG_KEYS = ['<your-flag-key-1>', '<your-flag-key-2>', '<your-flag-key-3>'];

// 3. Uncomment each scenario 1 by 1 modifying the contents of the method
// to test additional scenarios.

$test = new DecideForKeysTests();
$test->verifyDecisionProperties();
// $test->testWithVariationsOfDecideOptions();
// $test->verifyLogsImpressionsEventsDispatched();
// $test->verifyResultsPageInYourProjectShowsImpressionEvent();
// $test->verifyDecisionListenerWasCalled();
// $test->verifyAnInvalidFlagKeyIsHandledCorrectly();

// 4. Change the current folder into the bug-bash directory if you've not already
// cd bug-bash/

// 5. Run the following command to execute the uncommented tests above:
// php DecideForKeys.php

// https://docs.developers.optimizely.com/feature-experimentation/docs/decide-methods-php
class DecideForKeysTests
{

    // verify decision return properties with default DecideOptions
    public function verifyDecisionProperties(): void
    {
        $decision = $this->userContext->decideForKeys(FLAG_KEYS);

        $this->printDecisions($decision, "Check that the following decisions' properties are expected");
    }

    // test decide w all options: DISABLE_DECISION_EVENT, ENABLED_FLAGS_ONLY, IGNORE_USER_PROFILE_SERVICE, INCLUDE_REASONS, EXCLUDE_VARIABLES (will need to add variables)
    public function testWithVariationsOfDecideOptions(): void
    {
        $options = [
            OptimizelyDecideOption::INCLUDE_REASONS,
            // OptimizelyDecideOption::DISABLE_DECISION_EVENT,
            // OptimizelyDecideOption::ENABLED_FLAGS_ONLY,   //  ⬅️ Disable some of your flags
            // OptimizelyDecideOption::IGNORE_USER_PROFILE_SERVICE,
            // OptimizelyDecideOption::EXCLUDE_VARIABLES,
        ];

        $decision = $this->userContext->decideForKeys(FLAG_KEYS, $options);

        $this->printDecisions($decision, "Modify the OptimizelyDecideOptions and check all the decisions' are as expected");
    }

    // verify in logs that impression event of this decision was dispatched
    public function verifyLogsImpressionsEventsDispatched(): void
    {
        // 💡️ Be sure that your FLAG_KEYS array above includes A/B Test eg "product_version"
        $logger = new DefaultLogger(Logger::DEBUG);
        $localOptimizelyClient = new Optimizely(datafile: null, logger: $logger, sdkKey: SDK_KEY);
        $localUserContext = $localOptimizelyClient->createUserContext($this->userId);

        // review the DEBUG output, ensuring you see an impression log for each experiment type in your FLAG_KEYS
        // "Dispatching impression event to URL https://logx.optimizely.com/v1/events with params..."
        // ⚠️ Your Rollout type flags should not have impression events
        $localUserContext->decideForKeys(FLAG_KEYS);
    }

    // verify on Results page that impression even was created
    public function verifyResultsPageInYourProjectShowsImpressionEvent(): void
    {
        print "Go to your project's results page and verify decisions events are showing (5 min delay)";
    }

    // verify that decision listener contains correct information
    public function verifyDecisionListenerWasCalled(): void
    {
        // Check that this was called during the...
        $onDecision = function ($type, $userId, $attributes, $decisionInfo) {
            print ">>> [$this->outputTag] OnDecision:
            type: $type,
            userId: $userId,
            attributes: " . print_r($attributes, true) . "
            decisionInfo: " . print_r($decisionInfo, true) . "\r\n";
        };
        $this->optimizelyClient->notificationCenter->addNotificationListener(
            NotificationType::DECISION,
            $onDecision
        );

        // ...decide.
        $this->userContext->decide(FLAG_KEY);
    }

    // verify that invalid flag key is handled correctly
    public function verifyAnInvalidFlagKeyIsHandledCorrectly(): void
    {
        $logger = new DefaultLogger(Logger::ERROR);
        $localOptimizelyClient = new Optimizely(datafile: null, logger: $logger, sdkKey: SDK_KEY);
        $userId = 'user-' . mt_rand(10, 99);
        $localUserContext = $localOptimizelyClient->createUserContext($userId);

        // ensure you see an error -- Optimizely.ERROR: FeatureFlag Key "a_key_not_in_the_project" is not in datafile.
        $localUserContext->decide("a_key_not_in_the_project");
    }

    private Optimizely $optimizelyClient;
    private string $userId;
    private ?OptimizelyUserContext $userContext;
    private string $outputTag = "Decide For Keys";

    public function __construct()
    {
        $this->optimizelyClient = OptimizelyFactory::createDefaultInstance(SDK_KEY);

        $this->userId = 'user-' . mt_rand(10, 99);
        $attributes = ['likes_yams' => true, 'cart_value' => 34.13, 'country' => 'sweden'];
        $this->userContext = $this->optimizelyClient->createUserContext($this->userId, $attributes);
    }

    private function printDecisions($decisions, $message): void
    {
        $count = 0;
        foreach ($decisions as $decision) {
            $enabled = $decision->getEnabled() ? "true" : "false";

            print ">>> [$this->outputTag #$count] $message: 
                enabled: $enabled, 
                flagKey: {$decision->getFlagKey()}, 
                ruleKey: {$decision->getRuleKey()}, 
                variationKey: {$decision->getVariationKey()}, 
                variables: " . print_r($decision->getVariables(), true) . ", 
                reasons: " . print_r($decision->getReasons(), true) . "\r\n";

            $count++;
        }
    }
}
