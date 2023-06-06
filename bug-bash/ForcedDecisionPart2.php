<?php
namespace Optimizely\BugBash;

require_once '../vendor/autoload.php';
require_once '../bug-bash/_bug-bash-autoload.php';
// fetch the datafile from an authenticated endpoint
use Optimizely\Optimizely;
use Optimizely\OptimizelyDecisionContext;
use Optimizely\OptimizelyForcedDecision;
use Optimizely\Decide\OptimizelyDecideOption;

$optimizelyClient = new Optimizely(null, null, null, null, null, null, null, null, "X9mZd2WDywaUL9hZXyh9A");


$userId = 'user' . strval(rand(0, 1000001));

// PART 2 a)
echo '===================================';
echo 'F-to-D (no rule key specified):';
echo '==================================='.PHP_EOL;

echo '  Set user context, userId any, age = 20 (bucketed)'.PHP_EOL;
echo '  Call decide with flag1  ---> expected result is variation a'.PHP_EOL;
echo '  ---------------------'.PHP_EOL;

$user = $optimizelyClient->createUserContext($userId, array("age"=> 20));
$decideDecision = $user->decide('flag1', [OptimizelyDecideOption::INCLUDE_REASONS]);
echo '    VARIATION   >>>  ' . $decideDecision->getVariationKey() . PHP_EOL;
// ASSERT YOU GET CORRECT VARIATION
echo '    REASONS     >>>  ' . json_encode($decideDecision->getReasons()) . PHP_EOL;
// VERIFY REASONS ARE CORRECT
assert('variation_a' == $decideDecision->getVariationKey());

echo PHP_EOL . '  Set forced decision w flag1 and variation b' . PHP_EOL;
echo '  Call decide -----> expected variation b in decide decision' . PHP_EOL;
echo '  ---------------------';

$context = new OptimizelyDecisionContext('flag1', null);
$decision = new OptimizelyForcedDecision('variation_b');
$user->setForcedDecision($context, $decision);
$decideDecision = $user->decide('flag1', [OptimizelyDecideOption::INCLUDE_REASONS]);
echo '    VARIATION   >>>  ' . $decideDecision->getVariationKey() . PHP_EOL;
// ASSERT YOU GET CORRECT VARIATION
echo '    REASONS     >>>  ' . json_encode($decideDecision->getReasons()) . PHP_EOL;
// VERIFY REASONS ARE CORRECT
assert ('variation_b' == $decideDecision->getVariationKey());

echo PHP_EOL . '  Set forced decision with flag1 and variation c (invalid)' . PHP_EOL;
echo '  Call decide  ---->  expected variation a' . PHP_EOL;
echo '  ---------------------' . PHP_EOL;
$context = new OptimizelyDecisionContext('flag1', null);
$decision = new OptimizelyForcedDecision('variation_c');
$user->setForcedDecision($context, $decision);
$decideDecision = $user->decide('flag1', [OptimizelyDecideOption::INCLUDE_REASONS]);
echo '    VARIATION   >>>  ' . $decideDecision->getVariationKey() . PHP_EOL;
// ASSERT YOU GET CORRECT VARIATION
echo '    REASONS     >>>  ' . json_encode($decideDecision->getReasons()) . PHP_EOL;
// VERIFY REASONS ARE CORRECT
assert ('variation_a' == $decideDecision->getVariationKey());

// E-to-D (rule key = “flag1_experiment”):
// -------------------------------------------
// Set user context, userId any, age = 20 (bucketed)
// Call decide with flag1  ---> expected result is variation a in the decide decision
// Set forced decision w flag1 and rule key “flag1_experiment”, and variation b
// Call decide -----> expected variation b in decide decision
// Set forced decision with flag1 and rule key “flag1_experiment” and invalid variation c
// Call decide  ---->  expected variation a

echo PHP_EOL . PHP_EOL . '===================================';
echo 'E-to-D (rule key = “flag1_experiment”):';
echo '==================================='.PHP_EOL;

echo '  Set user context, userId any, age = 20 (bucketed)'.PHP_EOL;
echo '  Call decide with flag1  ---> expected result is variation a'.PHP_EOL;
echo '  ---------------------'.PHP_EOL;

$decideDecision = $user->decide('flag1', [OptimizelyDecideOption::INCLUDE_REASONS]);
echo '    VARIATION   >>>  ' . $decideDecision->getVariationKey() . PHP_EOL;
// ASSERT YOU GET CORRECT VARIATION
echo '    REASONS     >>>  ' . json_encode($decideDecision->getReasons()) . PHP_EOL;
// VERIFY REASONS ARE CORRECT
assert ('variation_a' == $decideDecision->getVariationKey());

echo PHP_EOL . '  Set forced decision with flag1 and rule flag1_experiment and variation b' . PHP_EOL;
echo '  Call decide -----> expected variation_b in decide decision' . PHP_EOL;
echo '  ---------------------';

$context = new OptimizelyDecisionContext('flag1', 'flag1_experiment');
$decision = new OptimizelyForcedDecision('variation_b');
$user->setForcedDecision($context, $decision);
$decideDecision = $user->decide('flag1', [OptimizelyDecideOption::INCLUDE_REASONS]);
echo '    VARIATION   >>>  ' . $decideDecision->getVariationKey() . PHP_EOL;
// ASSERT YOU GET CORRECT VARIATION
echo '    REASONS     >>>  ' . json_encode($decideDecision->getReasons()) . PHP_EOL;
// VERIFY REASONS ARE CORRECT
assert ('variation_b' == $decideDecision->getVariationKey());

echo PHP_EOL . '  Set forced decision with flag1 and rule flag1_experiment and variation c (Invalid)' . PHP_EOL;
echo '  Call decide -----> expected variation a in decide decision' . PHP_EOL;
echo '  ---------------------';

$context = new OptimizelyDecisionContext('flag1', 'flag1_experiment');
$decision = new OptimizelyForcedDecision('variation_c');
$user->setForcedDecision($context, $decision);
$decideDecision = $user->decide('flag1', [OptimizelyDecideOption::INCLUDE_REASONS]);
echo '    VARIATION   >>>  ' . $decideDecision->getVariationKey() . PHP_EOL;
// ASSERT YOU GET CORRECT VARIATION
echo '    REASONS     >>>  ' . json_encode($decideDecision->getReasons()) . PHP_EOL;
// VERIFY REASONS ARE CORRECT
assert ('variation_a' == $decideDecision->getVariationKey());

// D-to-D (rule key = “flag1_targeted_delivery”):
// -------------------------------------------
// Set user context, userId any, country = “US” (bucketed)
// Call decide with flag1  ---> expected result is “on” in the decide decision
// Set forced decision w flag1 and rule key “flag1_targeted_delivery”, and variation b
// Call decide -----> expected variation b in decide decision
// Set forced decision with flag1 and rule key “flag1_targeted_delivery” and invalid variation c
// Call decide  ---->  expected “on”

echo PHP_EOL . PHP_EOL . '===================================';
echo 'D-to-D (rule key = “flag1_targeted_delivery”):';
echo '==================================='.PHP_EOL;

echo '  Set user context, userId any, country = US (bucketed)'.PHP_EOL;
echo '  Call decide with flag1  ---> expected result is on on'.PHP_EOL;
echo '  ---------------------'.PHP_EOL;

$user = $optimizelyClient->createUserContext($userId, array("country"=> "US"));
$decideDecision = $user->decide('flag1', [OptimizelyDecideOption::INCLUDE_REASONS]);
echo '    VARIATION   >>>  ' . $decideDecision->getVariationKey() . PHP_EOL;
// ASSERT YOU GET CORRECT VARIATION
echo '    REASONS     >>>  ' . json_encode($decideDecision->getReasons()) . PHP_EOL;
// VERIFY REASONS ARE CORRECT
assert ('on' == $decideDecision->getVariationKey());

echo PHP_EOL . '  Set forced decision with flag1 and rule “flag1_targeted_delivery” and variation b' . PHP_EOL;
echo '  Call decide -----> expected variation_b in decide decision' . PHP_EOL;
echo '  ---------------------';

$context = new OptimizelyDecisionContext('flag1', 'flag1_targeted_delivery');
$decision = new OptimizelyForcedDecision('variation_b');
$user->setForcedDecision($context, $decision);
$decideDecision = $user->decide('flag1', [OptimizelyDecideOption::INCLUDE_REASONS]);
echo '    VARIATION   >>>  ' . $decideDecision->getVariationKey() . PHP_EOL;
// ASSERT YOU GET CORRECT VARIATION
echo '    REASONS     >>>  ' . json_encode($decideDecision->getReasons()) . PHP_EOL;
// VERIFY REASONS ARE CORRECT
assert ('variation_b' == $decideDecision->getVariationKey());

echo PHP_EOL . '  Set forced decision with flag1 and rule flag1_targeted_delivery and variation c (Invalid)' . PHP_EOL;
echo '  Call decide -----> expected on in decide decision' . PHP_EOL;
echo '  ---------------------';

$context = new OptimizelyDecisionContext('flag1', 'flag1_targeted_delivery');
$decision = new OptimizelyForcedDecision('variation_c');
$user->setForcedDecision($context, $decision);
$decideDecision = $user->decide('flag1', [OptimizelyDecideOption::INCLUDE_REASONS]);
echo '    VARIATION   >>>  ' . $decideDecision->getVariationKey() . PHP_EOL;
// ASSERT YOU GET CORRECT VARIATION
echo '    REASONS     >>>  ' . json_encode($decideDecision->getReasons()) . PHP_EOL;
// VERIFY REASONS ARE CORRECT
assert ('on' == $decideDecision->getVariationKey());


// ================================
// PART 2 b) Repeat above three blocks, this time user DOES NOT meet audience conditions
// ================================

// F-to-D (no rule key specified):
// -------------------------------------------
// Set user context, userId any, age = 0 (not bucketed)
// Call decide with flag1  ---> expected result is “off” (everyone else)
// Set forced decision w flag1, variation b
// Call decide -----> expected variation b in decide decision

echo PHP_EOL . PHP_EOL . '===================================';
echo 'F-to-D (no rule key specified):';
echo '==================================='.PHP_EOL;

echo '  Set user context, userId any, age = 0 (not bucketed)'.PHP_EOL;
echo '  Call decide with flag1  ---> expected result is off'.PHP_EOL;
echo '  ---------------------'.PHP_EOL;

$user = $optimizelyClient->createUserContext($userId, array("age"=> 0));
$decideDecision = $user->decide('flag1', [OptimizelyDecideOption::INCLUDE_REASONS]);
echo '    VARIATION   >>>  ' . $decideDecision->getVariationKey() . PHP_EOL;
// ASSERT YOU GET CORRECT VARIATION
echo '    REASONS     >>>  ' . json_encode($decideDecision->getReasons()) . PHP_EOL;
// VERIFY REASONS ARE CORRECT
assert('off' == $decideDecision->getVariationKey());

echo PHP_EOL . '  Set forced decision w flag1 and variation b' . PHP_EOL;
echo '  Call decide -----> expected variation b in decide decision' . PHP_EOL;
echo '  ---------------------';

$context = new OptimizelyDecisionContext('flag1', null);
$decision = new OptimizelyForcedDecision('variation_b');
$user->setForcedDecision($context, $decision);
$decideDecision = $user->decide('flag1', [OptimizelyDecideOption::INCLUDE_REASONS]);
echo '    VARIATION   >>>  ' . $decideDecision->getVariationKey() . PHP_EOL;
// ASSERT YOU GET CORRECT VARIATION
echo '    REASONS     >>>  ' . json_encode($decideDecision->getReasons()) . PHP_EOL;
// VERIFY REASONS ARE CORRECT
assert ('variation_b' == $decideDecision->getVariationKey());


// E-to-D (rule key = “flag1_experiment”):
// -------------------------------------------
// Set user context, userId any, age = 0 ( not bucketed)
// Call decide with flag1  ---> expected result is “off”
// Set forced decision w flag1 and rule key “flag1_experiment”, and variation b
// Call decide -----> expected variation b in decide decision

echo PHP_EOL . PHP_EOL . '===================================';
echo 'E-to-D (rule key = “flag1_experiment”):';
echo '==================================='.PHP_EOL;

echo '  Set user context, userId any, age = 0 (not bucketed)'.PHP_EOL;
echo '  Call decide with flag1 ---> expected result is off'.PHP_EOL;
echo '  ---------------------'.PHP_EOL;

$user = $optimizelyClient->createUserContext($userId, array("age"=> 0));
$decideDecision = $user->decide('flag1', [OptimizelyDecideOption::INCLUDE_REASONS]);
echo '    VARIATION   >>>  ' . $decideDecision->getVariationKey() . PHP_EOL;
// ASSERT YOU GET CORRECT VARIATION
echo '    REASONS     >>>  ' . json_encode($decideDecision->getReasons()) . PHP_EOL;
// VERIFY REASONS ARE CORRECT
assert('off' == $decideDecision->getVariationKey());

echo PHP_EOL . '  Set forced decision w flag1 and rule flag1_experiment and variation b' . PHP_EOL;
echo '  Call decide -----> expected variation b in decide decision' . PHP_EOL;
echo '  ---------------------';

$context = new OptimizelyDecisionContext('flag1', 'flag1_experiment');
$decision = new OptimizelyForcedDecision('variation_b');
$user->setForcedDecision($context, $decision);
$decideDecision = $user->decide('flag1', [OptimizelyDecideOption::INCLUDE_REASONS]);
echo '    VARIATION   >>>  ' . $decideDecision->getVariationKey() . PHP_EOL;
// ASSERT YOU GET CORRECT VARIATION
echo '    REASONS     >>>  ' . json_encode($decideDecision->getReasons()) . PHP_EOL;
// VERIFY REASONS ARE CORRECT
assert ('variation_b' == $decideDecision->getVariationKey());


// D-to-D (rule key = “flag1_targeted_delivery”):
// -------------------------------------------
// Set user context, userId any, country = “MX” (not bucketed)
// Call decide with flag1  ---> expected result is “off”
// Set forced decision w flag1 and rule key “flag1_targeted_delivery”, and variation b
// Call decide -----> expected variation b in decide decision


echo PHP_EOL . PHP_EOL . '===================================';
echo 'D-to-D (rule key = flag1_targeted_delivery):';
echo '==================================='.PHP_EOL;

echo '  Set user context, userId any, country = MX  (not bucketed)'.PHP_EOL;
echo '  Call decide with flag1 and rule flag1_targeted_delivery ---> expected result is off'.PHP_EOL;
echo '  ---------------------'.PHP_EOL;

$user = $optimizelyClient->createUserContext($userId, array("country"=> "MX"));
$decideDecision = $user->decide('flag1', [OptimizelyDecideOption::INCLUDE_REASONS]);
echo '    VARIATION   >>>  ' . $decideDecision->getVariationKey() . PHP_EOL;
// ASSERT YOU GET CORRECT VARIATION
echo '    REASONS     >>>  ' . json_encode($decideDecision->getReasons()) . PHP_EOL;
// VERIFY REASONS ARE CORRECT
assert('off' == $decideDecision->getVariationKey());

echo PHP_EOL . '  Set forced decision w flag1 and rule flag1_targeted_delivery and variation b' . PHP_EOL;
echo '  Call decide -----> expected variation b in decide decision' . PHP_EOL;
echo '  ---------------------';

$context = new OptimizelyDecisionContext('flag1', 'flag1_targeted_delivery');
$decision = new OptimizelyForcedDecision('variation_b');
$user->setForcedDecision($context, $decision);
$decideDecision = $user->decide('flag1', [OptimizelyDecideOption::INCLUDE_REASONS]);
echo '    VARIATION   >>>  ' . $decideDecision->getVariationKey() . PHP_EOL;
// ASSERT YOU GET CORRECT VARIATION
echo '    REASONS     >>>  ' . json_encode($decideDecision->getReasons()) . PHP_EOL;
// VERIFY REASONS ARE CORRECT
assert ('variation_b' == $decideDecision->getVariationKey());

// ================================
// Part 3
// ================================
$user = $optimizelyClient->createUserContext($userId);

$user->setForcedDecision(new OptimizelyDecisionContext('F1', null),
                         new OptimizelyForcedDecision('V1'));
$user->setForcedDecision(new OptimizelyDecisionContext('F1', 'E1'),
                        new OptimizelyForcedDecision('V3'));

assert($user->getForcedDecision(new OptimizelyDecisionContext('F1', null)) == 'V1');
assert($user->getForcedDecision(new OptimizelyDecisionContext('F1', 'E1')) == 'V3');
$user->setForcedDecision(new OptimizelyDecisionContext('F1', null),
                         new OptimizelyForcedDecision('V5'));
$user->setForcedDecision(new OptimizelyDecisionContext('F1', 'E1'),
                         new OptimizelyForcedDecision('V5'));

assert($user->getForcedDecision(new OptimizelyDecisionContext('F1', null)) == 'V5');
assert($user->getForcedDecision(new OptimizelyDecisionContext('F1', 'E1')) == 'V5');
$user->removeForcedDecision(new OptimizelyDecisionContext('F1', null));
echo $user->getForcedDecision(new OptimizelyDecisionContext('F1', null)) == null;
assert($user->getForcedDecision(new OptimizelyDecisionContext('F1', null)) == null);
assert($user->getForcedDecision(new OptimizelyDecisionContext('F1', 'E1')) == 'V5');

$user->removeForcedDecision(new OptimizelyDecisionContext('F1', 'E1'));

assert($user->getForcedDecision(new OptimizelyDecisionContext('F1', null)) == null);
assert($user->getForcedDecision(new OptimizelyDecisionContext('F1', 'E1')) == null);

$user->removeAllForcedDecisions();

assert($user->getForcedDecision(new OptimizelyDecisionContext('F2', null)) == null);
assert($user->getForcedDecision(new OptimizelyDecisionContext('F2', 'E1')) == null);



// ?>