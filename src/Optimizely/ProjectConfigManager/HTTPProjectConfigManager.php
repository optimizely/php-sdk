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
use Optimizely\Config\DatafileProjectConfig;
use Optimizely\Enums\ProjectConfigManagerConstants;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Logger\NoOpLogger;
use Optimizely\Utils\Validator;

class HTTPProjectConfigManager implements ProjectConfigManagerInterface
{
    /**
     * @var \GuzzleHttp\Client Guzzle HTTP client to send requests.
     */
    private $httpClient;

    /**
     * @var DatafileProjectConfig
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
            $this->_config = DatafileProjectConfig::createProjectConfigFromDatafile(
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

        if (!Validator::validateNonEmptyString($urlTemplate)) {
            $urlTemplate = ProjectConfigManagerConstants::DEFAULT_URL_TEMPLATE;
        }

        $url = sprintf($urlTemplate, $sdkKey);

        return $url;
    }

    /**
     * Function to fetch latest datafile.
     *
     * @return boolean flag to indicate if datafile is updated.
     */
    public function fetch()
    {
        $datafile = $this->fetchDatafile();

        if ($datafile === null) {
            return false;
        }

        return true;
    }

    /**
     * Helper function to fetch datafile and handle response if datafile is modified.
     *
     * @return null|datafile.
     */
    protected function fetchDatafile()
    {
        $headers = null;

        // Add If-Modified-Since header.
        if (Validator::validateNonEmptyString($this->_lastModifiedSince)) {
            $headers = array(ProjectConfigManagerConstants::IF_MODIFIED_SINCE => $this->_lastModifiedSince);
        }

        $options = [
            'headers' => $headers,
            'timeout' => ProjectConfigManagerConstants::TIMEOUT,
            'connect_timeout' => ProjectConfigManagerConstants::TIMEOUT
        ];

        try {
            $response = $this->httpClient->get($this->_url, $options);
        } catch (Exception $exception) {
            $this->_logger->log(Logger::ERROR, 'Unexpected response when trying to fetch datafile, status code: ' . $exception->getCode());
            return null;
        }

        $status = $response->getStatusCode();

        // Datafile not updated.
        if ($status === 304) {
            $this->_logger->log(Logger::DEBUG, 'Not updating ProjectConfig as datafile has not updated since ' . $this->_lastModifiedSince);
            return null;
        }

        // Datafile retrieved successfully.
        if ($status >= 200 && $status < 300) {
            if ($response->hasHeader(ProjectConfigManagerConstants::LAST_MODIFIED)) {
                $this->_lastModifiedSince = $response->getHeader(ProjectConfigManagerConstants::LAST_MODIFIED)[0];
            }

            $datafile = $response->getBody();

            if ($this->handleResponse($datafile) === true) {
                return $datafile;
            }

            return null;
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

        $config = DatafileProjectConfig::createProjectConfigFromDatafile($datafile, $this->_skipJsonValidation, $this->_logger, $this->_errorHandler);
        if ($config === null) {
            return false;
        }

        $previousRevision = null;
        if ($this->_config !== null) {
            $previousRevision = $this->_config->getRevision();
        }

        if ($previousRevision === $config->getRevision()) {
            return false;
        }

        $this->_config = $config;
        return true;
    }

    /**
     * Returns instance of DatafileProjectConfig.
     * @return null|DatafileProjectConfig DatafileProjectConfig instance.
     */
    public function getConfig()
    {
        return $this->_config;
    }
}
