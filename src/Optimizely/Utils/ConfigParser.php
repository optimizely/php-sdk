<?php
/**
 * Copyright 2016-2017, Optimizely
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

class ConfigParser
{

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
