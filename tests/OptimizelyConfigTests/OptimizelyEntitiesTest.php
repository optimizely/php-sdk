<?php
/**
 * Copyright 2020, 2023 Optimizely
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
use Optimizely\OptimizelyConfig\OptimizelyConfig;
use Optimizely\OptimizelyConfig\OptimizelyConfigService;
use Optimizely\OptimizelyConfig\OptimizelyExperiment;
use Optimizely\OptimizelyConfig\OptimizelyFeature;
use Optimizely\OptimizelyConfig\OptimizelyVariable;
use Optimizely\OptimizelyConfig\OptimizelyVariation;
use PHPUnit\Framework\TestCase;

class OptimizelyEntitiesTest extends TestCase
{
    public function testOptimizelyConfigEntity()
    {
        $optConfig = new OptimizelyConfig(
            "20",
            ["a" => "apple"],
            ["o" => "orange"]
        );

        $this->assertEquals("20", $optConfig->getRevision());
        $this->assertEquals(["a" => "apple"], $optConfig->getExperimentsMap());
        $this->assertEquals(["o" => "orange"], $optConfig->getFeaturesMap());
        $this->assertEquals("", $optConfig->getEnvironmentKey());
        $this->assertEquals("", $optConfig->getSdkKey());

        $expectedJson = '{
            "environmentKey":"",
            "sdkKey":"",
            "revision": "20",
            "experimentsMap" : {"a": "apple"},
            "featuresMap": {"o": "orange"},
            "attributes":[],
            "audiences":[],
            "events":[],
            "datafile": null
        }';

        $expectedJson = json_encode(json_decode($expectedJson));

        $this->assertEquals($expectedJson, json_encode($optConfig));
    }

    public function testOptimizelyExperimentEntity()
    {
        $optExp = new OptimizelyExperiment(
            "id",
            "key",
            ["a" => "apple"],
            ''
        );

        $this->assertEquals("id", $optExp->getId());
        $this->assertEquals("key", $optExp->getKey());
        $this->assertEquals(["a" => "apple"], $optExp->getVariationsMap());
        $this->assertEquals('', $optExp->getExperimentAudiences());

        $expectedJson = '{
            "id": "id",
            "key" : "key",
            "audiences":"",
            "variationsMap": {"a": "apple"}
        }';

        $expectedJson = json_encode(json_decode($expectedJson));

        $this->assertEquals($expectedJson, json_encode($optExp));
    }

    public function testOptimizelyFeatureEntity()
    {
        $optFeature = new OptimizelyFeature(
            "id",
            "key",
            ["a" => "apple"],
            ["o" => "orange"],
            [],
            []
        );

        $this->assertEquals("id", $optFeature->getId());
        $this->assertEquals("key", $optFeature->getKey());
        $this->assertEquals(["a" => "apple"], $optFeature->getExperimentsMap());
        $this->assertEquals(["o" => "orange"], $optFeature->getVariablesMap());
        $this->assertEquals([], $optFeature->getExperimentRules());
        $this->assertEquals([], $optFeature->getDeliveryRules());

        $expectedJson = '{
            "id": "id",
            "key" : "key",
            "experimentRules":[],
            "deliveryRules":[],
            "experimentsMap": {"a": "apple"},
            "variablesMap": {"o": "orange"}
        }';

        $expectedJson = json_encode(json_decode($expectedJson));

        $this->assertEquals($expectedJson, json_encode($optFeature));
    }

    public function testOptimizelyVariableEntity()
    {
        $optVariable = new OptimizelyVariable(
            "id",
            "key",
            "type",
            "value"
        );

        $this->assertEquals("id", $optVariable->getId());
        $this->assertEquals("key", $optVariable->getKey());
        $this->assertEquals("type", $optVariable->getType());
        $this->assertEquals("value", $optVariable->getValue());

        $expectedJson = '{
            "id": "id",
            "key" : "key",
            "type" : "type",
            "value" : "value"
        }';

        $expectedJson = json_encode(json_decode($expectedJson));

        $this->assertEquals($expectedJson, json_encode($optVariable));
    }

    public function testOptimizelyVariationEntity()
    {
        $optVariation = new OptimizelyVariation(
            "id",
            "key",
            true,
            ["a" => "apple"]
        );

        $this->assertEquals("id", $optVariation->getId());
        $this->assertEquals("key", $optVariation->getKey());
        $this->assertEquals(true, $optVariation->getFeatureEnabled());
        $this->assertEquals(["a" => "apple"], $optVariation->getVariablesMap());

        $expectedJson = '{
            "id": "id",
            "key" : "key",
            "featureEnabled" : true,
            "variablesMap": {"a": "apple"}
        }';

        $expectedJson = json_encode(json_decode($expectedJson));

        $this->assertEquals($expectedJson, json_encode($optVariation));
    }

    public function testOptimizelyVariationWithFeatureEnabledNull()
    {
        $optVariation = new OptimizelyVariation(
            "id",
            "key",
            null,
            ["a" => "apple"]
        );

        $expectedJson = '{
            "id": "id",
            "key" : "key",
            "variablesMap": {"a": "apple"}
        }';

        $expectedJson = json_encode(json_decode($expectedJson));

        $this->assertEquals($expectedJson, json_encode($optVariation));
    }
}
