<?php
/**
 * Copyright 2016-2018, Optimizely
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
use Optimizely\Optimizely;

define(
    'DATAFILE',
    '{
  "experiments": [
    {
      "status": "Running",
      "key": "test_experiment",
      "layerId": "7719770039",
      "trafficAllocation": [
        {
          "entityId": "",
          "endOfRange": 1500
        },
        {
          "entityId": "7722370027",
          "endOfRange": 4000
        },
        {
          "entityId": "7721010009",
          "endOfRange": 8000
        }
      ],
      "audienceIds": [
        "7718080042"
      ],
      "variations": [
        {
          "id": "7722370027",
          "key": "control"
        },
        {
          "id": "7721010009",
          "key": "variation"
        }
      ],
      "forcedVariations": {
        "user1": "control"
      },
      "id": "7716830082"
    },
    {
      "status": "Paused",
      "key": "paused_experiment",
      "layerId": "7719779139",
      "trafficAllocation": [
        {
          "entityId": "7722370427",
          "endOfRange": 5000
        },
        {
          "entityId": "7721010509",
          "endOfRange": 8000
        }
      ],
      "audienceIds": [
        
      ],
      "variations": [
        {
          "id": "7722370427",
          "key": "control"
        },
        {
          "id": "7721010509",
          "key": "variation"
        }
      ],
      "forcedVariations": {
        
      },
      "id": "7716830585"
    },
    {
      "key": "test_experiment_multivariate",
      "status": "Running",
      "layerId": "4",
      "audienceIds": [
        "11155"
      ],
      "id": "122230",
      "forcedVariations": {
        
      },
      "trafficAllocation": [
        {
          "entityId": "122231",
          "endOfRange": 2500
        },
        {
          "entityId": "122232",
          "endOfRange": 5000
        },
        {
          "entityId": "122233",
          "endOfRange": 7500
        },
        {
          "entityId": "122234",
          "endOfRange": 10000
        }
      ],
      "variations": [
        {
          "id": "122231",
          "key": "Fred",
          "variables": [
            {
              "id": "155560",
              "value": "F"
            },
            {
              "id": "155561",
              "value": "red"
            }
          ],
          "featureEnabled": true
        },
        {
          "id": "122232",
          "key": "Feorge",
          "variables": [
            {
              "id": "155560",
              "value": "F"
            },
            {
              "id": "155561",
              "value": "eorge"
            }
          ],
          "featureEnabled": true
        },
        {
          "id": "122233",
          "key": "Gred",
          "variables": [
            {
              "id": "155560",
              "value": "G"
            },
            {
              "id": "155561",
              "value": "red"
            }
          ],
          "featureEnabled": true
        },
        {
          "id": "122234",
          "key": "George",
          "variables": [
            {
              "id": "155560",
              "value": "G"
            },
            {
              "id": "155561",
              "value": "eorge"
            }
          ],
          "featureEnabled": true
        }
      ]
    },
    {
      "key": "test_experiment_with_feature_rollout",
      "status": "Running",
      "layerId": "5",
      "audienceIds": [
        
      ],
      "id": "122235",
      "forcedVariations": {
        
      },
      "trafficAllocation": [
        {
          "entityId": "122236",
          "endOfRange": 5000
        },
        {
          "entityId": "122237",
          "endOfRange": 10000
        }
      ],
      "variations": [
        {
          "id": "122236",
          "key": "control",
          "variables": [
            {
              "id": "155558",
              "value": "cta_1"
            }
          ],
          "featureEnabled": true
        },
        {
          "id": "122237",
          "key": "variation",
          "variables": [
            {
              "id": "155558",
              "value": "cta_2"
            }
          ],
          "featureEnabled": true
        }
      ]
    },
    {
      "key": "test_experiment_double_feature",
      "status": "Running",
      "layerId": "5",
      "audienceIds": [
        
      ],
      "id": "122238",
      "forcedVariations": {
        
      },
      "trafficAllocation": [
        {
          "entityId": "122239",
          "endOfRange": 5000
        },
        {
          "entityId": "122240",
          "endOfRange": 10000
        }
      ],
      "variations": [
        {
          "id": "122239",
          "key": "control",
          "variables": [
            {
              "id": "155551",
              "value": "42.42"
            }
          ],
          "featureEnabled": true
        },
        {
          "id": "122240",
          "key": "variation",
          "variables": [
            {
              "id": "155551",
              "value": "13.37"
            }
          ],
          "featureEnabled": false
        }
      ]
    },
    {
      "key": "test_experiment_integer_feature",
      "status": "Running",
      "layerId": "6",
      "audienceIds": [
        
      ],
      "id": "122241",
      "forcedVariations": {
        
      },
      "trafficAllocation": [
        {
          "entityId": "122242",
          "endOfRange": 5000
        },
        {
          "entityId": "122243",
          "endOfRange": 10000
        }
      ],
      "variations": [
        {
          "id": "122242",
          "key": "control",
          "variables": [
            {
              "id": "155553",
              "value": "42"
            }
          ],
          "featureEnabled": true
        },
        {
          "id": "122243",
          "key": "variation",
          "variables": [
            {
              "id": "155553",
              "value": "13"
            }
          ],
          "featureEnabled": true
        }
      ]
    }
  ],
  "version": "4",
  "audiences": [
    {
      "conditions": "[\"and\", [\"or\", [\"or\", {\"name\": \"device_type\", \"type\": \"custom_attribute\", \"value\": \"iPhone\"}]], [\"or\", [\"or\", {\"name\": \"location\", \"type\": \"custom_attribute\", \"value\": \"San Francisco\"}]]]",
      "id": "7718080042",
      "name": "iPhone users in San Francisco"
    },
    {
      "name": "Chrome users",
      "conditions": "[\"and\", [\"or\", [\"or\", {\"name\": \"browser_type\", \"type\": \"custom_attribute\", \"value\": \"chrome\"}]]]",
      "id": "11155"
    }
  ],
  "groups": [
    {
      "policy": "random",
      "trafficAllocation": [
        {
          "entityId": "",
          "endOfRange": 500
        },
        {
          "entityId": "7723330021",
          "endOfRange": 2000
        },
        {
          "entityId": "7718750065",
          "endOfRange": 6000
        }
      ],
      "experiments": [
        {
          "status": "Running",
          "key": "group_experiment_1",
          "layerId": "7721010011",
          "trafficAllocation": [
            {
              "entityId": "7722260071",
              "endOfRange": 5000
            },
            {
              "entityId": "7722360022",
              "endOfRange": 10000
            }
          ],
          "audienceIds": [
            
          ],
          "variations": [
            {
              "id": "7722260071",
              "key": "group_exp_1_var_1",
              "variables": [
                {
                  "id": "155563",
                  "value": "groupie_1_v1"
                }
              ],
          "featureEnabled": true
            },
            {
              "id": "7722360022",
              "key": "group_exp_1_var_2",
              "variables": [
                {
                  "id": "155563",
                  "value": "groupie_1_v2"
                }
              ],
          "featureEnabled": true
            }
          ],
          "forcedVariations": {
            "user1": "group_exp_1_var_1"
          },
          "id": "7723330021"
        },
        {
          "status": "Running",
          "key": "group_experiment_2",
          "layerId": "7721020020",
          "trafficAllocation": [
            {
              "entityId": "7713030086",
              "endOfRange": 5000
            },
            {
              "entityId": "7725250007",
              "endOfRange": 10000
            }
          ],
          "audienceIds": [
            
          ],
          "variations": [
            {
              "id": "7713030086",
              "key": "group_exp_2_var_1",
              "variables": [
                {
                  "id": "155563",
                  "value": "groupie_2_v1"
                }
              ],
          "featureEnabled": true
            },
            {
              "id": "7725250007",
              "key": "group_exp_2_var_2",
              "variables": [
                {
                  "id": "155563",
                  "value": "groupie_2_v1"
                }
              ],
          "featureEnabled": true
            }
          ],
          "forcedVariations": {
            
          },
          "id": "7718750065"
        }
      ],
      "id": "7722400015"
    }
  ],
  "attributes": [
    {
      "id": "7723280020",
      "key": "device_type"
    },
    {
      "id": "7723340004",
      "key": "location"
    },
    {
      "id": "7723340006",
      "key": "$opt_xyz"
    }
  ],
  "projectId": "7720880029",
  "accountId": "1592310167",
  "events": [
    {
      "experimentIds": [
        "7716830082",
        "7723330021",
        "7718750065",
        "7716830585"
      ],
      "id": "7718020063",
      "key": "purchase"
    },
    {
      "experimentIds": [],
      "id": "7718020064",
      "key": "unlinked_event"
    }
  ],
  "anonymizeIP": false,
  "botFiltering": true,
  "revision": "15",
  "featureFlags": [
    {
      "id": "155549",
      "key": "boolean_feature",
      "rolloutId": "",
      "experimentIds": [
        "7723330021",
        "7718750065"
      ],
      "variables": [
        
      ]
    },
    {
      "id": "155550",
      "key": "double_single_variable_feature",
      "rolloutId": "",
      "experimentIds": [
        "122238"
      ],
      "variables": [
        {
          "id": "155551",
          "key": "double_variable",
          "type": "double",
          "defaultValue": "14.99"
        }
      ]
    },
    {
      "id": "155552",
      "key": "integer_single_variable_feature",
      "rolloutId": "",
      "experimentIds": [
        "122241"
      ],
      "variables": [
        {
          "id": "155553",
          "key": "integer_variable",
          "type": "integer",
          "defaultValue": "7"
        }
      ]
    },
    {
      "id": "155554",
      "key": "boolean_single_variable_feature",
      "rolloutId": "166660",
      "experimentIds": [
        
      ],
      "variables": [
        {
          "id": "155556",
          "key": "boolean_variable",
          "type": "boolean",
          "defaultValue": "true"
        }
      ]
    },
    {
      "id": "155557",
      "key": "string_single_variable_feature",
      "rolloutId": "166661",
      "experimentIds": [
        "122235"
      ],
      "variables": [
        {
          "id": "155558",
          "key": "string_variable",
          "type": "string",
          "defaultValue": "wingardium leviosa"
        }
      ]
    },
    {
      "id": "155559",
      "key": "multi_variate_feature",
      "rolloutId": "",
      "experimentIds": [
        "122230"
      ],
      "variables": [
        {
          "id": "155560",
          "key": "first_letter",
          "type": "string",
          "defaultValue": "H"
        },
        {
          "id": "155561",
          "key": "rest_of_name",
          "type": "string",
          "defaultValue": "arry"
        }
      ]
    },
    {
      "id": "155562",
      "key": "mutex_group_feature",
      "rolloutId": "",
      "experimentIds": [
        "7723330021",
        "7718750065"
      ],
      "variables": [
        {
          "id": "155563",
          "key": "correlating_variation_name",
          "type": "string",
          "defaultValue": "null"
        }
      ]
    },
    {
      "id": "155564",
      "key": "empty_feature",
      "rolloutId": "",
      "experimentIds": [
        
      ],
      "variables": [
        
      ]
    }
  ],
  "rollouts": [
    {
      "id": "166660",
      "experiments": [
        {
          "id": "177770",
          "key": "rollout_1_exp_1",
          "status": "Running",
          "layerId": "166660",
          "audienceIds": [
            "11155"
          ],
          "variations": [
            {
              "id": "177771",
              "key": "177771",
              "variables": [
                {
                  "id": "155556",
                  "value": "true"
                }
              ],
              "featureEnabled": true
            }
          ],
          "trafficAllocation": [
            {
              "entityId": "177771",
              "endOfRange": 1000
            }
          ]
        },
        {
          "id": "177772",
          "key": "rollout_1_exp_2",
          "status": "Running",
          "layerId": "166660",
          "audienceIds": [
            "11155"
          ],
          "variations": [
            {
              "id": "177773",
              "key": "177773",
              "variables": [
                {
                  "id": "155556",
                  "value": "false"
                }
              ],
              "featureEnabled": true
            }
          ],
          "trafficAllocation": [
            {
              "entityId": "177773",
              "endOfRange": 10000
            }
          ]
        },
        {
          "id": "177776",
          "key": "rollout_1_exp_3",
          "status": "Running",
          "layerId": "166660",
          "audienceIds": [
            
          ],
          "variations": [
            {
              "id": "177778",
              "key": "177778",
              "variables": [
                {
                  "id": "155556",
                  "value": "false"
                }
              ],
              "featureEnabled": true
            }
          ],
          "trafficAllocation": [
            {
              "entityId": "177778",
              "endOfRange": 5000
            }
          ]
        }
      ]
    },
    {
      "id": "166661",
      "experiments": [
        {
          "id": "177774",
          "key": "rollout_2_exp_1",
          "status": "Running",
          "layerId": "166661",
          "audienceIds": [
            "11155"
          ],
          "variations": [
            {
              "id": "177775",
              "key": "177775",
              "variables": [
                
              ],
              "featureEnabled": true
            }
          ],
          "trafficAllocation": [
            {
              "entityId": "177775",
              "endOfRange": 1500
            }
          ]
        },
        {
          "id": "177779",
          "key": "rollout_2_exp_2",
          "status": "Running",
          "layerId": "166661",
          "audienceIds": [
            
          ],
          "variations": [
            {
              "id": "177780",
              "key": "177780",
              "variables": [
                
              ],
              "featureEnabled": true
            }
          ],
          "trafficAllocation": [
            {
              "entityId": "177780",
              "endOfRange": 1500
            }
          ]
        }
      ]
    }
  ]
}'
);

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

/**
 * Class OptimizelyTester
 * Extending Optimizely for the sake of tests.
 */
class OptimizelyTester extends Optimizely
{
    public function sendImpressionEvent($experimentKey, $variationKey, $userId, $attributes)
    {
        parent::sendImpressionEvent($experimentKey, $variationKey, $userId, $attributes);
    }
}

class FireNotificationTester
{
    public function decision_callback_no_args()
    {
    }

    public function decision_callback_no_args_2()
    {
    }

    public function decision_callback_with_args($anInt, $aDouble, $aString, $anArray, $aFunction)
    {
    }

    public function decision_callback_with_args_2($anInt, $aDouble, $aString, $anArray, $aFunction)
    {
    }

    public function track_callback_no_args()
    {
    }
}


class ValidEventDispatcher implements EventDispatcherInterface
{
    public function dispatchEvent(LogEvent $event)
    {
    }
}

class InvalidEventDispatcher
{
    public function dispatchEvent(LogEvent $event)
    {
    }
}

class InvalidLogger
{
    public function log($logLevel, $logMessage)
    {
    }
}

class InvalidErrorHandler
{
    public function handleError(Exception $error)
    {
    }
}
