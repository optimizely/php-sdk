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

// 2. Uncomment each scenario 1 by 1 modifying the contents of the method
// to test additional scenarios.

$test = new TrackEventTests();

// 3. Change the current folder into the bug-bash directory if you've not already
// cd bug-bash/

// 4. Run the following command to execute the uncommented tests above:
// php TrackEvent.php

class TrackEventTests
{

    private Optimizely $optimizelyClient;
    private string $userId;
    private ?OptimizelyUserContext $userContext;
    private string $outputTag = "Track Event";

    public function __construct()
    {
        $this->optimizelyClient = OptimizelyFactory::createDefaultInstance(SDK_KEY);

        $this->userId = 'user-' . mt_rand(10, 99);
        $attributes = ['age' => 11, 'country' => 'usa'];
        $this->userContext = $this->optimizelyClient->createUserContext($this->userId, $attributes);
    }
}
