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

namespace Optimizely\ProjectConfigManager;

use Optimizely\ErrorHandler\ErrorHandlerInterface;
use Optimizely\Logger\LoggerInterface;
use Optimizely\Utils\ConfigUtils;

/**
 * Project config manager that returns ProjectConfig based on provided datafile.
 */
class StaticProjectConfigManager implements ProjectConfigManagerInterface
{
	/**
     * @var ProjectConfig JSON datafile string.
     */
	private $_config;
	
	/**
     * @var LoggerInterface JSON datafile string.
     */
	private $_logger;
	
	/**
     * @var ErrorHandlerInterface JSON datafile string.
     */
	private $_errorHandler;

    /**
     * Initialize config manager. Datafile has to be provided to use.
     * 
     * @param string                $datafile           JSON string representing the Optimizely project.
	 * @param bool                  $skipJsonValidation boolean representing whether JSON schema validation needs to be performed.
     * @param LoggerInterface       $loggger            Logger instance
     * @param ErrorHandlerInterface $errorHandler       ErrorHandler instance.
     */
	public function __construct($datafile, $skipJsonValidation, $loggger, $errorHandler)
    {
		$this->_logger = $loggger;
		$this->_errorHandler = $errorHandler;
		$this->_config = ConfigUtils::createProjectConfigFromDatafile($datafile, $skipJsonValidation, $loggger, $errorHandler);
	}

	/**
	 * Returns instance of ProjectConfig.
	 * @return null|ProjectConfig ProjectConfig instance. 
	 */
	public function getConfig() {
		return $this->_config;
	}
}