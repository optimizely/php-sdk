<?php
/**
 * Copyright 2017, Optimizely
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
namespace Optimizely\DecisionService;

use Monolog\Logger;
use Optimizely\Bucketer;
use Optimizely\Entity\Experiment;
use Optimizely\Entity\Variation;
use Optimizely\Logger\LoggerInterface;
use Optimizely\ProjectConfig;
use Optimizely\Utils\Validator;

/**
 * Optimizely's decision service that determines which variation of an experiment the user will be allocated to.
 *
 * The decision service contains all logic around how a user decision is made. This includes all of the following (in order):
 *   1. Checking experiment status
 *   2. Checking whitelisting
 *   3. Checking audience targeting
 *   4. Using Murmurhash3 to bucket the user.
 *
 * @package Optimizely
 */
class DecisionService
{
  /**
   * @var LoggerInterface
   */
  private $_logger;

  /**
   * @var ProjectConfig
   */
  private $_projectConfig;

  /**
   * @var Bucketer
   */
  private $_bucketer;

  /**
   * DecisionService constructor.
   * @param LoggerInterface $logger
   * @param ProjectConfig $projectConfig
   */
  public function __construct(LoggerInterface $logger, ProjectConfig $projectConfig)
  {
      $this->_logger = $logger;
      $this->_projectConfig = $projectConfig;
      $this->_bucketer = new Bucketer($logger);
  }

  /**
   * Determine which variation to show the user.
   *
   * @param  $experiment Experiment Experiment to get the variation for.
   * @param  $userId     string     User identifier.
   * @param  $attributes array      Attributes of the user.
   *
   * @return Variation   Variation  which the user is bucketed into.
   */
  public function getVariation(Experiment $experiment, $userId, $attributes = null)
  {
    if (!$experiment->isExperimentRunning()) {
      $this->_logger->log(Logger::INFO, sprintf('Experiment "%s" is not running.', $experiment->getKey()));
      return null;
    }

    $variation = $this->getWhitelistedVariation($experiment, $userId);
    if (!is_null($variation)) {
      return $variation;
    }

    if (!Validator::isUserInExperiment($this->_projectConfig, $experiment, $attributes)) {
        $this->_logger->log(
            Logger::INFO,
            sprintf('User "%s" does not meet conditions to be in experiment "%s".', $userId, $experiment->getKey())
        );
        return null;
    }

    $variation = $this->_bucketer->bucket($this->_projectConfig, $experiment, $userId);
    return $variation;
  }

  /**
   * Determine variation the user has been forced into.
   *
   * @param  $experiment Experiment Experiment in which user is to be bucketed.
   * @param  $userId     string     string
   *
   * @return null|Variation Representing the variation the user is forced into.
   */
  private function getWhitelistedVariation(Experiment $experiment, $userId)
  {
    // Check if user is whitelisted for a variation.
    $forcedVariations = $experiment->getForcedVariations();
    if (!is_null($forcedVariations) && isset($forcedVariations[$userId])) {
        $variationKey = $forcedVariations[$userId];
        $variation = $this->_projectConfig->getVariationFromKey($experiment->getKey(), $variationKey);
        if ($variationKey) {
            $this->_logger->log(
                Logger::INFO,
                sprintf('User "%s" is forced in variation "%s" of experiment "%s".', $userId, $variationKey, $experiment->getKey())
            );
        }
        return $variation;
    }
    return null;
  }
}
