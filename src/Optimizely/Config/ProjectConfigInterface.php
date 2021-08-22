<?php
/**
 * Copyright 2016, 2018-2021 Optimizely
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

namespace Optimizely\Config;

/**
 * Interface ProjectConfigInterface
 *
 * @package Optimizely
 */
interface ProjectConfigInterface
{
    /**
     * @return string String representing account ID from the datafile.
     */
    public function getAccountId();

    /**
     * @return string String representing project ID from the datafile.
     */
    public function getProjectId();

    /**
     * @return boolean Flag denoting if Optimizely should remove last block
     * of visitors' IP address before storing event data
     */
    public function getAnonymizeIP();

    /**
     * @return boolean Flag denoting if Optimizely should perform
     * bot filtering on your dispatched events.
     */
    public function getBotFiltering();

    /**
     * @return string String representing revision of the datafile.
     */
    public function getRevision();

    /**
     * @return string String represnting environment key of the datafile.
     */
    public function getEnvironmentKey();

    /**
     * @return string String representing sdkkey of the datafile.
     */
    public function getSdkKey();

    /**
     * @return array List of attributes parsed from the datafile
     */
    public function getAttributes();

    /**
     * @return array List of audiences parsed from the datafile
     */
    public function getAudiences();

    /**
     * @return array List of events parsed from the datafile
     */
    public function getEvents();

    /**
     * @return array List of typed audiences parsed from the datafile
     */
    public function getTypedAudiences();

    
    /**
     * @return array List of feature flags parsed from the datafile
     */
    public function getFeatureFlags();

    /**
     * @return array List of all experiments (including group experiments)
     */
    public function getAllExperiments();

    /**
     * @param $groupId string ID of the group.
     *
     * @return Group Entity corresponding to the ID.
     *         Dummy entity is returned if ID is invalid.
     */
    public function getGroup($groupId);

    /**
     * @param $experimentKey string Key of the experiment.
     *
     * @return Experiment Entity corresponding to the key.
     *         Dummy entity is returned if key is invalid.
     */
    public function getExperimentFromKey($experimentKey);

    /**
     * @param $experimentId string ID of the experiment.
     *
     * @return Experiment Entity corresponding to the key.
     *         Dummy entity is returned if ID is invalid.
     */
    public function getExperimentFromId($experimentId);

    /**
     * @param String $featureKey Key of the feature flag
     *
     * @return FeatureFlag Entity corresponding to the key.
     */
    public function getFeatureFlagFromKey($featureKey);

    /**
     * @param String $rolloutId
     *
     * @return Rollout
     */
    public function getRolloutFromId($rolloutId);

    /**
     * @param $eventKey string Key of the event.
     *
     * @return Event Entity corresponding to the key.
     *         Dummy entity is returned if key is invalid.
     */
    public function getEvent($eventKey);

    /**
     * @param $audienceId string ID of the audience.
     *
     * @return Audience Entity corresponding to the ID.
     *         Null is returned if ID is invalid.
     */
    public function getAudience($audienceId);

    /**
     * @param $attributeKey string Key of the attribute.
     *
     * @return Attribute Entity corresponding to the key.
     *         Null is returned if key is invalid.
     */
    public function getAttribute($attributeKey);

    /**
     * @param $experimentKey string Key for experiment.
     * @param $variationKey string Key for variation.
     *
     * @return Variation Entity corresponding to the provided experiment key and variation key.
     *         Dummy entity is returned if key or ID is invalid.
     */
    public function getVariationFromKey($experimentKey, $variationKey);

    /**
     * @param $experimentKey string Key for experiment.
     * @param $variationId string ID for variation.
     *
     * @return Variation Entity corresponding to the provided experiment key and variation ID.
     *         Dummy entity is returned if key or ID is invalid.
     */
    public function getVariationFromId($experimentKey, $variationId);

    /**
     * @param $experimentId string ID for experiment.
     * @param $variationId string ID for variation.
     *
     * @return Variation Entity corresponding to the provided experiment ID and variation ID.
     *         Dummy entity is returned if key or ID is invalid.
     */
    public function getVariationFromIdByExperimentId($experimentId, $variationId);

    /**
     * @param $experimentId string ID for experiment.
     * @param $variationKey string Key for variation.
     *
     * @return Variation Entity corresponding to the provided experiment ID and variation Key.
     *         Dummy entity is returned if key or ID is invalid.
     */
    public function getVariationFromKeyByExperimentId($experimentId, $variationKey);

    /**
     * Gets the feature variable instance given feature flag key and variable key
     *
     * @param string Feature flag key
     * @param string Variable key
     *
     * @return FeatureVariable / null
     */
    public function getFeatureVariableFromKey($featureFlagKey, $variableKey);
    
    /**
     * Determines if given experiment is a feature test.
     *
     * @param string Experiment ID.
     *
     * @return boolean A boolean value that indicates if the experiment is a feature test.
     */
    public function isFeatureExperiment($experimentId);

    /**
     * Returns string representation of datafile.
     *
     * @return string A string value that contains datafile contents.
     */
    public function toDatafile();

    /**
     * Returns if flag decisions should be sent to server or not
     *
     * @return boolean
     */
    public function getSendFlagDecisions();
}
