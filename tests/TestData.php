<?php
/**
 * Copyright 2016-2017, Optimizely
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
namespace Optimizely\Tests;

use Exception;
use Optimizely\Bucketer;
use Optimizely\Event\Dispatcher\EventDispatcherInterface;
use Optimizely\Event\LogEvent;

define('DATAFILE',
    '{"experiments": [{"status": "Running", "key": "test_experiment", "layerId": "7719770039", 
    "trafficAllocation": [{"entityId": "", "endOfRange": 1500}, {"entityId": "7722370027", "endOfRange": 4000}, 
    {"entityId": "7721010009", "endOfRange": 8000}], "audienceIds": ["7718080042"], 
    "variations": [{"id": "7722370027", "key": "control"}, {"id": "7721010009", "key": "variation"}], 
    "forcedVariations": {"user1": "control"}, "id": "7716830082"}, {"status": "Paused", "key": "paused_experiment", "layerId": "7719779139", 
    "trafficAllocation": [{"entityId": "7722370427", "endOfRange": 5000}, 
    {"entityId": "7721010509", "endOfRange": 8000}], "audienceIds": [], 
    "variations": [{"id": "7722370427", "key": "control"}, {"id": "7721010509", "key": "variation"}], 
    "forcedVariations": {}, "id": "7716830585"}], "version": "2", 
    "audiences": [{"conditions": "[\"and\", [\"or\", [\"or\", {\"name\": \"device_type\", \"type\": \"custom_attribute\", \"value\": \"iPhone\"}]], [\"or\", [\"or\", {\"name\": \"location\", \"type\": \"custom_attribute\", \"value\": \"San Francisco\"}]]]", "id": "7718080042", "name": "iPhone users in San Francisco"}], 
    "groups": [{"policy": "random", "trafficAllocation": [{"entityId": "", "endOfRange": 500}, {"entityId": "7723330021", "endOfRange": 2000}, {"entityId": "7718750065", "endOfRange": 6000}], "experiments": [{"status": "Running", "key": "group_experiment_1", "layerId": "7721010011", "trafficAllocation": [{"entityId": "7722260071", "endOfRange": 5000}, {"entityId": "7722360022", "endOfRange": 10000}], "audienceIds": [], "variations": [{"id": "7722260071", "key": "group_exp_1_var_1"}, {"id": "7722360022", "key": "group_exp_1_var_2"}], "forcedVariations": {"user1": "group_exp_1_var_1"}, "id": "7723330021"}, {"status": "Running", "key": "group_experiment_2", "layerId": "7721020020", "trafficAllocation": [{"entityId": "7713030086", "endOfRange": 5000}, {"entityId": "7725250007", "endOfRange": 10000}], "audienceIds": [], 
    "variations": [{"id": "7713030086", "key": "group_exp_2_var_1"}, {"id": "7725250007", "key": "group_exp_2_var_2"}], "forcedVariations": {}, "id": "7718750065"}], "id": "7722400015"}], 
    "attributes": [{"id": "7723280020", "key": "device_type"}, {"id": "7723340004", "key": "location"}], 
    "projectId": "7720880029", "accountId": "1592310167", 
    "events": [{"experimentIds": ["7716830082", "7723330021", "7718750065", "7716830585"], "id": "7718020063", "key": "purchase"}],"anonymizeIP": false,
    "revision": "15"}');

define('DATAFILE_V3',
    '{"experiments": [{"status": "Running", "key": "test_experiment", "layerId": "7719770039", 
    "trafficAllocation": [{"entityId": "", "endOfRange": 1500}, {"entityId": "7722370027", "endOfRange": 4000}, 
    {"entityId": "7721010009", "endOfRange": 8000}], "audienceIds": ["7718080042"], 
    "variations": [{"id": "7722370027", "key": "control", "variables": [{"id": "8284765437", "value": "true"}]}, {"id": "7721010009", "key": "variation", "variables": [{"id": "8284765437", "value": "false"}]}], 
    "forcedVariations": {"user1": "control"}, "id": "7716830082"}, {"status": "Paused", "key": "paused_experiment", "layerId": "7719779139", 
    "trafficAllocation": [{"entityId": "7722370427", "endOfRange": 5000}, 
    {"entityId": "7721010509", "endOfRange": 8000}], "audienceIds": [], 
    "variations": [{"id": "7722370427", "key": "control", "variables": []}, {"id": "7721010509", "key": "variation", "variables": []}], 
    "forcedVariations": {}, "id": "7716830585"}], "version": "2", 
    "audiences": [{"conditions": "[\"and\", [\"or\", [\"or\", {\"name\": \"device_type\", \"type\": \"custom_attribute\", \"value\": \"iPhone\"}]], [\"or\", [\"or\", {\"name\": \"location\", \"type\": \"custom_attribute\", \"value\": \"San Francisco\"}]]]", "id": "7718080042", "name": "iPhone users in San Francisco"}], 
    "groups": [{"policy": "random", "trafficAllocation": [{"entityId": "", "endOfRange": 500}, {"entityId": "7723330021", "endOfRange": 2000}, {"entityId": "7718750065", "endOfRange": 6000}], "experiments": [{"status": "Running", "key": "group_experiment_1", "layerId": "7721010011", "trafficAllocation": [{"entityId": "7722260071", "endOfRange": 5000}, {"entityId": "7722360022", "endOfRange": 10000}], "audienceIds": [], "variations": [{"id": "7722260071", "key": "group_exp_1_var_1", "variables": []}, {"id": "7722360022", "key": "group_exp_1_var_2", "variables": []}], "forcedVariations": {"user1": "group_exp_1_var_1"}, "id": "7723330021"}, {"status": "Running", "key": "group_experiment_2", "layerId": "7721020020", "trafficAllocation": [{"entityId": "7713030086", "endOfRange": 5000}, {"entityId": "7725250007", "endOfRange": 10000}], "audienceIds": [], 
    "variations": [{"id": "7713030086", "key": "group_exp_2_var_1", "variables": []}, {"id": "7725250007", "key": "group_exp_2_var_2", "variables": []}], "forcedVariations": {}, "id": "7718750065"}], "id": "7722400015"}], 
    "attributes": [{"id": "7723280020", "key": "device_type"}, {"id": "7723340004", "key": "location"}], 
    "projectId": "7720880029", "accountId": "1592310167", 
    "events": [{"experimentIds": ["7716830082", "7723330021", "7718750065", "7716830585"], "id": "7718020063", "key": "purchase"}],
    "anonymizeIP": false, "variables": [{"defaultValue": "true", "type": "boolean", "id": "8284765437", "key": "is_working"}],
    "revision": "15"}');

define('DATAFILE_MORE_DATA',
    '{"experiments": [{"status": "Running", "key": "test_experiment", "layerId": "7719770039", 
    "trafficAllocation": [{"entityId": "", "endOfRange": 1500}, {"entityId": "7722370027", "endOfRange": 4000}, 
    {"entityId": "7721010009", "endOfRange": 8000}], "audienceIds": ["7718080042"], 
    "variations": [{"id": "7722370027", "key": "control"}, {"id": "7721010009", "key": "variation"}], 
    "forcedVariations": {"user1": "control"}, "id": "7716830082"}, {"status": "Paused", "key": "paused_experiment", "layerId": "7719779139", 
    "trafficAllocation": [{"entityId": "7722370427", "endOfRange": 5000}, 
    {"entityId": "7721010509", "endOfRange": 8000}], "audienceIds": [], 
    "variations": [{"id": "7722370427", "key": "control"}, {"id": "7721010509", "key": "variation", "some_additiona_key": "some_additional_value"}], 
    "forcedVariations": {}, "id": "7716830585"}], "version": "2", 
    "audiences": [{"conditions": "[\"and\", [\"or\", [\"or\", {\"name\": \"device_type\", \"type\": \"custom_attribute\", \"value\": \"iPhone\"}]], [\"or\", [\"or\", {\"name\": \"location\", \"type\": \"custom_attribute\", \"value\": \"San Francisco\"}]]]", "id": "7718080042", "name": "iPhone users in San Francisco"}], 
    "groups": [{"policy": "random", "trafficAllocation": [{"entityId": "", "endOfRange": 500}, {"entityId": "7723330021", "endOfRange": 2000}, {"entityId": "7718750065", "endOfRange": 6000}], "experiments": [{"status": "Running", "key": "group_experiment_1", "layerId": "7721010011", "trafficAllocation": [{"entityId": "7722260071", "endOfRange": 5000}, {"entityId": "7722360022", "endOfRange": 10000}], "audienceIds": [], "variations": [{"id": "7722260071", "key": "group_exp_1_var_1"}, {"id": "7722360022", "key": "group_exp_1_var_2"}], "forcedVariations": {"user1": "group_exp_1_var_1"}, "id": "7723330021"}, {"status": "Running", "key": "group_experiment_2", "layerId": "7721020020", "trafficAllocation": [{"entityId": "7713030086", "endOfRange": 5000}, {"entityId": "7725250007", "endOfRange": 10000}], "audienceIds": [], 
    "variations": [{"id": "7713030086", "key": "group_exp_2_var_1"}, {"id": "7725250007", "key": "group_exp_2_var_2"}], "forcedVariations": {}, "id": "7718750065"}], "id": "7722400015"}], 
    "attributes": [{"id": "7723280020", "key": "device_type"}, {"id": "7723340004", "key": "location"}], 
    "projectId": "7720880029", "accountId": "1592310167", 
    "events": [{"experimentIds": ["7716830082", "7723330021", "7718750065", "7716830585"], "id": "7718020063", "key": "purchase"}],"anonymizeIP": false, 
    "revision": "15", "random_data_key": [{"key_1": "value_1", "key_2": "value_2"}]}');

/**
 * Class TestBucketer
 * Extending Bucketer for the sake of tests.
 * In PHP we cannot mock private/protected methods and so this was the most novel way to test.
 *
 * @package Optimizely\Tests
 */
class TestBucketer extends Bucketer
{
    private $values;

    public function setBucketValues($values)
    {
        $this->values = $values;
    }

    public function generateBucketValue($bucketingId)
    {
        return array_shift($this->values);
    }
}


class ValidEventDispatcher implements EventDispatcherInterface
{
    public function dispatchEvent(LogEvent $event) {}
}

class InvalidEventDispatcher
{
    public function dispatchEvent(LogEvent $event) {}
}

class InvalidLogger
{
    public function log($logLevel, $logMessage) {}
}

class InvalidErrorHandler
{
    public function handleError(Exception $error) {}
}
