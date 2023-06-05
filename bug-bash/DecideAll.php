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
const SDK_KEY = 'K4UmaV5Pk7cEh2hbcjgwe';

// 2. Create additional flag keys in your project (2+)

// 3. Uncomment each scenario 1 by 1 modifying the contents of the method
// to test additional scenarios.

$test = new DecideAllTests();
$test->verifyDecisionProperties();
// $test->testWithVariousCombinationsOfOptions();
// $test->verifyLogImpressionEventDispatched();
// $test->verifyResultsPageShowsImpressionEvents();
// $test->verifyDecisionListenerContainsCorrectInformation();

// 4. Change the current folder into the bug-bash directory if you're not already there:
// cd bug-bash/

// 5. Run the following command to execute the uncommented tests above:
// php DecideAll.php

class DecideAllTests
{
    // verify decide all returns properties without specifying default options
    public function verifyDecisionProperties(): void
    {
        $decision = $this->userContext->decideAll();

        $this->printDecisions($decision, "Check that all of the decisions' multiple properties are expected for user `$this->userId`");
    }

    // test with all and variations/combinations of options
    public function testWithVariousCombinationsOfOptions(): void
    {
        $options = [
            OptimizelyDecideOption::INCLUDE_REASONS,
            // OptimizelyDecideOption::DISABLE_DECISION_EVENT,
            // OptimizelyDecideOption::ENABLED_FLAGS_ONLY,  //  ⬅️ Disable some of your flags
            // OptimizelyDecideOption::IGNORE_USER_PROFILE_SERVICE,
            OptimizelyDecideOption::EXCLUDE_VARIABLES,
        ];

        $decisions = $this->userContext->decideAll($options);

        $this->printDecisions($decisions, "Check that all of your flags' decisions respected the options passed.");
    }

    // verify in logs that impression event of this decision was dispatched
    public function verifyLogImpressionEventDispatched(): void
    {
        // 💡️ Be sure you have >=1 of your project's flags has an EXPERIMENT type
        $logger = new DefaultLogger(Logger::DEBUG);
        $localOptimizelyClient = new Optimizely(datafile: null, logger: $logger, sdkKey: SDK_KEY);
        $localUserContext = $localOptimizelyClient->createUserContext($this->userId);

        // review the DEBUG output, ensuring you see an impression log for each *EXPERIMENT* with a message like
        // "Dispatching impression event to URL https://logx.optimizely.com/v1/events with params..."
        // ⚠️ Rollout flag types should not dispatch and impression event
        $localUserContext->decideAll();
    }

    // verify on Results page that impression events was created
    public function verifyResultsPageShowsImpressionEvents(): void
    {
        print "After about 5-10 minutes, go to your project's results page and verify decisions events are showing.";
    }

    // verify that decision listener contains correct information
    public function verifyDecisionListenerContainsCorrectInformation(): void
    {
        // Check that this was called for each of your project flag keys
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

        $this->userContext->decideAll();
    }

    private Optimizely $optimizelyClient;
    private string $userId;
    private ?OptimizelyUserContext $userContext;
    private string $outputTag = "Decide All";

    public function __construct()
    {
        $this->optimizelyClient = OptimizelyFactory::createDefaultInstance(SDK_KEY);

        $this->userId = 'user-' . mt_rand(10, 99);
        $attributes = ['country' => 'nederland', 'age' => 43, 'is_return_visitor' => true];
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
