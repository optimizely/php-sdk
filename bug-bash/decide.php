<?php

namespace Optimizely\BugBash;

require_once 'bug-bash-autoload.php';

use Optimizely\Decide\OptimizelyDecideOption;
use Optimizely\Optimizely;
use Optimizely\OptimizelyUserContext;

// 1. Change this SDK key to your project's SDK Key
const SDK_KEY = "K4UmaV5Pk7cEh2hbcjgwe";

// 2. Uncomment each scenario 1 by 1 modifying the contents of the method
// to test additional scenarios.

$test = new DecideTests();
$test->verifyDecisionProperties();
//$test->testWithVariationsOfDecideOptions();
//$test->verifyLogsImpressionsEventsDispatched();
//$test->verifyResultsPageInYourProjectShowsImpressionEvent();
//$test->verifyDecisionListenerWasCalled();
//$test->verifyAnInvalidFlagKeyIsHandledCorrectly();

// 3. Run the following command to execute uncommented test:
// php ./bug-bash/decide.php

class DecideTests
{
    private Optimizely $optimizelyClient;
    private ?OptimizelyUserContext $userContext;
    private array $options;

    public function __construct()
    {
        $this->optimizelyClient = new Optimizely(datafile:null, sdkKey: SDK_KEY);

        $userId = 'user-' . mt_rand(10, 99);
        $attributes = ['age' => 11, 'country' => 'usa'];
        $this->userContext = $this->optimizelyClient->createUserContext($userId, $attributes);
    }

    // verify decision return properties with default DecideOptions
    public function verifyDecisionProperties(): void
    {
        $decision = $this->userContext->decide('product_sort');

        print  "{${self::LOG_TAG}} [Decide] Check that the following decision properties are expected: 
            {$decision->getEnabled()}, 
            {$decision->getFlagKey()}, 
            {$decision->getRuleKey()}, 
            {$decision->getUserContext()}, 
            {$decision->getVariationKey()}, 
            {$decision->getVariables()}, 
            {$decision->getReasons()}";
    }

    //   test decide w all options: DISABLE_DECISION_EVENT, ENABLED_FLAGS_ONLY, IGNORE_USER_PROFILE_SERVICE, INCLUDE_REASONS, EXCLUDE_VARIABLES (will need to add variables)
    public function testWithVariationsOfDecideOptions(): void
    {
        $options = [
            OptimizelyDecideOption::INCLUDE_REASONS,
            // OptimizelyDecideOption::DISABLE_DECISION_EVENT,
            // OptimizelyDecideOption::ENABLED_FLAGS_ONLY,
            // OptimizelyDecideOption::IGNORE_USER_PROFILE_SERVICE,
            // OptimizelyDecideOption::EXCLUDE_VARIABLES,
        ];

        $decision = $this->userContext->decide('product_sort', $options);

        print  "{${self::LOG_TAG}} [Decide] DECISION 1: 
            {$decision->getEnabled()}, 
            {$decision->getFlagKey()}, 
            {$decision->getRuleKey()}, 
            {$decision->getUserContext()}, 
            {$decision->getVariationKey()}, 
            {$decision->getVariables()}, 
            {$decision->getReasons()}";
    }

    //   verify in logs that impression event of this decision was dispatched
    public function verifyLogsImpressionsEventsDispatched(): void
    {

    }

    //   verify on Results page that impression even was created
    public function verifyResultsPageInYourProjectShowsImpressionEvent(): void
    {
        printf('%s Go to your project\'s results page and verify the decision events', self::LOG_TAG);
    }

    //   verify that decision listener contains correct information
    public function verifyDecisionListenerWasCalled(): void
    {

    }

    //   verify that invalid flag key is handled correctly
    public function verifyAnInvalidFlagKeyIsHandledCorrectly(): void
    {

    }

    const LOG_TAG = '>>>';
}
