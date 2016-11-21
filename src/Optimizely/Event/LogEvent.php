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

namespace Optimizely\Event;

class LogEvent
{
    /**
     * @var string URL to dispatch log event to.
     */
    private $_url;

    /**
     * @var array Parameters to be set in the log event.
     */
    private $_params;

    /**
     * @var string HTTP verb to be used when dispatching the log event.
     */
    private $_httpVerb;

    /**
     * @var array Headers to be set when sending the request.
     */
    private $_headers;

    /**
     * LogEvent constructor.
     *
     * @param $url
     * @param $params
     * @param $httpVerb
     * @param $headers
     */
    public function __construct($url, $params, $httpVerb, $headers)
    {
        $this->_url = $url;
        $this->_params = $params;
        $this->_httpVerb = $httpVerb;
        $this->_headers = $headers;
    }

    /**
     * @return string The URL to dispatch the request to.
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * @return array The parameters to send with the request.
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * @return string HTTP verb to be used when dispatching the request.
     */
    public function getHttpVerb()
    {
        return $this->_httpVerb;
    }

    /**
     * @return array The headers to set for the request.
     */
    public function getHeaders()
    {
        return $this->_headers;
    }
}
