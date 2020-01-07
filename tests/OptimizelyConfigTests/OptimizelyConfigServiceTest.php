<?php
/**
 * Copyright 2020, Optimizely
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
use Optimizely\OptimizelyConfig\OptimizelyConfig;
use Optimizely\OptimizelyConfig\OptimizelyConfigService;
use Optimizely\OptimizelyConfig\OptimizelyExperiment;
use Optimizely\OptimizelyConfig\OptimizelyFeature;
use Optimizely\OptimizelyConfig\OptimizelyVariable;
use Optimizely\OptimizelyConfig\OptimizelyVariation;

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

        $this->expectedDefaultVariableKeyMap = [];
        $this->expectedDefaultVariableKeyMap['boolean_var'] = $boolDefaultVariable;
        $this->expectedDefaultVariableKeyMap['integer_var'] = $intDefaultVariable;
        $this->expectedDefaultVariableKeyMap['double_var'] = $doubleDefaultVariable;
        $this->expectedDefaultVariableKeyMap['string_var'] = $strDefaultVariable;

        // Create variable variables map for feat_experiment variation_a
        $boolFeatVariable = new OptimizelyVariable('17252790456', 'boolean_var', 'boolean', 'true');
        $intFeatVariable = new OptimizelyVariable('17258820367', 'integer_var', 'integer', 5);
        $doubleFeatVariable = new OptimizelyVariable('17260550714', 'double_var', 'double', 5.5);
        $strFeatVariable = new OptimizelyVariable('17290540010', 'string_var', 'string', 'i am variable value');

        $this->expectedVariableKeyMap = [];
        $this->expectedVariableKeyMap['boolean_var'] = $boolFeatVariable;
        $this->expectedVariableKeyMap['integer_var'] = $intFeatVariable;
        $this->expectedVariableKeyMap['double_var'] = $doubleFeatVariable;
        $this->expectedVariableKeyMap['string_var'] = $strFeatVariable;

        // Create variations map for feat_experiment
        $this->featExpVariationMap = [];
        $this->featExpVariationMap['variation_a'] =
            new OptimizelyVariation('17289540366', 'variation_a', true, $this->expectedVariableKeyMap);
    
        $this->featExpVariationMap['variation_b'] =
            new OptimizelyVariation('17304990114', 'variation_b', false, $this->expectedDefaultVariableKeyMap);

        // create feat_experiment
        $featExperiment =
            new OptimizelyExperiment("17279300791", "feat_experiment", $this->featExpVariationMap);


        // create feature
        $experimentsMap = ['feat_experiment' => $featExperiment];
        $this->feature =
            new OptimizelyFeature(
                '17266500726',
                'test_feature',
                $experimentsMap,
                $this->expectedDefaultVariableKeyMap
            );

        // create ab experiment and variations
        $variationA = new OptimizelyVariation('17277380360', 'variation_a', null, []);
        $variationB = new OptimizelyVariation('17273501081', 'variation_b', null, []);
        $variationsMap = [];
        $variationsMap['variation_a'] = $variationA;
        $variationsMap['variation_b'] = $variationB;

        $abExperiment = new OptimizelyExperiment('17301270474', 'ab_experiment', $variationsMap);

        // create group_ab_experiment and variations
        $variationA = new OptimizelyVariation('17287500312', 'variation_a', null, []);
        $variationB = new OptimizelyVariation('17283640326', 'variation_b', null, []);
        $variationsMap = [];
        $variationsMap['variation_a'] = $variationA;
        $variationsMap['variation_b'] = $variationB;

        $groupExperiment =
            new OptimizelyExperiment('17258450439', 'group_ab_experiment', $variationsMap);

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
          "revision": "16",
          "experimentsMap": {
            "ab_experiment": {
              "id": "17301270474",
              "key": "ab_experiment",
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
                    }
                  }
                }
              }
            },
            "group_ab_experiment": {
              "id": "17258450439",
              "key": "group_ab_experiment",
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
              "experimentsMap": {
                "feat_experiment": {
                  "id": "17279300791",
                  "key": "feat_experiment",
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
                }
              }
            }
          }
        }';

        $this->assertEquals(json_encode(json_decode($expectedJSON)), json_encode($response));
    }
}
