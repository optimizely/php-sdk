<?php

namespace Optimizely\BugBash;

require_once '../vendor/autoload.php';
require_once '../bug-bash/_bug-bash-autoload.php';

use Monolog\Logger;
use Optimizely\Logger\DefaultLogger;
use Optimizely\Notification\NotificationType;
use Optimizely\Optimizely;
use Optimizely\OptimizelyFactory;
use Optimizely\OptimizelyUserContext;

// 1. Change this SDK key to your project's SDK Key
const SDK_KEY = 'K4UmaV5Pk7cEh2hbcjgwe';

// 2. Add an event to your project, adding it to your Experiment flag as a metric, then set the key here
const EVENT_KEY = 'version_presented';

// 2. Uncomment each scenario 1 by 1 modifying the contents of the method
// to test additional scenarios.

$test = new ForcedDecisionTests();

// 3. Change the current folder into the bug-bash directory if you've not already
// cd bug-bash/

// 4. Run the following command to execute the uncommented tests above:
// php ForcedDecision.php

class ForcedDecisionTests
{

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
}
