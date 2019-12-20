<?php
/**
 * Copyright 2019, Optimizely Inc and Contributors
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
    private $experiments;

    private $featureFlags;

    private $revision;

    private $experimentIdFeatureMap;

    private $featKeyOptlyVariableKeyVariableMap;

    private $featKeyOptlyVariableIdVariableMap;

    public function __construct(ProjectConfigInterface $projectConfig)
    {
        $this->experiments = $projectConfig->getAllExperiments();
        $this->featureFlags = $projectConfig->getFeatureFlags();
        $this->revision = $projectConfig->getRevision();

        $this->createLookupMaps();
    }


    public function getConfig()
    {
        $experimentsMaps = $this->getExperimentsMaps();
        $featuresMap = $this->getFeaturesMap($experimentsMaps[1]);

        return new OptimizelyConfig(
            $this->revision,
            $experimentsMaps[0],
            $featuresMap
        );
    }
    
    protected function createLookupMaps()
    {
        $this->experimentIdFeatureMap = [];
        $this->featKeyOptlyVariableKeyVariableMap = [];
        $this->featKeyOptlyVariableIdVariableMap = [];

        foreach ($this->featureFlags as $key => $feature) {
            # Populate experimentIdFeatureMap
            foreach ($feature->getExperimentIds() as $expId) {
                $this->experimentIdFeatureMap[$expId] = $feature;
            }

            # Populate featKeyOptlyVariableKeyVariableMap and featKeyOptlyVariableIdVariableMap
            $variablesKeyMap = [];
            $variablesIdMap = [];

            foreach ($feature->getVariables() as $variable) {
                $optVariable = new OptimizelyVariable(
                    $variable->getId(),
                    $variable->getKey(),
                    $variable->getType(),
                    $variable->getDefaultValue()
                );

                $variablesKeyMap[$variable->getKey()] = $optVariable;
                $variablesIdMap[$variable->getId()] = $optVariable;
            }

            $this->featKeyOptlyVariableKeyVariableMap[$feature->getKey()] = $variablesKeyMap;
            $this->featKeyOptlyVariableIdVariableMap[$feature->getKey()] = $variablesIdMap;
        }
    }

    protected function getVariablesMap(Variation $variation, Experiment $experiment)
    {
        $experimentId = $experiment->getId();
        if (!array_key_exists($experimentId, $this->experimentIdFeatureMap)) {
            return [];
        }

        $featureFlag = $this->experimentIdFeatureMap[$experimentId];

        // Set default variables for variation.
        $variablesMap = $this->featKeyOptlyVariableKeyVariableMap[$featureFlag->getKey()];
        
  
        if (!$variation->getFeatureEnabled()) {
            return $variablesMap;
        }

        // Set variation specific value if any.
        foreach ($variation->getVariables() as $variableUsage) {
            $optVariable =
                $this->featKeyOptlyVariableIdVariableMap[$featureFlag->getKey()][$variableUsage->getId()];
            
           
            $value = $variableUsage->getValue();

            $modifiedOptVariable = new OptimizelyVariable(
                $optVariable->getId(),
                $optVariable->getKey(),
                $optVariable->getType(),
                $value
            );

            $variablesMap[$optVariable->getKey()] = $modifiedOptVariable;
        }

        return $variablesMap;
    }

    protected function getVariationsMap(Experiment $experiment)
    {
        $variationsMap = [];

        foreach ($experiment->getVariations() as $variation) {
            $variablesMap = $this->getVariablesMap($variation, $experiment);
            $featureEnabled = $variation->getFeatureEnabled();

            $optVariation = new OptimizelyVariation(
                $variation->getId(),
                $variation->getKey(),
                $featureEnabled,
                $variablesMap
            );

            $variationsMap[$variation->getKey()] = $optVariation;
        }

        return $variationsMap;
    }

    protected function getExperimentsMaps()
    {
        $experimentsKeyMap = [];
        $experimentsIdMap = [];

        foreach ($this->experiments as $exp) {
            $optExp = new OptimizelyExperiment(
                $exp->getId(),
                $exp->getKey(),
                $this->getVariationsMap($exp)
            );

            $experimentsKeyMap[$exp->getKey()] = $optExp;
            $experimentsIdMap[$exp->getId()] = $optExp;
        }

        return [$experimentsKeyMap, $experimentsIdMap];
    }

    protected function getFeaturesMap(array $experimentsIdMap)
    {
        $featuresMap = [];

        foreach ($this->featureFlags as $feature) {
            $experimentsMap = [];

            foreach ($feature->getExperimentIds() as $expId) {
                $optExp = $experimentsIdMap[$expId];
                $experimentsMap[$optExp->getKey()] = $optExp;
            }

            $variablesMap = $this->featKeyOptlyVariableKeyVariableMap[$feature->getKey()];

            $optFeature = new OptimizelyFeature(
                $feature->getId(),
                $feature->getKey(),
                $experimentsMap,
                $variablesMap
            );

            $featuresMap[$feature->getKey()] = $optFeature;
        }

        return $featuresMap;
    }
}
