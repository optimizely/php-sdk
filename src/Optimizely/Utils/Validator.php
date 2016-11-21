<?php
/**
 * Copyright 2016, Optimizely
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

use JsonSchema;


class Validator
{
    /**
     * @param $datafile string JSON string representing the project.
     *
     * @return boolean representing whether schema is valid or not.
     */
    public static function validateJsonSchema($datafile)
    {
        $jsonSchemaObject = json_decode(file_get_contents(__DIR__.'/schema.json'));
        $datafileJson = json_decode($datafile);

        $jsonValidator = new JsonSchema\Validator;
        $jsonValidator->check($datafileJson, $jsonSchemaObject);
        return $jsonValidator->isValid() ?  true : false;
    }

    /**
     * @param $attributes mixed Attributes of the user.
     *
     * @return boolean representing whether attributes are valid or not.
     */
    public static function areAttributesValid($attributes)
    {
        //TODO(ali): Implement me
        return True;
    }
}
