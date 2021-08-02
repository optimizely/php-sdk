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

use function GuzzleHttp\json_decode;

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
    private $environment_key;

    /**
     * @var string sdkKey of the config.
     */
    private $sdk_key;

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
        $this->environment_key = $projectConfig->getEnvironmentKey();
        $this->sdk_key = $projectConfig->getSdkKey();
        $this->project_config = $projectConfig;

        
        $this->createLookupMaps();
    }

    /**
     * @return OptimizelyConfig Instance of OptimizelyConfig for the given project config.
     */
    public function getConfig()
    {
        $experimentsMaps = $this->getExperimentsMaps();
        $featuresMap = $this->getFeaturesMap($experimentsMaps[1], $this->project_config);
        $attributes = $this->getConfigAttributes();
        $audiences = $this->getConfigAudiences();
        $events = $this->getConfigEvents();
        return new OptimizelyConfig(
            $this->revision,
            $experimentsMaps[0],
            $featuresMap,
            $this->datafile,
            $this->environment_key,
            $this->sdk_key,
            $attributes,
            $audiences,
            $events
        );
    }
    
    /**
     * Generates array of attributes as OptimizelyAttribute.
     *
     *
     * @return array of OptimizelyAttributes.
     */
    protected function getConfigAttributes()
    {
        $attributArray = [];
        $attributes = $this->project_config->getAttributes();
        foreach($attributes as $attr){
            $optly_attr = new OptimizelyAttribute(
                $attr['id'],
                $attr['key']
            );
            array_push($attributArray, $optly_attr);
        }
        return $attributArray;
    }

    /**
     * Generates array of events as OptimizelyEvents.
     *
     *
     * @return array of OptimizelyEvents.
     */
    protected function getConfigEvents()
    {
        $eventsArray = [];
        $events = $this->project_config->getEvents();
        foreach($events as $event){
            $optly_event = new OptimizelyEvent(
                $event['id'],
                $event['key'],
                $event['experimentIds']
            );
            array_push($eventsArray, $optly_event);
        }
        return $eventsArray;
    }

    /**
     * Generates array of audiences giving typed audiences high priority as OptimizelyAudience.
     *
     *
     * @return array of OptimizelyEvents.
     */
    protected function getConfigAudiences()
    {
        $optAudiences = [];
        $uniqueIds = [];
        $normalAudiences = $this->project_config->getAudiences();
        $typedAudiences = $this->project_config->getTypedAudiences();
        $audiencesArray = $typedAudiences;
        foreach($audiencesArray as $typedAudience){
            $id = $typedAudience['id'];
            array_push($uniqueIds, $id);
        }
        foreach ($normalAudiences as $naudience) {
            $id = $naudience['id'];
            if (in_array($id, $uniqueIds) == false) {
                array_push($audiencesArray, $naudience);
            }
        }    
        foreach ($audiencesArray as $audience){
            $id = $audience['id'];
            if ($id != '$opt_dummy_audience'){
                $optly_audience = new OptimizelyAudience(
                    $audience['id'],
                    $audience['name'],
                    $audience['conditions']
                );
                array_push($optAudiences, $optly_audience);
            }

        }
        return $optAudiences;
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
     * Generates array of delivery rules for optimizelyFeature.
     *
     * @param string feature rollout id.
     *
     * @return array of optimizelyExperiments as delivery rules .
     */
    protected function getExperimentAudiences(array $audienceCond)
    {
        $projectConfig = $this->project_config;
        $resAudiences = '';
        $audienceConditions = array('and', 'or', 'not');
        if ($audienceCond != null){
            $cond = '';
            foreach($audienceCond as $var) {
                $subAudience = '';
                if (is_array($var)){
                    $subAudience = $this->getExperimentAudiences($var);
                    $subAudience = '('. $subAudience. ')';    
                }
                elseif (in_array($var, $audienceConditions, TRUE)){
                    $cond = strtoupper(strval($var));
                }
                else {
                    $itemStr = strval($var);
                    if ($resAudiences !== '' || $cond == "NOT"){
                        if ($resAudiences !== '') {
                            $resAudiences = $resAudiences . ' ';
                        }
                        else {
                            $resAudiences = $resAudiences;
                        }
                        if ($cond ==''){
                            $cond = 'OR';
                        }
                        $audience = $projectConfig->getAudience($itemStr);
                        $name = $audience->getName();
                        $resAudiences = $resAudiences . $cond .' '.'"' . $name . '"';
                    }
                    else{
                        $audience = $projectConfig->getAudience($itemStr);
                        $name = $audience->getName();
                        $resAudiences = '"'. $name. '"';
                        
                    }

                }
                if (strval($subAudience !== '')){
                    if ((strval($resAudiences) !== '') || $cond=="NOT"){
                        if ($resAudiences == '') {
                            $resAudiences = $resAudiences. ' ';
                        }
                        else {
                            $resAudiences = $resAudiences;

                        }
                        if ($cond = ''){
                            $cond = 'OR';
                        }
                        else{
                            $cond = $cond;

                        }
                        $resAudiences = $resAudiences. $cond. ' '. $subAudience;
                }
                else{
                    $resAudiences = $resAudiences. $subAudience;
                }
            }
        }
    }
    return $resAudiences;
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
            if (is_null($exp->getAudienceConditions())) {
                $audiences = '';
            }
            else {
                $audienceCond = $exp->getAudienceConditions();
                $audiences = $this->getExperimentAudiences($audienceCond, $this->project_config);

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
    protected function getDeliveryRules(string $rollout_id, $projectConfig)
    {
        $deliveryRules = [];
        $rollout = $projectConfig->getRolloutFromId($rollout_id);
        $experiments = $rollout->getExperiments();
        foreach ($experiments as $exp){
            $expId = $exp->getId();
            $expKey = $exp->getKey();
            $audiences = '';
            if (is_null($exp->getAudienceConditions())) {
                $audiences = '';
            }
            else {
                $audienceCond = $exp->getAudienceConditions();
                $audiences = $this->getExperimentAudiences($audienceCond, $projectConfig);

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
            $rollout_id = $feature->getRolloutId();
            if (is_null($rollout_id))
            {
                $deliveryRules = [];
            }
            else{
                $deliveryRules = $this->getDeliveryRules($rollout_id, $this->project_config);

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
                $deliveryRules,
            );

            $featuresMap[$featureKey] = $optFeature;
        }

        return $featuresMap;
    }
}
