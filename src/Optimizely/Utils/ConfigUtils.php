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

use Exception;
use Monolog\Logger;
use Optimizely\ErrorHandler\ErrorHandlerInterface;
use Optimizely\Exceptions\InvalidDatafileVersionException;
use Optimizely\Exceptions\InvalidInputException;
use Optimizely\Logger\LoggerInterface;
use Optimizely\Logger\DefaultLogger;
use Optimizely\ProjectConfig;

class ConfigUtils
{
    /**
     * Create ProjectConfig based on datafile string.
     *
     * @param string                $datafile           JSON string representing the Optimizely project.
     * @param bool                  $skipJsonValidation boolean representing whether JSON schema validation needs to be performed.
     * @param LoggerInterface       $logger             Logger instance
     * @param ErrorHandlerInterface $errorHandler       ErrorHandler instance.
     * @return ProjectConfig ProjectConfig instance or null;
     */
    public static function createProjectConfigFromDatafile($datafile, $skipJsonValidation, $logger, $errorHandler)
    {
        if (!$skipJsonValidation) {
            if (!Validator::validateJsonSchema($datafile)) {
                $defaultLogger = new DefaultLogger();
                $defaultLogger->log(Logger::ERROR, 'Provided "datafile" has invalid schema.');
                $logger->log(Logger::ERROR, 'Provided "datafile" has invalid schema.');
                return null;
            }
        }

        try {
            $config = new ProjectConfig($datafile, $logger, $errorHandler);
        } catch (Exception $exception) {
            $defaultLogger = new DefaultLogger();
            $errorMsg = $exception->getCode() == InvalidDatafileVersionException::class ? $exception->getMessage() : sprintf(Errors::INVALID_FORMAT, 'datafile');
            $errorToHandle = $exception->getCode() == InvalidDatafileVersionException::class ? new InvalidDatafileVersionException($errorMsg) : new InvalidInputException($errorMsg);
            $defaultLogger->log(Logger::ERROR, $errorMsg);
            $logger->log(Logger::ERROR, $errorMsg);
            $errorHandler->handleError($errorToHandle);
            return null;
        }

        return $config;
    }

    /**
     * @param $entities array Entities to be stored as objects.
     * @param $entityId string ID to be used in generating map.
     * @param $entityClass string Class of entities.
     *
     * @return array Map mapping entity identifier to the entity.
     */
    public static function generateMap($entities, $entityId, $entityClass)
    {
        $entityMap = [];
        foreach ($entities as $entity) {
            if ($entity instanceof $entityClass) {
                $entityObject = $entity;
            } else {
                $entityObject = new $entityClass;
                foreach ($entity as $key => $value) {
                    $propSetter = 'set'.ucfirst($key);
                    if (method_exists($entityObject, $propSetter)) {
                        $entityObject->$propSetter($value);
                    }
                }
            }

            if (is_null($entityId)) {
                array_push($entityMap, $entityObject);
            } else {
                $propKeyGetter = 'get'.ucfirst($entityId);
                $entityMap[$entityObject->$propKeyGetter()] = $entityObject;
            }
        }

        return $entityMap;
    }
}
