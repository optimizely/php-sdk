<?php
/**
 * Copyright 2020-2021, Optimizely
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
use Optimizely\Config\DatafileProjectConfig;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Logger\NoOpLogger;
use Optimizely\OptimizelyConfig\OptimizelyAttribute;
use Optimizely\OptimizelyConfig\OptimizelyAudience;
use Optimizely\OptimizelyConfig\OptimizelyConfig;
use Optimizely\OptimizelyConfig\OptimizelyConfigService;
use Optimizely\OptimizelyConfig\OptimizelyEvent;
use Optimizely\OptimizelyConfig\OptimizelyExperiment;
use Optimizely\OptimizelyConfig\OptimizelyFeature;
use Optimizely\OptimizelyConfig\OptimizelyVariable;
use Optimizely\OptimizelyConfig\OptimizelyVariation;

use function GuzzleHttp\json_decode;

class OptimizelyConfigServiceTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->datafile = DATAFILE_FOR_OPTIMIZELY_CONFIG;

        $this->projectConfig = new DatafileProjectConfig(
            $this->datafile,
            new NoOpLogger(),
            new NoOpErrorHandler()
        );

        $this->optConfigService = new OptimizelyConfigService($this->projectConfig);

        // Create expected default variables map for feat_experiment variation_b
        $boolDefaultVariable = new OptimizelyVariable('17252790456', 'boolean_var', 'boolean', 'false');
        $intDefaultVariable = new OptimizelyVariable('17258820367', 'integer_var', 'integer', 1);
        $doubleDefaultVariable = new OptimizelyVariable('17260550714', 'double_var', 'double', 0.5);
        $strDefaultVariable = new OptimizelyVariable('17290540010', 'string_var', 'string', 'i am default value');
        $jsonDefaultVariable = new OptimizelyVariable('17260550458', 'json_var', 'json', "{\"text\": \"default value\"}");

        $this->expectedDefaultVariableKeyMap = [];
        $this->expectedDefaultVariableKeyMap['boolean_var'] = $boolDefaultVariable;
        $this->expectedDefaultVariableKeyMap['integer_var'] = $intDefaultVariable;
        $this->expectedDefaultVariableKeyMap['double_var'] = $doubleDefaultVariable;
        $this->expectedDefaultVariableKeyMap['string_var'] = $strDefaultVariable;
        $this->expectedDefaultVariableKeyMap['json_var'] = $jsonDefaultVariable;

        // Create variable variables map for feat_experiment variation_a
        $boolFeatVariable = new OptimizelyVariable('17252790456', 'boolean_var', 'boolean', 'true');
        $intFeatVariable = new OptimizelyVariable('17258820367', 'integer_var', 'integer', 5);
        $doubleFeatVariable = new OptimizelyVariable('17260550714', 'double_var', 'double', 5.5);
        $strFeatVariable = new OptimizelyVariable('17290540010', 'string_var', 'string', 'i am variable value');
        $jsonFeatVariable = new OptimizelyVariable('17260550458', 'json_var', 'json', "{\"text\": \"variable value\"}");

        $this->expectedVariableKeyMap = [];
        $this->expectedVariableKeyMap['boolean_var'] = $boolFeatVariable;
        $this->expectedVariableKeyMap['integer_var'] = $intFeatVariable;
        $this->expectedVariableKeyMap['double_var'] = $doubleFeatVariable;
        $this->expectedVariableKeyMap['string_var'] = $strFeatVariable;
        $this->expectedVariableKeyMap['json_var'] = $jsonFeatVariable;

        // Create variations map for feat_experiment
        $this->featExpVariationMap = [];
        $this->featExpVariationMap['variation_a'] =
            new OptimizelyVariation('17289540366', 'variation_a', true, $this->expectedVariableKeyMap);
    
        $this->featExpVariationMap['variation_b'] =
            new OptimizelyVariation('17304990114', 'variation_b', false, $this->expectedDefaultVariableKeyMap);

        // create feat_experiment
        $featExperiment =
            new OptimizelyExperiment("17279300791", "feat_experiment", $this->featExpVariationMap, '');

        //  creating optimizely Experiment for delivery rules
        $boolDeliveryFeatVariable = new OptimizelyVariable('17252790456', 'boolean_var', 'boolean', 'false');
        $intDeliveryFeatVariable = new OptimizelyVariable('17258820367', 'integer_var', 'integer', 1);
        $doubleDeliveryFeatVariable = new OptimizelyVariable('17260550714', 'double_var', 'double', 0.5);
        $strDeliveryFeatVariable = new OptimizelyVariable('17290540010', 'string_var', 'string', 'i am default value');
        $jsonDeliveryFeatVariable = new OptimizelyVariable('17260550458', 'json_var', 'json', "{\"text\": \"default value\"}");

        $this->deliveryDefaultVariableKeyMap = [];
        $this->deliveryDefaultVariableKeyMap['boolean_var'] = $boolDeliveryFeatVariable;
        $this->deliveryDefaultVariableKeyMap['integer_var'] = $intDeliveryFeatVariable;
        $this->deliveryDefaultVariableKeyMap['double_var'] = $doubleDeliveryFeatVariable;
        $this->deliveryDefaultVariableKeyMap['string_var'] = $strDeliveryFeatVariable;
        $this->deliveryDefaultVariableKeyMap['json_var'] = $jsonDeliveryFeatVariable;
        $this->deliveryExpVariationMap = [];
        $this->deliveryExpVariationMap['17285550838'] =
            new OptimizelyVariation('17285550838', '17285550838', true, $this->deliveryDefaultVariableKeyMap);

        $del_Experiment =
            new OptimizelyExperiment("17268110732", "17268110732", $this->deliveryExpVariationMap, '');
        // create feature
        $experimentsMap = ['feat_experiment' => $featExperiment];
        $experiment_rules = [$featExperiment];
        $deliver_rules = [$del_Experiment];
        $this->feature =
            new OptimizelyFeature(
                '17266500726',
                'test_feature',
                $experimentsMap,
                $this->expectedDefaultVariableKeyMap,
                $experiment_rules,
                $deliver_rules
            );

        // create ab experiment and variations
        $variationA = new OptimizelyVariation('17277380360', 'variation_a', null, []);
        $variationB = new OptimizelyVariation('17273501081', 'variation_b', null, []);
        $variationsMap = [];
        $variationsMap['variation_a'] = $variationA;
        $variationsMap['variation_b'] = $variationB;

        $abExperiment = new OptimizelyExperiment('17301270474', 'ab_experiment', $variationsMap, '');

        // create group_ab_experiment and variations
        $variationA = new OptimizelyVariation('17287500312', 'variation_a', null, []);
        $variationB = new OptimizelyVariation('17283640326', 'variation_b', null, []);
        $variationsMap = [];
        $variationsMap['variation_a'] = $variationA;
        $variationsMap['variation_b'] = $variationB;

        $groupExperiment =
            new OptimizelyExperiment('17258450439', 'group_ab_experiment', $variationsMap, '');

        // create experiment key map
        $this->expectedExpKeyMap = [];
        $this->expectedExpKeyMap['ab_experiment'] = $abExperiment;
        $this->expectedExpKeyMap['group_ab_experiment'] = $groupExperiment;
        $this->expectedExpKeyMap['feat_experiment'] = $featExperiment;

        // create experiment id map
        $this->expectedExpIdMap = [];
        $this->expectedExpIdMap['17301270474'] = $abExperiment;
        $this->expectedExpIdMap['17258450439'] = $groupExperiment;
        $this->expectedExpIdMap['17279300791'] = $featExperiment;
    }

    protected static function getMethod($name)
    {
        $class = new \ReflectionClass('Optimizely\\OptimizelyConfig\\OptimizelyConfigService');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function testGetVariablesMapReturnsEmptyForAbExpVariation()
    {
        $abExp = $this->projectConfig->getExperimentFromKey("ab_experiment");
        $abExpVarA = $abExp->getVariations()[0];

        $getVariablesMap = self::getMethod("getVariablesMap");
        $response = $getVariablesMap->invokeArgs($this->optConfigService, array($abExp, $abExpVarA));

        $this->assertEmpty($response);
    }

    public function testGetVariablesMapReturnsVariableMapForEnabledVariation()
    {
        $featExp = $this->projectConfig->getExperimentFromKey("feat_experiment");
        $featureDisabledVar = $featExp->getVariations()[0];

        $getVariablesMap = self::getMethod("getVariablesMap");
        $response = $getVariablesMap->invokeArgs($this->optConfigService, array($featExp, $featureDisabledVar));

        $this->assertEquals($this->expectedVariableKeyMap, $response);
    }

    public function testGetVariablesMapReturnsVariableMapForDisabledVariation()
    {
        $featExp = $this->projectConfig->getExperimentFromKey("feat_experiment");
        $featureDisabledVar = $featExp->getVariations()[1];

        $getVariablesMap = self::getMethod("getVariablesMap");
        $response = $getVariablesMap->invokeArgs($this->optConfigService, array($featExp, $featureDisabledVar));

        $this->assertEquals($this->expectedDefaultVariableKeyMap, $response);
    }

    public function testGetVariationsMap()
    {
        $featExp = $this->projectConfig->getExperimentFromKey("feat_experiment");

        $getVariationsMap = self::getMethod("getVariationsMap");
        $response = $getVariationsMap->invokeArgs($this->optConfigService, array($featExp));
        $this->assertEquals($this->featExpVariationMap, $response);
    }

    public function testGetOptimizelyConfigWithDuplicateExperimentKeys()
    {
        $this->datafile = DATAFILE_FOR_DUPLICATE_EXP_KEYS;
        $this->projectConfig = new DatafileProjectConfig(
            $this->datafile,
            new NoOpLogger(),
            new NoOpErrorHandler()
        );
        $this->optConfigService = new OptimizelyConfigService($this->projectConfig);
        $optimizelyConfig = $this->optConfigService->getConfig();
        $this->assertEquals(Count($optimizelyConfig->getExperimentsMap()), 1);
        $experimentMapFlag1 = $optimizelyConfig->getFeaturesMap()['flag1']->getExperimentsMap(); // 9300000007569
        $experimentMapFlag2 = $optimizelyConfig->getFeaturesMap()['flag2']->getExperimentsMap(); // 9300000007573
        $this->assertEquals($experimentMapFlag1['targeted_delivery']->getId(), '9300000007569');
        $this->assertEquals($experimentMapFlag2['targeted_delivery']->getId(), '9300000007573');
    }

    public function testGetOptimizelyConfigWithDuplicateRuleKeys()
    {
        $this->datafile = DATAFILE_FOR_DUPLICATE_RUL_KEYS;
        $this->projectConfig = new DatafileProjectConfig(
            $this->datafile,
            new NoOpLogger(),
            new NoOpErrorHandler()
        );
        $this->optConfigService = new OptimizelyConfigService($this->projectConfig);
        
        $optimizelyConfig = $this->optConfigService->getConfig();
        $this->assertEquals(Count($optimizelyConfig->getExperimentsMap()), 0);
        $rolloutFlag1 = $optimizelyConfig->getFeaturesMap()["flag_1"]->getDeliveryRules()[0]; // 9300000004977
        $rolloutFlag2 = $optimizelyConfig->getFeaturesMap()["flag_2"]->getDeliveryRules()[0]; // 9300000004979
        $rolloutFlag3 = $optimizelyConfig->getFeaturesMap()["flag_3"]->getDeliveryRules()[0]; // 9300000004981
        $this->assertEquals($rolloutFlag1->getId(), "9300000004977");
        $this->assertEquals($rolloutFlag1->getKey(), "targeted_delivery");
        $this->assertEquals($rolloutFlag2->getId(), "9300000004979");
        $this->assertEquals($rolloutFlag2->getKey(), "targeted_delivery");
        $this->assertEquals($rolloutFlag3->getId(), "9300000004981");
        $this->assertEquals($rolloutFlag3->getKey(), "targeted_delivery");
    }

    public function testGetExperimentsMaps()
    {
        $getExperimentsMap = self::getMethod("getExperimentsMaps");
        $response = $getExperimentsMap->invokeArgs($this->optConfigService, array());

        // assert experiment key map
        $this->assertEquals($this->expectedExpKeyMap, $response[0]);

        // assert experiment id map
        $this->assertEquals($this->expectedExpIdMap, $response[1]);
    }

    public function testGetFeaturesMap()
    {
        $getFeaturesMap = self::getMethod("getFeaturesMap");
        $response = $getFeaturesMap->invokeArgs(
            $this->optConfigService,
            array($this->expectedExpIdMap)
        );

        $this->assertEquals(['test_feature' => $this->feature], $response);
        foreach ($response as $feature) {
            $this->assertInstanceof(OptimizelyFeature::class, $feature);
            $experiment_rules = $feature->getExperimentRules();
            $deliver_rules = $feature->getDeliveryRules();
            if (!empty($experiment_rules)) {
                foreach ($experiment_rules as $exp) {
                    $this->assertInstanceof(OptimizelyExperiment::class, $exp);
                }
            }
            if (!empty($deliver_rules)) {
                foreach ($deliver_rules as $del) {
                    $this->assertInstanceof(OptimizelyExperiment::class, $del);
                }
            }
        }
    }

    public function testgetExperimentAudiences()
    {
        $audienceConditions = [
            array("or", "3468206642"),
            array('or', '3468206642', '3988293899'),
            array('or', '3468206642', '3988293899', '3988293898'),
            array("not", "3468206642"),
            array('and', '3468206642', '3988293899'),
            array("and", "3468206642"),
            array('3468206642', '3988293899'),
            array("3468206642"),
            array('and', array('or', '3468206642', '3988293899'), '3988293898'),
            array('and', 'and'),
            array(),
            array('not', array('and', '3468206642', '3988293899')),
            array(
                "and", array("or", "3468206642", array(
                    'and', '3468206642', '3988293899'
                )),
                array(
                    "and", "3468206642",
                    array('or', '3468206642', '3988293899')
                )
            )
        ];

        $expectedAudienceOutputs = [
            '"' . "exactString" . '"',
            '"' . "exactString" . '"' . ' ' . 'OR' . ' ' . '"' . '$$dummyExists' . '"',
            '"' . "exactString" . '"' . ' ' . 'OR' . ' ' . '"' . '$$dummyExists' . '"' . ' ' . 'OR' . ' ' . '"' . '$$dummySubstringString' . '"',
            'NOT' . ' ' . '"' . "exactString" . '"',
            '"' . "exactString" . '"' . ' ' . 'AND' . ' ' . '"' . '$$dummyExists' . '"',
            '"' . "exactString" . '"',
            '"' . "exactString" . '"' . ' ' . 'OR' . ' ' . '"' . '$$dummyExists' . '"',
            '"' . "exactString" . '"',
            '(' . '"' . 'exactString' . '"' . ' ' . 'OR' . ' ' . '"' . '$$dummyExists' . '"' . ')'
                . ' ' . 'AND' . ' ' . '"' . '$$dummySubstringString' . '"',
            '',
            '',
            'NOT' . ' ' . '(' . '"' . "exactString" . '"' . ' ' . 'AND' . ' ' . '"' . '$$dummyExists' . '"' . ')',

            '(' . '"' . "exactString" . '"' . ' ' . 'OR' . ' ' .
                '(' . '"' . "exactString" . '"' . ' ' . 'AND' . ' ' . '"' . '$$dummyExists' . '"' . ')' . ')'
                . ' ' . 'AND' . ' ' . '(' . '"' . "exactString" . '"' . ' ' . 'AND' . ' ' . '(' .
                '"' . "exactString" . '"' . ' ' . 'OR' . ' ' . '"' . '$$dummyExists' . '"' . ')' . ')'
        ];

        for ($testNo = 0; $testNo < count($audienceConditions); $testNo++) {
            $getExperimentAudiences = self::getMethod("getSerializedAudiences");
            $response = $getExperimentAudiences->invokeArgs(
                $this->optConfigService,
                array($audienceConditions[$testNo])
            );
            $this->assertEquals($expectedAudienceOutputs[$testNo], $response);
        }
    }

    public function testgetConfigAttributes()
    {
        $getConfigAttributes = self::getMethod("getConfigAttributes");
        $response = $getConfigAttributes->invokeArgs($this->optConfigService, array());
        if (!empty($response)) {
            foreach ($response as $attr) {
                $this->assertInstanceof(OptimizelyAttribute::class, $attr);
            }
        }
    }

    public function testgetConfigAudiences()
    {
        $getConfigAudiences = self::getMethod("getConfigAudiences");
        $response = $getConfigAudiences->invokeArgs($this->optConfigService, array());
        if (!empty($response)) {
            foreach ($response as $attr) {
                $this->assertInstanceof(OptimizelyAudience::class, $attr);
            }
        }
    }


    public function testgetConfigEvents()
    {
        $getConfigEvents = self::getMethod("getConfigEvents");
        $response = $getConfigEvents->invokeArgs($this->optConfigService, array());
        if (!empty($response)) {
            foreach ($response as $event) {
                $this->assertInstanceof(OptimizelyEvent::class, $event);
            }
        }
    }

    public function testGetConfig()
    {
        $response = $this->optConfigService->getConfig();

        $this->assertInstanceof(OptimizelyConfig::class, $response);

        // assert revision
        $this->assertEquals('16', $response->getRevision());

        // assert experiments map
        $this->assertEquals($this->expectedExpKeyMap, $response->getExperimentsMap());

        // assert features map
        $this->assertEquals(['test_feature' => $this->feature], $response->getFeaturesMap());
    }

    public function testJsonEncodeofOptimizelyConfig()
    {
        $response = $this->optConfigService->getConfig();

        $expectedJSON = '{
          "environmentKey":"",
          "sdkKey":"",
          "revision": "16",
          "experimentsMap": {
            "ab_experiment": {
              "id": "17301270474",
              "key": "ab_experiment",
              "audiences":"",
              "variationsMap": {
                "variation_a": {
                  "id": "17277380360",
                  "key": "variation_a",
                  "variablesMap": [
                    
                  ]
                },
                "variation_b": {
                  "id": "17273501081",
                  "key": "variation_b",
                  "variablesMap": [
                    
                  ]
                }
              }
            },
            "feat_experiment": {
              "id": "17279300791",
              "key": "feat_experiment",
              "audiences":"",
              "variationsMap": {
                "variation_a": {
                  "id": "17289540366",
                  "key": "variation_a",
                  "featureEnabled": true,
                  "variablesMap": {
                    "boolean_var": {
                      "id": "17252790456",
                      "key": "boolean_var",
                      "type": "boolean",
                      "value": "true"
                    },
                    "integer_var": {
                      "id": "17258820367",
                      "key": "integer_var",
                      "type": "integer",
                      "value": "5"
                    },
                    "double_var": {
                      "id": "17260550714",
                      "key": "double_var",
                      "type": "double",
                      "value": "5.5"
                    },
                    "string_var": {
                      "id": "17290540010",
                      "key": "string_var",
                      "type": "string",
                      "value": "i am variable value"
                    },
                    "json_var": {
                      "id": "17260550458",
                      "key": "json_var",
                      "type": "json",
                      "value": "{\"text\": \"variable value\"}"
                    }
                  }
                },
                "variation_b": {
                  "id": "17304990114",
                  "key": "variation_b",
                  "featureEnabled": false,
                  "variablesMap": {
                    "boolean_var": {
                      "id": "17252790456",
                      "key": "boolean_var",
                      "type": "boolean",
                      "value": "false"
                    },
                    "integer_var": {
                      "id": "17258820367",
                      "key": "integer_var",
                      "type": "integer",
                      "value": "1"
                    },
                    "double_var": {
                      "id": "17260550714",
                      "key": "double_var",
                      "type": "double",
                      "value": "0.5"
                    },
                    "string_var": {
                      "id": "17290540010",
                      "key": "string_var",
                      "type": "string",
                      "value": "i am default value"
                    },
                    "json_var": {
                      "id": "17260550458",
                      "key": "json_var",
                      "type": "json",
                      "value": "{\"text\": \"default value\"}"
                    }
                  }
                }
              }
            },
            "group_ab_experiment": {
              "id": "17258450439",
              "key": "group_ab_experiment",
              "audiences":"",
              "variationsMap": {
                "variation_a": {
                  "id": "17287500312",
                  "key": "variation_a",
                  "variablesMap": [
                    
                  ]
                },
                "variation_b": {
                  "id": "17283640326",
                  "key": "variation_b",
                  "variablesMap": [
                    
                  ]
                }
              }
            }
          },
          "featuresMap": {
            "test_feature": {
              "id": "17266500726",
              "key": "test_feature",
              "experimentRules":[{"id":"17279300791","key":"feat_experiment","audiences":"","variationsMap":{"variation_a":{"id":"17289540366","key":"variation_a","featureEnabled":true,"variablesMap":{"boolean_var":{"id":"17252790456","key":"boolean_var","type":"boolean","value":"true"},"integer_var":{"id":"17258820367","key":"integer_var","type":"integer","value":"5"},"double_var":{"id":"17260550714","key":"double_var","type":"double","value":"5.5"},"string_var":{"id":"17290540010","key":"string_var","type":"string","value":"i am variable value"},"json_var":{"id":"17260550458","key":"json_var","type":"json","value":"{\"text\": \"variable value\"}"}}},"variation_b":{"id":"17304990114","key":"variation_b","featureEnabled":false,"variablesMap":{"boolean_var":{"id":"17252790456","key":"boolean_var","type":"boolean","value":"false"},"integer_var":{"id":"17258820367","key":"integer_var","type":"integer","value":"1"},"double_var":{"id":"17260550714","key":"double_var","type":"double","value":"0.5"},"string_var":{"id":"17290540010","key":"string_var","type":"string","value":"i am default value"},"json_var":{"id":"17260550458","key":"json_var","type":"json","value":"{\"text\": \"default value\"}"}}}}}],
              
              "deliveryRules":[{"id":"17268110732","key":"17268110732","audiences":"","variationsMap":{"17285550838":{"id":"17285550838","key":"17285550838","featureEnabled":true,"variablesMap":{"boolean_var":{"id":"17252790456","key":"boolean_var","type":"boolean","value":"false"},"integer_var":{"id":"17258820367","key":"integer_var","type":"integer","value":"1"},"double_var":{"id":"17260550714","key":"double_var","type":"double","value":"0.5"},"string_var":{"id":"17290540010","key":"string_var","type":"string","value":"i am default value"},"json_var":{"id":"17260550458","key":"json_var","type":"json","value":"{\"text\": \"default value\"}"}}}}}],
              "experimentsMap": {
                "feat_experiment": {
                  "id": "17279300791",
                  "key": "feat_experiment",
                  "audiences":"",
                  "variationsMap": {
                    "variation_a": {
                      "id": "17289540366",
                      "key": "variation_a",
                      "featureEnabled": true,
                      "variablesMap": {
                        "boolean_var": {
                          "id": "17252790456",
                          "key": "boolean_var",
                          "type": "boolean",
                          "value": "true"
                        },
                        "integer_var": {
                          "id": "17258820367",
                          "key": "integer_var",
                          "type": "integer",
                          "value": "5"
                        },
                        "double_var": {
                          "id": "17260550714",
                          "key": "double_var",
                          "type": "double",
                          "value": "5.5"
                        },
                        "string_var": {
                          "id": "17290540010",
                          "key": "string_var",
                          "type": "string",
                          "value": "i am variable value"
                        },
                        "json_var": {
                          "id": "17260550458",
                          "key": "json_var",
                          "type": "json",
                          "value": "{\"text\": \"variable value\"}"
                        }
                      }
                    },
                    "variation_b": {
                      "id": "17304990114",
                      "key": "variation_b",
                      "featureEnabled": false,
                      "variablesMap": {
                        "boolean_var": {
                          "id": "17252790456",
                          "key": "boolean_var",
                          "type": "boolean",
                          "value": "false"
                        },
                        "integer_var": {
                          "id": "17258820367",
                          "key": "integer_var",
                          "type": "integer",
                          "value": "1"
                        },
                        "double_var": {
                          "id": "17260550714",
                          "key": "double_var",
                          "type": "double",
                          "value": "0.5"
                        },
                        "string_var": {
                          "id": "17290540010",
                          "key": "string_var",
                          "type": "string",
                          "value": "i am default value"
                        },
                        "json_var": {
                          "id": "17260550458",
                          "key": "json_var",
                          "type": "json",
                          "value": "{\"text\": \"default value\"}"
                        }
                      }
                    }
                  }
                }
              },
              "variablesMap": {
                "boolean_var": {
                  "id": "17252790456",
                  "key": "boolean_var",
                  "type": "boolean",
                  "value": "false"
                },
                "integer_var": {
                  "id": "17258820367",
                  "key": "integer_var",
                  "type": "integer",
                  "value": "1"
                },
                "double_var": {
                  "id": "17260550714",
                  "key": "double_var",
                  "type": "double",
                  "value": "0.5"
                },
                "string_var": {
                  "id": "17290540010",
                  "key": "string_var",
                  "type": "string",
                  "value": "i am default value"
                },
                "json_var": {
                  "id": "17260550458",
                  "key": "json_var",
                  "type": "json",
                  "value": "{\"text\": \"default value\"}"
                }
              }
            }
          }
        }';

        $optimizelyConfig = json_decode($expectedJSON, true);
        $optimizelyConfig["attributes"] = [["id" => "111094", "key" => "test_attribute"]];
        $json_encoded = '[{"id":"3468206642","name":"exactString","conditions":"[\"and\", [\"or\", [\"or\", {\"name\": \"house\", \"type\": \"custom_attribute\", \"value\": \"Gryffindor\"}]]]"},{"id":"3988293898","name":"$$dummySubstringString","conditions":"{ \"type\": \"custom_attribute\", \"name\": \"$opt_dummy_attribute\", \"value\": \"impossible_value\" }"},{"id":"3988293899","name":"$$dummyExists","conditions":"{ \"type\": \"custom_attribute\", \"name\": \"$opt_dummy_attribute\", \"value\": \"impossible_value\" }"}]';
        $converted_audiences = json_decode($json_encoded, true);
        $optimizelyConfig["audiences"] = $converted_audiences;
        $optimizelyConfig["events"] = [["id" => "111095", "key" => "test_event", "experimentIds" => ["111127"]]];
        $optimizelyConfig['datafile'] =  DATAFILE_FOR_OPTIMIZELY_CONFIG;
        $this->assertEquals(json_encode($optimizelyConfig), json_encode($response));
    }

    public function testGetDatafile()
    {
        $expectedDatafile = DATAFILE_FOR_OPTIMIZELY_CONFIG;
        $actualDatafile = $this->optConfigService->getConfig()->getDatafile();
        $this->assertEquals($expectedDatafile, $actualDatafile);
    }
}
