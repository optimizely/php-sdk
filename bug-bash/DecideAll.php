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

// 2. Change this to your flag key
const FLAG_KEY = 'product_sort';

// 3. Uncomment each scenario 1 by 1 modifying the contents of the method
// to test additional scenarios.

$test = new DecideAllTests();
$test->verifyDecisionProperties();
$test->testDefaultDecideAllOptions();
$test->testWithAllOptions();
$test->verifyLogImpressionEventDispatched();
$test->verifyResultsPageShowsImpressionEvents();
$test->verifyDecisionListenerContainsCorrectInformation();
$test->verifyInvalidFlagsAreHandled();

// 4. Change the current folder into the bug-bash directory if you're not already there:
// cd bug-bash/

// 5. Run the following command to execute the uncommented tests above:
// php DecideAll.php

class DecideAllTests
{
    // verify decision properties
    public function verifyDecisionProperties(): void
    {

    }

    // test with default options
    public function testDefaultDecideAllOptions(): void
    {

    }

    // test with all options
    public function testWithAllOptions(): void
    {

    }

    // verify in logs that impression event of this decision was dispatched
    public function verifyLogImpressionEventDispatched(): void
    {

    }

    // verify on Results page that impression events was created
    public function verifyResultsPageShowsImpressionEvents(): void
    {

    }

    // verify that decision listener contains correct information
    public function verifyDecisionListenerContainsCorrectInformation(): void
    {

    }

    // verify that invalid flag key is handled
    public function verifyInvalidFlagsAreHandled(): void
    {

    }

    private Optimizely $optimizelyClient;
    private string $userId;
    private ?OptimizelyUserContext $userContext;
    private array $options;

    public function __construct()
    {
        $this->optimizelyClient = OptimizelyFactory::createDefaultInstance(SDK_KEY);

        $this->userId = 'user-' . mt_rand(10, 99);
        $attributes = ['age' => 11, 'country' => 'usa'];
        $this->userContext = $this->optimizelyClient->createUserContext($this->userId, $attributes);
    }
}
