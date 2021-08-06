<?php
/**
 * Copyright 2016-2019, 2021, Optimizely
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

use Monolog\Logger;

use Optimizely\Bucketer;
use Optimizely\DecisionService\DecisionService;
use Optimizely\Event\Dispatcher\EventDispatcherInterface;
use Optimizely\Event\LogEvent;
use Optimizely\Optimizely;
use Optimizely\ProjectConfigManager\HTTPProjectConfigManager;

define(
    'DATAFILE',
    '{
  "experiments": [
    {
      "status": "Running",
      "key": "test_experiment",
      "layerId": "7719770040",
      "trafficAllocation": [
        {
          "entityId": "",
          "endOfRange": 1500
        },
        {
          "entityId": "7722370028",
          "endOfRange": 4000
        },
        {
          "entityId": "7721010010",
          "endOfRange": 8000
        }
      ],
      "audienceIds": [
        "7718080042"
      ],
      "variations": [
        {
          "id": "7722370028",
          "key": "control"
        },
        {
          "id": "7721010010",
          "key": "variation"
        }
      ],
      "forcedVariations": {
        "user1": "control"
      },
      "id": "7716830083"
    },
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
    }, {
      "key": "test_experiment_2",
      "status": "Running",
      "layerId": "5",
      "audienceIds": [],
      "id": "111133",
      "forcedVariations": {},
      "trafficAllocation": [{
        "entityId": "151239",
        "endOfRange": 5000
      }, {
        "entityId": "151240",
        "endOfRange": 10000
      }],
      "variations": [{
        "id": "151239",
        "key": "test_variation_1",
        "featureEnabled": true,
        "variables": [
          {
            "id": "155551",
            "value": "42.42"
          }
        ]
      }, {
        "id": "151240",
        "key": "test_variation_2",
        "featureEnabled": true,
        "variables": [
          {
            "id": "155551",
            "value": "13.37"
          }
        ]
      }]
    },
    {
      "key": "test_experiment_json_feature",
      "status": "Running",
      "layerId": "5",
      "audienceIds": [

      ],
      "id": "122245",
      "forcedVariations": {

      },
      "trafficAllocation": [
        {
          "entityId": "122246",
          "endOfRange": 5000
        },
        {
          "entityId": "122240",
          "endOfRange": 10000
        }
      ],
      "variations": [
        {
          "id": "122246",
          "key": "json_variation",
          "variables": [
            {
              "id": "122247",
              "value": "{\"text\": \"variable value\"}"
            },
            {
                "id": "122248",
                "value": "13.37"
            },
            {
                "id": "122249",
                "value": "13"
            },
            {
                "id": "122250",
                "value": "string variable"
            },
            {
                "id": "122251",
                "value": "true"
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
    },
    {
      "id": "7723340007",
      "key": "boolean_key"
    },
    {
      "id": "7723340008",
      "key": "double_key"
    },
    {
      "id": "7723340009",
      "key": "integer_key"
    }
  ],
  "projectId": "7720880029",
  "accountId": "1592310167",
  "events": [
    {
      "experimentIds": [
        "7716830082",
        "7716830083",
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
    },
    {
      "experimentIds":[
        "7716830082",
        "7716830083",
        "122230"
      ],
      "id": "7718020065",
      "key": "multi_exp_event"
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
      "experimentIds": ["111133"],
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
      "id": "155597",
      "key": "multiple_variables_feature",
      "rolloutId": "",
      "experimentIds": [
        122245
      ],
      "variables": [
        {
          "id": "122247",
          "key": "json_variable",
          "type": "string",
          "subType": "json",
          "defaultValue": "{\"text\": \"default value\"}"
        },
        {
          "id": "122248",
          "key": "double_variable",
          "type": "double",
          "defaultValue": "10.37"
        },
        {
          "id": "122249",
          "key": "integer_variable",
          "type": "integer",
          "defaultValue": "10"
        },
        {
          "id": "122250",
          "key": "string_variable",
          "type": "string",
          "defaultValue": "default string variable"
        },
        {
          "id": "122251",
          "key": "boolean_variable",
          "type": "boolean",
          "defaultValue": "false"
        },
        {
          "id": "122252",
          "key": "json_type_variable",
          "type": "json",
          "defaultValue": "{\"text\": \"json_type_variable default value\"}"
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

define(
    'UNSUPPORTED_DATAFILE',
    '{
      "version": "5",
      "rollouts": [],
      "anonymizeIP": true,
      "projectId": "10431130345",
      "variables": [],
      "featureFlags": [],
      "experiments": [
        {
          "status": "Running",
          "key": "ab_running_exp_untargeted",
          "layerId": "10417730432",
          "trafficAllocation": [
            {
              "entityId": "10418551353",
              "endOfRange": 10000
            }
          ],
          "audienceIds": [],
          "variations": [
            {
              "variables": [],
              "id": "10418551353",
              "key": "all_traffic_variation"
            },
            {
              "variables": [],
              "id": "10418510624",
              "key": "no_traffic_variation"
            }
          ],
          "forcedVariations": {},
          "id": "10420810910"
        }
      ],
      "audiences": [],
      "groups": [],
      "attributes": [],
      "accountId": "10367498574",
      "events": [
        {
          "experimentIds": [
            "10420810910"
          ],
          "id": "10404198134",
          "key": "winning"
        }
      ],
      "revision": "1337"
    }'
);

define(
    'DATAFILE_WITH_TYPED_AUDIENCES',
    '{
      "version": "4",
      "rollouts": [
        {
          "experiments": [
            {
              "status": "Running",
              "key": "11488548027",
              "layerId": "11551226731",
              "trafficAllocation": [
                {
                  "entityId": "11557362669",
                  "endOfRange": 10000
                }
              ],
              "audienceIds": ["3468206642", "3988293898", "3988293899", "3468206646",
                              "3468206647", "3468206644", "3468206643"],
              "variations": [
                {
                  "variables": [],
                  "id": "11557362669",
                  "key": "11557362669",
                  "featureEnabled":true
                }
              ],
              "forcedVariations": {},
              "id": "11488548027"
            }
          ],
          "id": "11551226731"
        },
        {
          "experiments": [
            {
              "status": "Paused",
              "key": "11630490911",
              "layerId": "11638870867",
              "trafficAllocation": [
                {
                  "entityId": "11475708558",
                  "endOfRange": 0
                }
              ],
              "audienceIds": [],
              "variations": [
                {
                  "variables": [],
                  "id": "11475708558",
                  "key": "11475708558",
                  "featureEnabled":false
                }
              ],
              "forcedVariations": {},
              "id": "11630490911"
            }
          ],
          "id": "11638870867"
        },
        {
          "experiments": [
            {
              "status": "Running",
              "key": "11488548028",
              "layerId": "11551226732",
              "trafficAllocation": [
                {
                  "entityId": "11557362670",
                  "endOfRange": 10000
                }
              ],
              "audienceIds": ["0"],
              "audienceConditions": ["and", ["or", "3468206642", "3988293898"], ["or", "3988293899",
                                     "3468206646", "3468206647", "3468206644", "3468206643"]],
              "variations": [
                {
                  "variables": [],
                  "id": "11557362670",
                  "key": "11557362670",
                  "featureEnabled": true
                }
              ],
              "forcedVariations": {},
              "id": "11488548028"
            }
          ],
          "id": "11551226732"
        },
        {
          "experiments": [
            {
              "status": "Paused",
              "key": "11630490912",
              "layerId": "11638870868",
              "trafficAllocation": [
                {
                  "entityId": "11475708559",
                  "endOfRange": 0
                }
              ],
              "audienceIds": [],
              "variations": [
                {
                  "variables": [],
                  "id": "11475708559",
                  "key": "11475708559",
                  "featureEnabled": false
                }
              ],
              "forcedVariations": {},
              "id": "11630490912"
            }
          ],
          "id": "11638870868"
        }

      ],
      "anonymizeIP": false,
      "projectId": "11624721371",
      "variables": [],
      "featureFlags": [
        {
          "experimentIds": [],
          "rolloutId": "11551226731",
          "variables": [],
          "id": "11477755619",
          "key": "feat"
        },
        {
          "experimentIds": [
            "11564051718"
          ],
          "rolloutId": "11638870867",
          "variables": [
            {
              "defaultValue": "x",
              "type": "string",
              "id": "11535264366",
              "key": "x"
            }
          ],
          "id": "11567102051",
          "key": "feat_with_var"
        },
        {
            "experimentIds": [],
            "rolloutId": "11551226732",
            "variables": [],
            "id": "11567102052",
            "key": "feat2"
        },
        {
          "experimentIds": ["1323241599"],
          "rolloutId": "11638870868",
          "variables": [
            {
              "defaultValue": "10",
              "type": "integer",
              "id": "11535264367",
              "key": "z"
            }
          ],
          "id": "11567102053",
          "key": "feat2_with_var"
        }
      ],
      "experiments": [
        {
          "status": "Running",
          "key": "feat_with_var_test",
          "layerId": "11504144555",
          "trafficAllocation": [
            {
              "entityId": "11617170975",
              "endOfRange": 10000
            }
          ],
          "audienceIds": ["3468206642", "3988293898", "3988293899", "3468206646",
                          "3468206647", "3468206644", "3468206643"],
          "variations": [
            {
              "variables": [
                {
                  "id": "11535264366",
                  "value": "xyz"
                }
              ],
              "id": "11617170975",
              "key": "variation_2",
              "featureEnabled": true
            }
          ],
          "forcedVariations": {},
          "id": "11564051718"
        },
        {
          "id": "1323241597",
          "key": "typed_audience_experiment",
          "layerId": "1630555627",
          "status": "Running",
          "variations": [
            {
              "id": "1423767503",
              "key": "A",
              "variables": []
            }
          ],
          "trafficAllocation": [
            {
              "entityId": "1423767503",
              "endOfRange": 10000
            }
          ],
          "audienceIds": ["3468206642", "3988293898", "3988293899", "3468206646",
                          "3468206647", "3468206644", "3468206643"],
          "forcedVariations": {}
        },
        {
          "id": "1323241598",
          "key": "audience_combinations_experiment",
          "layerId": "1323241598",
          "status": "Running",
          "variations": [
            {
              "id": "1423767504",
              "key": "A",
              "variables": []
            }
          ],
          "trafficAllocation": [
            {
              "entityId": "1423767504",
              "endOfRange": 10000
            }
          ],
          "audienceIds": ["0"],
          "audienceConditions": ["and", ["or", "3468206642", "3988293898"], ["or", "3988293899",
                                 "3468206646", "3468206647", "3468206644", "3468206643"]],
          "forcedVariations": {}
        },
        {
          "id": "1323241599",
          "key": "feat2_with_var_test",
          "layerId": "1323241600",
          "status": "Running",
          "variations": [
            {
              "variables": [
                {
                  "id": "11535264367",
                  "value": "150"
                }
              ],
              "id": "1423767505",
              "key": "variation_2",
              "featureEnabled": true
            }
          ],
          "trafficAllocation": [
            {
              "entityId": "1423767505",
              "endOfRange": 10000
            }
          ],
          "audienceIds": ["0"],
          "audienceConditions": ["and", ["or", "3468206642", "3988293898"], ["or", "3988293899", "3468206646",
                                                                             "3468206647", "3468206644", "3468206643"]],
          "forcedVariations": {}
            }
      ],
      "audiences": [
        {
          "id": "3468206642",
          "name": "exactString",
          "conditions": "[\"and\", [\"or\", [\"or\", {\"name\": \"house\", \"type\": \"custom_attribute\", \"value\": \"Gryffindor\"}]]]"
        },
        {
          "id": "3988293898",
          "name": "$$dummySubstringString",
          "conditions": "{ \"type\": \"custom_attribute\", \"name\": \"$opt_dummy_attribute\", \"value\": \"impossible_value\" }"
        },
        {
          "id": "3988293899",
          "name": "$$dummyExists",
          "conditions": "{ \"type\": \"custom_attribute\", \"name\": \"$opt_dummy_attribute\", \"value\": \"impossible_value\" }"
        },
        {
          "id": "3468206646",
          "name": "$$dummyExactNumber",
          "conditions": "{ \"type\": \"custom_attribute\", \"name\": \"$opt_dummy_attribute\", \"value\": \"impossible_value\" }"
        },
        {
          "id": "3468206647",
          "name": "$$dummyGtNumber",
          "conditions": "{ \"type\": \"custom_attribute\", \"name\": \"$opt_dummy_attribute\", \"value\": \"impossible_value\" }"
        },
        {
          "id": "3468206644",
          "name": "$$dummyLtNumber",
          "conditions": "{ \"type\": \"custom_attribute\", \"name\": \"$opt_dummy_attribute\", \"value\": \"impossible_value\" }"
        },
        {
          "id": "3468206643",
          "name": "$$dummyExactBoolean",
          "conditions": "{ \"type\": \"custom_attribute\", \"name\": \"$opt_dummy_attribute\", \"value\": \"impossible_value\" }"
        },
        {
          "id": "3468206645",
          "name": "$$dummyMultipleCustomAttrs",
          "conditions": "{ \"type\": \"custom_attribute\", \"name\": \"$opt_dummy_attribute\", \"value\": \"impossible_value\" }"
        },
        {
          "id": "0",
          "name": "$$dummy",
          "conditions": "{ \"type\": \"custom_attribute\", \"name\": \"$opt_dummy_attribute\", \"value\": \"impossible_value\" }"
        }
      ],
      "typedAudiences": [
        {
          "id": "3988293898",
          "name": "substringString",
          "conditions": ["and", ["or", ["or", {"name": "house", "type": "custom_attribute",
                                               "match": "substring", "value": "Slytherin"}]]]
        },
        {
          "id": "3988293899",
          "name": "exists",
          "conditions": ["and", ["or", ["or", {"name": "favorite_ice_cream", "type": "custom_attribute",
                                               "match": "exists"}]]]
        },
        {
          "id": "3468206646",
          "name": "exactNumber",
          "conditions": ["and", ["or", ["or", {"name": "lasers", "type": "custom_attribute",
                                               "match": "exact", "value": 45.5}]]]
        },
        {
          "id": "3468206647",
          "name": "gtNumber",
          "conditions": ["and", ["or", ["or", {"name": "lasers", "type": "custom_attribute",
                                               "match": "gt", "value": 70}]]]
        },
        {
          "id": "3468206644",
          "name": "ltNumber",
          "conditions": ["and", ["or", ["or", {"name": "lasers", "type": "custom_attribute",
                                               "match": "lt", "value": 1.0}]]]
        },
        {
          "id": "3468206643",
          "name": "exactBoolean",
          "conditions": ["and", ["or", ["or", {"name": "should_do_it", "type": "custom_attribute",
                                               "match": "exact", "value": true}]]]
        },
        {
          "id": "3468206645",
          "name": "multiple_custom_attrs",
          "conditions": ["and", ["or", ["or", {"type": "custom_attribute", "name": "browser", "value": "chrome"}, {"type": "custom_attribute", "name": "browser", "value": "firefox"}]]]
        }
      ],
      "groups": [],
      "attributes": [
        {
          "key": "house",
          "id": "594015"
        },
        {
          "key": "lasers",
          "id": "594016"
        },
        {
          "key": "should_do_it",
          "id": "594017"
        },
        {
          "key": "favorite_ice_cream",
          "id": "594018"
        }
      ],
      "botFiltering": false,
      "accountId": "4879520872",
      "events": [
        {
          "key": "item_bought",
          "id": "594089",
          "experimentIds": [
            "11564051718",
            "1323241597"
          ]
        },
        {
          "key": "user_signed_up",
          "id": "594090",
          "experimentIds": ["1323241598", "1323241599"]
        }
      ],
      "revision": "3"
  }'
);

define(
    'DATAFILE_FOR_OPTIMIZELY_CONFIG',
    '{
      "version": "4",
      "rollouts": [
        {
          "experiments": [
            {
              "status": "Running",
              "audienceIds": [
                
              ],
              "variations": [
                {
                  "variables": [
                    {
                      "id": "17252790456",
                      "value": "false"
                    },
                    {
                      "id": "17258820367",
                      "value": "1"
                    },
                    {
                      "id": "17290540010",
                      "value": "i am default value"
                    },
                    {
                      "id": "17260550714",
                      "value": "0.5"
                    },
                    {
                      "id": "17260550458",
                      "value": "{\"text\": \"default value\"}"
                    }
                  ],
                  "id": "17285550838",
                  "key": "17285550838",
                  "featureEnabled": true
                }
              ],
              "id": "17268110732",
              "key": "17268110732",
              "layerId": "17271811066",
              "trafficAllocation": [
                {
                  "entityId": "17285550838",
                  "endOfRange": 10000
                }
              ],
              "forcedVariations": {
                
              }
            }
          ],
          "id": "17271811066"
        }
      ],
      "typedAudiences": [
        
      ],
      "anonymizeIP": true,
      "projectId": "17285070103",
      "variables": [
        
      ],
      "featureFlags": [
        {
          "experimentIds": [
            "17279300791"
          ],
          "rolloutId": "17271811066",
          "variables": [
            {
              "defaultValue": "false",
              "type": "boolean",
              "id": "17252790456",
              "key": "boolean_var"
            },
            {
              "defaultValue": "1",
              "type": "integer",
              "id": "17258820367",
              "key": "integer_var"
            },
            {
              "defaultValue": "0.5",
              "type": "double",
              "id": "17260550714",
              "key": "double_var"
            },
            {
              "defaultValue": "i am default value",
              "type": "string",
              "id": "17290540010",
              "key": "string_var"
            },
            {
              "id": "17260550458",
              "key": "json_var",
              "type": "string",
              "subType": "json",
              "defaultValue": "{\"text\": \"default value\"}"
            }
          ],
          "id": "17266500726",
          "key": "test_feature"
        }
      ],
      "experiments": [
        {
          "status": "Running",
          "audienceIds": [
            
          ],
          "variations": [
            {
              "variables": [
                
              ],
              "id": "17277380360",
              "key": "variation_a"
            },
            {
              "variables": [
                
              ],
              "id": "17273501081",
              "key": "variation_b"
            }
          ],
          "id": "17301270474",
          "key": "ab_experiment",
          "layerId": "17266330800",
          "trafficAllocation": [
            {
              "entityId": "17273501081",
              "endOfRange": 2500
            },
            {
              "entityId": "",
              "endOfRange": 5000
            },
            {
              "entityId": "17277380360",
              "endOfRange": 7500
            },
            {
              "entityId": "",
              "endOfRange": 10000
            }
          ],
          "forcedVariations": {
            
          }
        }
      ],
      "audiences": [
        {
          "conditions": "[\"or\", {\"match\": \"exact\", \"name\": \"$opt_dummy_attribute\", \"type\": \"custom_attribute\", \"value\": \"$opt_dummy_value\"}]",
          "id": "$opt_dummy_audience",
          "name": "Optimizely-Generated Audience for Backwards Compatibility"
        },
        {
          "id": "3468206642",
          "name": "exactString",
          "conditions": "[\"and\", [\"or\", [\"or\", {\"name\": \"house\", \"type\": \"custom_attribute\", \"value\": \"Gryffindor\"}]]]"
        },
        {
          "id": "3988293898",
          "name": "$$dummySubstringString",
          "conditions": "{ \"type\": \"custom_attribute\", \"name\": \"$opt_dummy_attribute\", \"value\": \"impossible_value\" }"
        },
        {
          "id": "3988293899",
          "name": "$$dummyExists",
          "conditions": "{ \"type\": \"custom_attribute\", \"name\": \"$opt_dummy_attribute\", \"value\": \"impossible_value\" }"
        }

      ],
      "groups": [
        {
          "policy": "random",
          "trafficAllocation": [
            {
              "entityId": "17279300791",
              "endOfRange": 5000
            },
            {
              "entityId": "17258450439",
              "endOfRange": 10000
            }
          ],
          "experiments": [
            {
              "status": "Running",
              "audienceIds": [
                
              ],
              "variations": [
                {
                  "variables": [
                    {
                      "id": "17252790456",
                      "value": "true"
                    },
                    {
                      "id": "17258820367",
                      "value": "5"
                    },
                    {
                      "id": "17290540010",
                      "value": "i am variable value"
                    },
                    {
                      "id": "17260550714",
                      "value": "5.5"
                    },
                    {
                      "id": "17260550458",
                      "value": "{\"text\": \"variable value\"}"
                    }
                  ],
                  "id": "17289540366",
                  "key": "variation_a",
                  "featureEnabled": true
                },
                {
                  "variables": [
                    
                  ],
                  "id": "17304990114",
                  "key": "variation_b",
                  "featureEnabled": false
                }
              ],
              "id": "17279300791",
              "key": "feat_experiment",
              "layerId": "17267970413",
              "trafficAllocation": [
                {
                  "entityId": "17289540366",
                  "endOfRange": 5000
                },
                {
                  "entityId": "17304990114",
                  "endOfRange": 10000
                }
              ],
              "forcedVariations": {
                
              }
            },
            {
              "status": "Running",
              "audienceIds": [
                
              ],
              "variations": [
                {
                  "variables": [
                    
                  ],
                  "id": "17287500312",
                  "key": "variation_a"
                },
                {
                  "variables": [
                    
                  ],
                  "id": "17283640326",
                  "key": "variation_b"
                }
              ],
              "id": "17258450439",
              "key": "group_ab_experiment",
              "layerId": "17294040003",
              "trafficAllocation": [
                {
                  "entityId": "17287500312",
                  "endOfRange": 5000
                },
                {
                  "entityId": "17283640326",
                  "endOfRange": 10000
                }
              ],
              "forcedVariations": {
                
              }
            }
          ],
          "id": "17262540782"
        }
      ],
      "attributes": [
        {"key": "test_attribute", "id": "111094"}
      ],
      "botFiltering": false,
      "accountId": "8272261422",
      "events": [
        {"key": "test_event", "experimentIds": ["111127"], "id": "111095"}
      ],
      "revision": "16"
  }'
);

define('DATAFILE_FOR_DUPLICATE_EXP_KEYS',
    '{
      "version": "4",
      "rollouts": [],
      "typedAudiences": [
        {
          "id": "20415611520",
          "conditions": [
            "and",
            [
              "or",
              [
                "or",
                {
                  "value": true,
                  "type": "custom_attribute",
                  "name": "hiddenLiveEnabled",
                  "match": "exact"
                }
              ]
            ]
          ],
          "name": "test1"
        },
        {
          "id": "20406066925",
          "conditions": [
            "and",
            [
              "or",
              [
                "or",
                {
                  "value": false,
                  "type": "custom_attribute",
                  "name": "hiddenLiveEnabled",
                  "match": "exact"
                }
              ]
            ]
          ],
          "name": "test2"
        }
      ],
      "anonymizeIP": true,
      "projectId": "20430981610",
      "variables": [],
      "featureFlags": [
        {
          "experimentIds": ["9300000007569"],
          "rolloutId": "",
          "variables": [],
          "id": "3045",
          "key": "flag1"
        },
        {
          "experimentIds": ["9300000007573"],
          "rolloutId": "",
          "variables": [],
          "id": "3046",
          "key": "flag2"
        }
      ],
      "experiments": [
        {
          "status": "Running",
          "audienceConditions": ["or", "20415611520"],
          "audienceIds": ["20415611520"],
          "variations": [
            {
              "variables": [],
              "id": "8045",
              "key": "variation1",
              "featureEnabled": true
            }
          ],
          "forcedVariations": {},
          "key": "targeted_delivery",
          "layerId": "9300000007569",
          "trafficAllocation": [{ "entityId": "8045", "endOfRange": 10000 }],
          "id": "9300000007569"
        },
        {
          "status": "Running",
          "audienceConditions": ["or", "20406066925"],
          "audienceIds": ["20406066925"],
          "variations": [
            {
              "variables": [],
              "id": "8048",
              "key": "variation2",
              "featureEnabled": true
            }
          ],
          "forcedVariations": {},
          "key": "targeted_delivery",
          "layerId": "9300000007573",
          "trafficAllocation": [{ "entityId": "8048", "endOfRange": 10000 }],
          "id": "9300000007573"
        }
      ],
      "audiences": [
        {
          "id": "20415611520",
          "conditions": "[\"or\", {\"match\": \"exact\", \"name\": \"$opt_dummy_attribute\", \"type\": \"custom_attribute\", \"value\": \"$opt_dummy_value\"}]",
          "name": "test1"
        },
        {
          "id": "20406066925",
          "conditions": "[\"or\", {\"match\": \"exact\", \"name\": \"$opt_dummy_attribute\", \"type\": \"custom_attribute\", \"value\": \"$opt_dummy_value\"}]",
          "name": "test2"
        },
        {
          "conditions": "[\"or\", {\"match\": \"exact\", \"name\": \"$opt_dummy_attribute\", \"type\": \"custom_attribute\", \"value\": \"$opt_dummy_value\"}]",
          "id": "$opt_dummy_audience",
          "name": "Optimizely-Generated Audience for Backwards Compatibility"
        }
      ],
      "groups": [],
      "attributes": [{ "id": "20408641883", "key": "hiddenLiveEnabled" }],
      "botFiltering": false,
      "accountId": "17882702980",
      "events": [],
      "revision": "25",
      "sendFlagDecisions": true
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
    public function sendImpressionEvent($config, $experimentId, $variationKey, $flagKey, $ruleKey, $ruleType, $enabled, $userId, $attributes)
    {
        parent::sendImpressionEvent($config, $experimentId, $variationKey, $flagKey, $ruleKey, $ruleType, $enabled, $userId, $attributes);
    }

    public function validateInputs(array $values, $logLevel = Logger::ERROR)
    {
        return parent::validateInputs($values, $logLevel);
    }

    public function getConfig()
    {
        return parent::getConfig();
    }
}

class FireNotificationTester
{
    public function decisionCallbackNoArgs()
    {
    }

    public function decisionCallbackNoArgs2()
    {
    }

    public function decisionCallbackWithArgs($anInt, $aDouble, $aString, $anArray, $aFunction)
    {
    }

    public function decisionCallbackWithArgs2($anInt, $aDouble, $aString, $anArray, $aFunction)
    {
    }

    public function trackCallbackNoArgs()
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

/**
 * Class DecisionTester
 * Extending DecisionService for the sake of tests.
 */
class DecisionTester extends DecisionService
{
    public function getBucketingId($userId, $userAttributes, &$decideReasons = [])
    {
        return parent::getBucketingId($userId, $userAttributes, $decideReasons);
    }
}

/**
 * Class HTTPProjectConfigManagerTester
 * Extending HTTPProjectConfigManager for the sake of tests.
 */
class HTTPProjectConfigManagerTester extends HTTPProjectConfigManager
{
    public function getUrl($sdkKey, $url, $urlTemplate)
    {
        return parent::getUrl($sdkKey, $url, $urlTemplate);
    }

    public function fetchDatafile()
    {
        return parent::fetchDatafile();
    }

    public function handleResponse($datafile)
    {
        return parent::handleResponse($datafile);
    }
}
