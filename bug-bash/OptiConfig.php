<?php

namespace Optimizely\BugBash;

require_once '../vendor/autoload.php';
require_once '../bug-bash/_bug-bash-autoload.php';

use Optimizely\Optimizely;
use Optimizely\Decide\OptimizelyDecideOption;

// Instantiate an Optimizely client
$sdkKey = "TbrfRLeKvLyWGusqANoeR";
// $optimizelyClient = new Optimizely($sdkKey);
$optimizelyClient = new Optimizely(null, null, null, null, false, null, null, null, $sdkKey);
$user = $optimizelyClient->createUserContext('user123', ['attribute1' => 'hello']);
$decision = $user->decide('flag1', [OptimizelyDecideOption::INCLUDE_REASONS]);

$reasons = $decision->getReasons();
echo "[OptimizelyConfig] reasons: " . json_encode($reasons) . PHP_EOL;
echo "[OptimizelyConfig - flag key]: " . $decision->getFlagKey() . PHP_EOL;
echo "[OptimizelyConfig - rule key]: " . $decision->getFlagKey() . PHP_EOL;
echo "[OptimizelyConfig - enabled]: " . $decision->getEnabled() . PHP_EOL;
echo "[OptimizelyConfig - variation key]: " . $decision->getVariationKey() . PHP_EOL;
$variables = $decision->getVariables();
echo "[OptimizelyConfig - variables]: " . json_encode($variables) . PHP_EOL;
echo PHP_EOL;

$user->trackEvent('myevent');

echo "===========================" . PHP_EOL;
echo "   OPTIMIZELY CONFIG V2    " . PHP_EOL;
echo "===========================" . PHP_EOL . PHP_EOL;

$config = $optimizelyClient->getOptimizelyConfig();
// get the revision
echo "[OptimizelyConfig] revision:" . $config->getRevision() . PHP_EOL;

// get the SDK key
echo "[OptimizelyConfig] SDKKey:" . $config->getSdkKey() . PHP_EOL;

// get the environment key
echo "[OptimizelyConfig] environmentKey:" . $config->getEnvironmentKey() . PHP_EOL;

// all attributes
echo "[OptimizelyConfig] attributes:" . PHP_EOL;
$attributes = $config->getAttributes();
foreach($attributes as $attribute)
{
    echo "[OptimizelyAttribute]   -- (id, key) = ((" . $attribute->getId(). "), (". $attribute->getKey() . "))" . PHP_EOL;
}

// all audiences
echo "[OptimizelyConfig] audiences:" . PHP_EOL;
$audiences = $config->getAudiences();
foreach($audiences as $audience)
{
    echo "[OptimizelyAudience]   -- (id, key, conditions) = ((" . $audience->getId(). "), (". $audience->getName() . "), (". $audience->getConditions() . "))" . PHP_EOL;
}

// all events
echo "[OptimizelyConfig] events:" . PHP_EOL;
$events = $config->getEvents();
foreach($events as $event)
{
    echo "[OptimizelyEvent]   -- (id, key, experimentIds) = ((" . $event->getId(). "), (". $event->getKey() . "), (". $event->getExperimentIds() . "))" . PHP_EOL;
}

// all flags
$flags = array_values((array)$config->getFeaturesMap());
foreach ($flags as $flag)
{
    // Use  experimentRules and deliverRules
    $experimentRules = $flag->getExperimentRules();
    echo "------ Experiment rules -----" . PHP_EOL;
    foreach ($experimentRules as $experimentRule)
    {
        echo "---" . PHP_EOL;
        echo "[OptimizelyExperiment]   - experiment rule-key = " . $experimentRule->getKey() . PHP_EOL;
        echo "[OptimizelyExperiment]   - experiment audiences = " . PHP_EOL;$experimentRule->getExperimentAudiences();
            // all variations
            $variations = array_values((array)$experimentRule->getVariationsMap());
            foreach ($variations as $variation)
            {
                echo "[OptimizelyVariation]       -- variation = { key: " . $variation->getKey() . ", . id: " . $variation->getId() . ", featureEnabled: " . $variation->getFeatureEnabled() . " }" . PHP_EOL;
                $variables = $variation->getVariablesMap();
                foreach ($variables as $variable)
                {
                    echo "[OptimizelyVariable]           --- variable: " . $variable->getKey() . ", " . $variable->getId() . PHP_EOL;
                // use variable data here.
                }
                // use experimentRule data here.
            }
    }
    $deliveryRules = $flag->getDeliveryRules();
    echo "------ Delivery rules -----" . PHP_EOL;
    foreach ($deliveryRules as $deliveryRule)
    {
        echo "---";
        echo "[OptimizelyExperiment]   - delivery rule-key = " . $deliveryRule->getKey() . PHP_EOL;
        echo "[OptimizelyExperiment]   - delivery audiences = " . $deliveryRule->getExperimentAudiences() . PHP_EOL;

        // use delivery rule data here...
    }
}
// $optimizelyClient->notificationCenter->addNotificationListener(
//     NotificationType::OPTIMIZELY_CONFIG_UPDATE,
//     function () {
//         $newConfig = $optimizelyClient->getOptimizelyConfig();
//         echo "[OptimizelyConfig] revision = " . $newConfig ? $newConfig->getRevision() : "" . PHP_EOL;
    // }
// );

