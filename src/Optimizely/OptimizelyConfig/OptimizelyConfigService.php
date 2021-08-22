<?php
/**
 * Copyright 2020-2021, Optimizely Inc and Contributors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Optimizely\OptimizelyConfig;

use Optimizely\Config\ProjectConfigInterface;
use Optimizely\Entity\Experiment;
use Optimizely\Entity\Variation;

class OptimizelyConfigService
{
    /**
     * @var array List of experiments in config.
     */
    private $experiments;

    /**
     * @var array List of feature flags in config.
     */
    private $featureFlags;

    /**
     * @var string Revision of config.
     */
    private $revision;

    /**
     * @var string environmentKey of the config.
     */
    private $environmentKey;

    /**
     * @var string sdkKey of the config.
     */
    private $sdkKey;

    /**
     * @var string String denoting datafile.
     */
    private $datafile;

    /**
     * Map of experiment IDs to FeatureFlags.
     *
     * @var <string, FeatureFlag> associative array.
     */
    private $experimentIdFeatureMap;

    /**
     * Map of feature keys to Map of variable keys to OptimizelyVariables map.
     *
     * @var <string, <string, OptimizelyVariable>> associative array.
     */
    private $featKeyOptlyVariableKeyVariableMap;

    /**
     * Map of feature keys to Map of variable IDs to OptimizelyVariables map.
     *
     * @var <string, <string, OptimizelyVariable>> associative array.
     */
    private $featKeyOptlyVariableIdVariableMap;

    public function __construct(ProjectConfigInterface $projectConfig)
    {
        $this->experiments = $projectConfig->getAllExperiments();
        $this->featureFlags = $projectConfig->getFeatureFlags();
        $this->revision = $projectConfig->getRevision();
        $this->datafile = $projectConfig->toDatafile();
        $this->environmentKey = $projectConfig->getEnvironmentKey();
        $this->sdkKey = $projectConfig->getSdkKey();
        $this->projectConfig = $projectConfig;
        
        $this->createLookupMaps();
    }

    /**
     * @return OptimizelyConfig Instance of OptimizelyConfig for the given project config.
     */
    public function getConfig()
    {
        $experimentsMaps = $this->getExperimentsMaps();
        $featuresMap = $this->getFeaturesMap($experimentsMaps[1]);
        $attributes = $this->getConfigAttributes();
        $audiences = $this->getConfigAudiences();
        $events = $this->getConfigEvents();

        return new OptimizelyConfig(
            $this->revision,
            $experimentsMaps[0],
            $featuresMap,
            $this->datafile,
            $this->environmentKey,
            $this->sdkKey,
            $attributes,
            $audiences,
            $events
        );
    }

    /**
     * Generates array of attributes as OptimizelyAttribute.
     *
     * @return array of OptimizelyAttributes.
     */
    protected function getConfigAttributes()
    {
        $attributeArray = [];
        $attributes = $this->projectConfig->getAttributes();
        foreach ($attributes as $attr) {
            $optlyAttr = new OptimizelyAttribute(
                $attr['id'],
                $attr['key']
            );
            array_push($attributeArray, $optlyAttr);
        }
        return $attributeArray;
    }


    /**
     * Generates array of events as OptimizelyEvents.
     *
     * @return array of OptimizelyEvents.
     */
    protected function getConfigEvents()
    {
        $eventsArray = [];
        $events = $this->projectConfig->getEvents();
        foreach ($events as $event) {
            $optlyEvent = new OptimizelyEvent(
                $event['id'],
                $event['key'],
                $event['experimentIds']
            );
            $eventsArray[] = $optlyEvent;
        }
        return $eventsArray;
    }

    /**
     * Generates array of audiences giving typed audiences high priority as OptimizelyAudience.
     *
     * @return array of OptimizelyEvents.
     */
    protected function getConfigAudiences()
    {
        $finalAudiences = [];
        $uniqueIdsMap = [];
        $normalAudiences = $this->projectConfig->getAudiences();
        $typedAudiences = $this->projectConfig->getTypedAudiences();
        $audiencesArray = $typedAudiences;
        foreach ($audiencesArray as $key => $typedAudience) {
            $uniqueIdsMap[$typedAudience['id']] = $typedAudience['id'];
            $audiencesArray[$key]['conditions'] = json_encode($typedAudience['conditions']);
        }
        foreach ($normalAudiences as $naudience) {
            if (!array_key_exists($naudience['id'], $uniqueIdsMap)) {
                array_push($audiencesArray, $naudience);
            }
        }
        foreach ($audiencesArray as $audience) {
            $id = $audience['id'];
            if ($id != '$opt_dummy_audience') {
                $optlyAudience = new OptimizelyAudience(
                    $id,
                    $audience['name'],
                    $audience['conditions']
                );
                array_push($finalAudiences, $optlyAudience);
            }
        }
        return $finalAudiences;
    }

    /**
     * Generates lookup maps to avoid redundant iteration while creating OptimizelyConfig.
     */
    protected function createLookupMaps()
    {
        $this->experimentIdFeatureMap = [];
        $this->featKeyOptlyVariableKeyVariableMap = [];
        $this->featKeyOptlyVariableIdVariableMap = [];

        foreach ($this->featureFlags as $feature) {
            # Populate experimentIdFeatureMap
            foreach ($feature->getExperimentIds() as $expId) {
                $this->experimentIdFeatureMap[$expId] = $feature;
            }
            $rolloutID = $feature->getRolloutId();
            $rollout = $this->projectConfig->getRolloutFromId($rolloutID);
            foreach ($rollout->getExperiments() as $exp) {
                $this->experimentIdFeatureMap[$exp->getId()] = $feature;
            }
            # Populate featKeyOptlyVariableKeyVariableMap and featKeyOptlyVariableIdVariableMap
            $variablesKeyMap = [];
            $variablesIdMap = [];

            foreach ($feature->getVariables() as $variable) {
                $variableId = $variable->getId();
                $variableKey = $variable->getKey();

                $optVariable = new OptimizelyVariable(
                    $variableId,
                    $variableKey,
                    $variable->getType(),
                    $variable->getDefaultValue()
                );

                $variablesKeyMap[$variableKey] = $optVariable;
                $variablesIdMap[$variableId] = $optVariable;
            }

            $featureKey = $feature->getKey();
            $this->featKeyOptlyVariableKeyVariableMap[$featureKey] = $variablesKeyMap;
            $this->featKeyOptlyVariableIdVariableMap[$featureKey] = $variablesIdMap;
        }
    }

    /**
     * Generates Variables map for the given Experiment and Variation.
     *
     * @param Experiment
     * @param Variation
     *
     * @return <String, OptimizelyVariable> Map of Variable key to OptimizelyVariable.
     */
    protected function getVariablesMap(Experiment $experiment, Variation $variation)
    {
        $experimentId = $experiment->getId();
        if (!array_key_exists($experimentId, $this->experimentIdFeatureMap)) {
            return [];
        }

        $featureFlag = $this->experimentIdFeatureMap[$experimentId];

        $featureKey = $featureFlag->getKey();

        // Set default variables for variation.
        $variablesMap = $this->featKeyOptlyVariableKeyVariableMap[$featureKey];
        
        // Return default variable values if feature is not enabled.
        if (!$variation->getFeatureEnabled()) {
            return $variablesMap;
        }

        // Set variation specific value if any.
        foreach ($variation->getVariables() as $variableUsage) {
            $id = $variableUsage->getId();
    
            $optVariable = $this->featKeyOptlyVariableIdVariableMap[$featureKey][$id];
    
            $key = $optVariable->getKey();
            $value = $variableUsage->getValue();
            $type = $optVariable->getType();
            
            $modifiedOptVariable = new OptimizelyVariable(
                $id,
                $key,
                $type,
                $value
            );

            $variablesMap[$key] = $modifiedOptVariable;
        }

        return $variablesMap;
    }

    
    /**
     * Generates Variations map for the given Experiment.
     *
     * @param Experiment
     *
     * @return <String, OptimizelyVariation> Map of Variation key to OptimizelyVariation.
     */
    protected function getVariationsMap(Experiment $experiment)
    {
        $variationsMap = [];

        foreach ($experiment->getVariations() as $variation) {
            $variablesMap = $this->getVariablesMap($experiment, $variation);
 
            $variationKey = $variation->getKey();
            $optVariation = new OptimizelyVariation(
                $variation->getId(),
                $variationKey,
                $variation->getFeatureEnabled(),
                $variablesMap
            );

            $variationsMap[$variationKey] = $optVariation;
        }

        return $variationsMap;
    }

    /**
     * Converts array of audience conditions to serialized audiences.
     *
     * for examples:
     * 1. Input: ["or", "1", "2"]
     *     Output: "us" OR "female"
     * 2. Input: ["not", "1"]
     *     Output: "NOT "us"
     * 3. Input: ["or", "1"]
     *     Output: "us"
     * 4. Input: ["and", ["or", "1", ["and", "2", "3"]], ["and", "11", ["or", "12", "13"]]]
     *     Output: "("us" OR ("female" AND "adult")) AND ("fr" AND ("male" OR "kid"))"
     *
     * @param array audience conditions .
     *
     * @return string of experiment audience conditions.
     */
    protected function getSerializedAudiences(array $audienceConditions)
    {
        $finalAudiences = '';
        if ($audienceConditions == null) {
            return $finalAudiences;
        }
        $cond = '';
        foreach ($audienceConditions as $var) {
            $subAudience = '';
            // Checks if item is list of conditions means if it is sub audience
            if (is_array($var)) {
                $subAudience = $this->getSerializedAudiences($var);

                $subAudience = '(' . $subAudience . ')';
            } elseif (in_array($var, array('and', 'or', 'not'), true)) {
                $cond = strtoupper(strval($var));
            } else {
                // Checks if item is audience id
                $itemStr = strval($var);
                $audience = $this->projectConfig->getAudience($itemStr);
                $name = $audience == null ? $itemStr : $audience->getName();
                // if audience condition is "NOT" then add "NOT" at start. Otherwise check if there is already audience id in finalAudiences then append condition between finalAudiences and item
                if ($finalAudiences !== '' || $cond == "NOT") {
                    if ($finalAudiences !== '') {
                        $finalAudiences = $finalAudiences . ' ';
                    } else {
                        $finalAudiences = $finalAudiences;
                    }
                    if ($cond == '') {
                        $cond = 'OR';
                    }
                    $finalAudiences = $finalAudiences . $cond . ' ' . '"' . $name . '"';
                } else {
                    $finalAudiences = '"' . $name . '"';
                }
            }
            // Checks if sub audience is empty or not
            if (strval($subAudience !== '')) {
                if ($finalAudiences !== '' || $cond == "NOT") {
                    if ($finalAudiences !== '') {
                        $finalAudiences = $finalAudiences . ' ';
                    } else {
                        $finalAudiences = $finalAudiences;
                    }
                    if ($cond == '') {
                        $cond = 'OR';
                    }
                    $finalAudiences = $finalAudiences . $cond . ' ' . $subAudience;
                } else {
                    $finalAudiences = $finalAudiences . $subAudience;
                }
            }
        }
        return $finalAudiences;
    }


    /**
     * Generates OptimizelyExperiment Key and ID Maps.
     * Returns an array with
     * [0] OptimizelyExperimentKeyMap Used to form OptimizelyConfig
     * [1] OptimizelyExperimentIdMap Used for quick lookup when forming Features for OptimizelyConfig.
     *
     * @return [<string, OptimizelyExperiment>, <string, OptimizelyExperiment>]
     */
    protected function getExperimentsMaps()
    {
        $experimentsKeyMap = [];
        $experimentsIdMap = [];

        foreach ($this->experiments as $exp) {
            $expId = $exp->getId();
            $expKey = $exp->getKey();
            $audiences = '';
            if ($exp->getAudienceConditions() != null) {
                $audienceConditions = $exp->getAudienceConditions();
                $audiences = $this->getSerializedAudiences($audienceConditions);
            }
            $optExp = new OptimizelyExperiment(
                $expId,
                $expKey,
                $this->getVariationsMap($exp),
                $audiences
            );

            $experimentsKeyMap[$expKey] = $optExp;
            $experimentsIdMap[$expId] = $optExp;
        }

        return [$experimentsKeyMap, $experimentsIdMap];
    }

    /**
     * Generates array of delivery rules for optimizelyFeature.
     *
     * @param string feature rollout id.
     *
     * @return array of optimizelyExperiments as delivery rules .
     */
    protected function getDeliveryRules($rollout_id)
    {
        $deliveryRules = [];
        $rollout = $this->projectConfig->getRolloutFromId($rollout_id);
        $experiments = $rollout->getExperiments();
        foreach ($experiments as $exp) {
            $expId = $exp->getId();
            $expKey = $exp->getKey();
            $audiences = '';
            if ($exp->getAudienceConditions() != null) {
                $audienceConditions = $exp->getAudienceConditions();
                $audiences = $this->getSerializedAudiences($audienceConditions);
            }
            $optExp = new OptimizelyExperiment(
                $expId,
                $expKey,
                $this->getVariationsMap($exp),
                $audiences
            );
            array_push($deliveryRules, $optExp);
        }

        return $deliveryRules;
    }


    /**
     * Generates Features map for the project config.
     *
     * @param array Map of ID to OptimizelyExperiments.
     *
     * @return <String, OptimizelyFeature> Map of Feature key to OptimizelyFeature.
     */
    protected function getFeaturesMap(array $experimentsIdMap)
    {
        $featuresMap = [];

        foreach ($this->featureFlags as $feature) {
            $featureKey = $feature->getKey();
            $experimentsMap = [];
            $experimentRules = [];
            $deliveryRules = [];
            if ($feature->getRolloutId() != null) {
                $deliveryRules = $this->getDeliveryRules($feature->getRolloutId());
            }
            foreach ($feature->getExperimentIds() as $expId) {
                $optExp = $experimentsIdMap[$expId];
                $experimentsMap[$optExp->getKey()] = $optExp;
                array_push($experimentRules, $optExp);
            }

            $variablesMap = $this->featKeyOptlyVariableKeyVariableMap[$featureKey];

            $optFeature = new OptimizelyFeature(
                $feature->getId(),
                $featureKey,
                $experimentsMap,
                $variablesMap,
                $experimentRules,
                $deliveryRules
            );

            $featuresMap[$featureKey] = $optFeature;
        }

        return $featuresMap;
    }
}
