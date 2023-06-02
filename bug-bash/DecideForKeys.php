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

// 2. Check that you have 3+ flag keys in your project and add them here
const FLAG_KEYS = ['product_sort', 'marketing_banner'];

// 3. Uncomment each scenario 1 by 1 modifying the contents of the method
// to test additional scenarios.

$test = new DecideForKeysTests();


// 4. Change the current folder into the bug-bash directory if you've not already
// cd bug-bash/

// 5. Run the following command to execute the uncommented tests above:
// php DecideForKeys.php

class DecideForKeysTests
{

    private Optimizely $optimizelyClient;
    private string $userId;
    private ?OptimizelyUserContext $userContext;

    public function __construct()
    {
        $this->optimizelyClient = OptimizelyFactory::createDefaultInstance(SDK_KEY);

        $this->userId = 'user-' . mt_rand(10, 99);
        $attributes = ['age' => 11, 'country' => 'usa'];
        $this->userContext = $this->optimizelyClient->createUserContext($this->userId, $attributes);
    }

    private function printDecisions($decisions, $message): void
    {
        $count = 0;
        foreach ($decisions as $decision) {
            $enabled = $decision->getEnabled() ? "true" : "false";

            print ">>> [Decision #$count] $message: 
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
