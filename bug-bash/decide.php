<?php

namespace Optimizely\BugBash;

require_once '../vendor/autoload.php';
require_once '../bug-bash/bug-bash-autoload.php';

use Monolog\Logger;
use Optimizely\Decide\OptimizelyDecideOption;
use Optimizely\Logger\DefaultLogger;
use Optimizely\Notification\NotificationType;
use Optimizely\Optimizely;
use Optimizely\OptimizelyFactory;
use Optimizely\OptimizelyUserContext;

// 1. Change this SDK key to your project's SDK Key
const SDK_KEY = 'K4UmaV5Pk7cEh2hbcjgwe';

// 2. Change this to your flag key
const FLAG_KEY = 'product_sort';

// 3. Uncomment each scenario 1 by 1 modifying the contents of the method
// to test additional scenarios.

$test = new DecideTests();
$test->verifyDecisionProperties();
// $test->testWithVariationsOfDecideOptions();
// $test->verifyLogsImpressionsEventsDispatched();
// $test->verifyResultsPageInYourProjectShowsImpressionEvent();
// $test->verifyDecisionListenerWasCalled();
// $test->verifyAnInvalidFlagKeyIsHandledCorrectly();

// 4. Change directory into ./bug-bash 
// cd ./bug-bash/

// 5. Run the following command to execute the uncommented tests above:
// php decide.php

class DecideTests
{
    // verify decision return properties with default DecideOptions
    public function verifyDecisionProperties(): void
    {
        $decision = $this->userContext->decide(FLAG_KEY);

        $this->printDecision($decision, 'Check that the following decision properties are expected');
    }

    // test decide w all options: DISABLE_DECISION_EVENT, ENABLED_FLAGS_ONLY, IGNORE_USER_PROFILE_SERVICE, INCLUDE_REASONS, EXCLUDE_VARIABLES (will need to add variables)
    public function testWithVariationsOfDecideOptions(): void
    {
        $options = [
            OptimizelyDecideOption::INCLUDE_REASONS,
//            OptimizelyDecideOption::DISABLE_DECISION_EVENT,
//            OptimizelyDecideOption::ENABLED_FLAGS_ONLY,
//            OptimizelyDecideOption::IGNORE_USER_PROFILE_SERVICE,
//            OptimizelyDecideOption::EXCLUDE_VARIABLES,
        ];

        $decision = $this->userContext->decide(FLAG_KEY, $options);

        $this->printDecision($decision, 'Modify the OptimizelyDecideOptions and check the decision variables expected');
    }

    // verify in logs that impression event of this decision was dispatched
    public function verifyLogsImpressionsEventsDispatched(): void
    {
        // Create a new flag with an A/B Test eg "product_version"
        $featureFlagKey = 'product_version';
        $logger = new DefaultLogger(Logger::DEBUG);
        $localOptimizelyClient = new Optimizely(datafile: null, logger: $logger, sdkKey: SDK_KEY);
        $userId = 'user-' . mt_rand(10, 99);
        $localUserContext = $localOptimizelyClient->createUserContext($userId);

        // review the DEBUG output, ensuring you see an impression log
        // "Dispatching impression event to URL https://logx.optimizely.com/v1/events with params..."
        $localUserContext->decide($featureFlagKey);
    }

    // verify on Results page that impression even was created
    public function verifyResultsPageInYourProjectShowsImpressionEvent(): void
    {
        print "Go to your project's results page and verify decisions events are showing (might be a 5 min delay)";
    }

    // verify that decision listener contains correct information
    public function verifyDecisionListenerWasCalled(): void
    {
        // Check that this was called during the...
        $onDecision = function ($type, $userId, $attributes, $decisionInfo) {
            print ">>> [NotificationCenter] OnDecision:
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

    private function printDecision($decision, $message): void
    {
        print ">>> [Decision] $message: 
            enabled: {$decision->getEnabled()}, 
            flagKey: {$decision->getFlagKey()}, 
            ruleKey: {$decision->getRuleKey()}, 
            variationKey: {$decision->getVariationKey()}, 
            variables: " . print_r($decision->getVariables(), true) . ", 
            reasons: " . print_r($decision->getReasons(), true) . "\r\n";
    }

    private Optimizely $optimizelyClient;
    private ?OptimizelyUserContext $userContext;
    private array $options;

    public function __construct()
    {
        $this->optimizelyClient = OptimizelyFactory::createDefaultInstance(SDK_KEY);

        $userId = 'user-' . mt_rand(10, 99);
        $attributes = ['age' => 11, 'country' => 'usa'];
        $this->userContext = $this->optimizelyClient->createUserContext($userId, $attributes);
    }
}
