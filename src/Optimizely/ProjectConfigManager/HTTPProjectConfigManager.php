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

use Exception;
use GuzzleHttp\Client as HttpClient;
use Monolog\Logger;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Logger\NoOpLogger;
use Optimizely\ProjectConfig;
use Optimizely\Utils\Validator;

class HTTPProjectConfigManager implements ProjectConfigManagerInterface
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
     * @var \GuzzleHttp\Client Guzzle HTTP client to send requests.
     */
    private $httpClient;

    /**
     * @var ProjectConfig
     */
    private $_config;

    /**
     * @var String Datafile URL.
     */
    private $_url;

    /**
     * @var boolean Flag indicates that skip JSON validation of datafile.
     */
    private $_skipJsonValidation;

    /**
     * @var String datafile last modified time.
     */
    private $_lastModifiedSince;
    
    /**
     * @var LoggerInterface Logger instance.
     */
    private $_logger;
    
    /**
     * @var ErrorHandlerInterface ErrorHandler instance.
     */
    private $_errorHandler;

    public function __construct(
        $sdkKey = null,
        $url = null,
        $urlTemplate = null,
        $fetchOnInit = true,
        $datafile = null,
        $skipJsonValidation = false,
        $logger = null,
        $errorHandler = null
    ) {
        $this->_skipJsonValidation = $skipJsonValidation;
        $this->_logger = $logger;
        $this->_errorHandler = $errorHandler;
        $this->httpClient = new HttpClient();
        
        if ($this->_logger === null) {
            $this->_logger = new NoOpLogger();
        }

        if ($this->_errorHandler === null) {
            $this->_errorHandler = new NoOpErrorHandler();
        }

        $this->_url = $this->getUrl($sdkKey, $url, $urlTemplate);

        if ($datafile !== null) {
            $this->_config = ProjectConfig::createProjectConfigFromDatafile(
                $datafile,
                $skipJsonValidation,
                $this->_logger,
                $this->_errorHandler
            );
        }

        // Update config on initialization.
        if ($fetchOnInit === true) {
            $this->fetch();
        }
    }

    /**
     * Helper function to return URL based on params passed.
     *
     * @param $sdkKey string SDK key.
     * @param $url string URL for datafile.
     * @param $urlTemplate string Template to be used with SDK key to fetch datafile.
     *
     * @return string URL for datafile.
     */
    protected function getUrl($sdkKey, $url, $urlTemplate)
    {
        if (Validator::validateNonEmptyString($url)) {
            return $url;
        }

        if (!Validator::validateNonEmptyString($sdkKey)) {
            $exception = new Exception("One of the SDK key or URL must be provided.");
            $this->_errorHandler->handleError($exception);
            throw $exception;
        }

        // Use default URL template if no template is provided.
        if (Validator::validateNonEmptyString($urlTemplate)) {
            $url = sprintf($urlTemplate, $sdkKey);
        } else {
            $url = sprintf(HTTPProjectConfigManager::DEFAULT_URL_TEMPLATE, $sdkKey);
        }

        return $url;
    }

    /**
     * Function to fetch latest datafile.
     *
     * @return boolean flag to indicate if datafile is updated.
     */
    public function fetch()
    {
        return $this->handleResponse($this->fetchDatafile());
    }

    /**
     * Helper function to fetch datafile if modified.
     *
     * @return null|datafile.
     */
    protected function fetchDatafile()
    {
        $headers = null;

        // Add If-Modified-Since header.
        if (Validator::validateNonEmptyString($this->_lastModifiedSince)) {
            $headers = array('If-Modified-Since' => $this->_lastModifiedSince);
        }

        $options = [
            'headers' => $headers,
            'timeout' => HTTPProjectConfigManager::TIMEOUT,
            'connect_timeout' => HTTPProjectConfigManager::TIMEOUT
        ];

        $response = $this->httpClient->get($this->_url, $options);
        $status = $response->getStatusCode();

        // Datafile not updated.
        if ($status === 304) {
            $this->_logger->log(Logger::DEBUG, 'Not updating ProjectConfig as datafile has not updated since ' . $this->_lastModifiedSince);
            return null;
        }

        // Datafile retrieved successfully.
        if ($status >= 200 && $status < 300) {
            if ($response->hasHeader('Last-Modified')) {
                $this->_lastModifiedSince = $response->getHeader('Last-Modified')[0];
            }

            return $response->getBody();
        }

        // Failed to retrieve datafile from Url.
        $this->_logger->log(Logger::ERROR, 'Unexpected response when trying to fetch datafile, status code: ' . $status);
        return null;
    }

    /**
     * Helper function to create config from datafile.
     *
     * @param string $datafile
     * @return boolean flag to indicate if config is updated.
     */
    protected function handleResponse($datafile)
    {
        if ($datafile === null) {
            return false;
        }

        $config = ProjectConfig::createProjectConfigFromDatafile($datafile, $this->_skipJsonValidation, $this->_logger, $this->_errorHandler);
        if ($config === null) {
            return false;
        }

        $this->_config = $config;
        return true;
    }

    /**
     * Returns instance of ProjectConfig.
     * @return null|ProjectConfig ProjectConfig instance.
     */
    public function getConfig()
    {
        return $this->_config;
    }
}
