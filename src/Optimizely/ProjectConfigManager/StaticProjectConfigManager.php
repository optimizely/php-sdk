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

use Optimizely\ErrorHandler;
use Optimizely\Logger;
use Optimizely\NotificationCenter;
/**
 * Project config manager that returns ProjectConfig based on provided datafile.
 */
class StaticProjectConfigManager implements ProjectConfigManagerInterface
{
    /**
     * @var string JSON datafile string.
     */
	private $_datafile;
	
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
     * @var NotificationCenter JSON datafile string.
     */
	private $_notificationCenter;
	
	/**
     * @var bool JSON datafile string.
     */
	private $_validateSchema;

    /**
     * Initialize config manager. Datafile has to be provided to use.
     * 
     * @param string                $datafile           JSON string representing the Optimizely project.
     * @param LoggerInterface       $loggger            Logger instance
     * @param ErrorHandlerInterface $errorHandler       ErrorHandler instance.
     * @param NotificationCenter    $notificationCenter NotificationCenter instance.
     * @param bool                  $skipJsonValidation boolean representing whether JSON schema validation needs to be performed.
     */
    public function __construct(string $datafile = null, LoggerInterface $loggger = null, ErrorHandlerInterface $errorHandler = null,
    NotificationCenter $notificationCenter = null, bool $skipJsonValidation = false)
    {
		$this->_config = null;
		$this->_logger = $loggger;
		$this->_errorHandler = $errorHandler;
		$this->_notificationCenter = $notificationCenter;
		$this->_validateSchema = !$skipJsonValidation;
		$this->setConfig($datafile);
	}
	
	/**
	 * Sets config based on response body.
	 * 
	 * @param string $datafile JSON string representing the Optimizely project.
	 */
	public function setConfig(string $datafile)
	{
		if ($this->_validateSchema) {
			if (!Validator::validateJsonSchema($datafile)) {
				$this->_logger->log(Logger::ERROR, 'Provided "datafile" has invalid schema.');
				return;
			}
		}

		try {
            $config = new ProjectConfig($datafile, $this->_logger, $this->_errorHandler);
        } catch (Exception $exception) {
			$errorMsg = $exception->getCode() == InvalidDatafileVersionException::class ? $exception->getMessage() : sprintf(Errors::INVALID_FORMAT, 'datafile');
			$errorToHandle = $exception->getCode() == InvalidDatafileVersionException::class ? new InvalidDatafileVersionException($errorMsg) : new InvalidInputException($errorMsg);
			$this->_logger->log(Logger::ERROR, $errorMsg);
			$this->_errorHandler->handleError($errorToHandle);
			return;
		}

		$previousRevision = $this->_config != null ? $this->_config.getRevision() : null;
		if ($previousRevision == $config.getRevision()) {
			return;
		}

		$this->_config = $config;
		$this->_notificationCenter.sendNotifications(NotificationType::OPTIMIZELY_CONFIG_UPDATE);
		$this->_logger->log(Logger::DEBUG, sprintf(
			'Received new datafile and updated config. Old revision number: %s, New revision number %s.',
			$previousRevision,
			$this->_config.getRevision()
		));
	}

	/**
	 * Returns instance of ProjectConfig.
	 * @return null|ProjectConfig ProjectConfig instance. 
	 */
	public function getConfig() {
		return $this->_config;
	}
}