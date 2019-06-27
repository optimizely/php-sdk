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

namespace Optimizely\Utils;

use Monolog\Logger;
use Optimizely\ErrorHandler\ErrorHandlerInterface;
use Optimizely\Logger\LoggerInterface;
use Optimizely\ProjectConfig;

class ConfigUtils
{
	/**
	 * Create ProjectConfig based on datafile string.
	 * 
	 * @param string 		  		$datafile			JSON string representing the Optimizely project.
	 * @param bool   		  		$skipJsonValidation	boolean representing whether JSON schema validation needs to be performed.
	 * @param LoggerInterface 		$logger            	Logger instance
	 * @param ErrorHandlerInterface $errorHandler       ErrorHandler instance.
	 * @return ProjectConfig ProjectConfig instance or null;
	 */
	public static function createProjectConfigFromDatafile($datafile, $skipJsonValidation, $logger, $errorHandler)
	{
		if (!$skipJsonValidation) {
			if (!Validator::validateJsonSchema($datafile)) {
				$logger->log(Logger::ERROR, 'Provided "datafile" has invalid schema.');
				return null;
			}
		}

		try {
			$config = new ProjectConfig($datafile, $logger, $errorHandler);
		} catch (Exception $exception) {
			$errorMsg = $exception->getCode() == InvalidDatafileVersionException::class ? $exception->getMessage() : sprintf(Errors::INVALID_FORMAT, 'datafile');
			$errorToHandle = $exception->getCode() == InvalidDatafileVersionException::class ? new InvalidDatafileVersionException($errorMsg) : new InvalidInputException($errorMsg);
			$logger->log(Logger::ERROR, $errorMsg);
			$errorHandler->handleError($errorToHandle);
			return null;
		}

		return $config;
	}
}