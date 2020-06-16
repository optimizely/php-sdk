<?php
/**
 * Copyright 2019, Optimizely
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

namespace Optimizely\Enums;

class ProjectConfigManagerConstants
{
   /**
    * @const int Time in seconds to wait before timing out.
    */
    const TIMEOUT = 10;

   /**
    * @const String Default URL Template to use if only SDK key is provided.
    */
    const DEFAULT_URL_TEMPLATE = "https://cdn.optimizely.com/datafiles/%s.json";

    /**
    * @const  String Default URL Template to use if Access token is provided along with the SDK key.
    */
    const AUTHENTICATED_DATAFILE_URL_TEMPLATE = "https://config.optimizely.com/datafiles/auth/%s.json";

   /**
    * @const String to use while fetching the datafile.
    */
    const IF_MODIFIED_SINCE = "If-Modified-Since";

   /**
    * @const String to use while handling the response.
    */
     const LAST_MODIFIED = "Last-Modified";
}
