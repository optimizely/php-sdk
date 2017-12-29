<?php
/**
 * Copyright 2017, Optimizely Inc and Contributors
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
namespace Optimizely\Notification;

use ArgumentCountError;
use Exception;
use Throwable;

use Monolog\Logger;

use Optimizely\ErrorHandler\ErrorHandlerInterface;
use Optimizely\Exceptions\InvalidCallbackArgumentCountException;
use Optimizely\Exceptions\InvalidNotificationTypeException;
use Optimizely\Logger\LoggerInterface;
use Optimizely\Logger\NoOpLogger;

class NotificationCenter
{
    private $_notificationId;

    private $_notifications;

    private $_logger;

    private $_errorHandler;

    public function __construct(LoggerInterface $logger, ErrorHandlerInterface $errorHandler)
    {
        $this->_notificationId = 1;
        $this->_notifications = [];
        foreach (array_values(NotificationType::getAll()) as $type) {
            $this->_notifications[$type] = [];
        }

        $this->_logger = $logger;
        $this->_errorHandler = $errorHandler;
    }

    public function getNotifications()
    {
        return $this->_notifications;
    }

    /**
     * Adds a notification callback for a notification type to the notification center
     *
     * @param string $notification_type     One of the constants defined in NotificationType
     * @param string $notification_callback A valid PHP callback
     *
     * @return null  Given invalid notification type/callback
     *         -1    Given callback has been already added
     *         int   Notification ID for the added callback
     */
    public function addNotificationListener($notification_type, $notification_callback)
    {
        if (!NotificationType::isNotificationTypeValid($notification_type)) {
            $this->_logger->log(Logger::ERROR, "Invalid notification type.");
            $this->_errorHandler->handleError(new InvalidNotificationTypeException('Invalid notification type.'));
            return null;
        }

        if (!is_callable($notification_callback)) {
            $this->_logger->log(Logger::ERROR, "Invalid notification callback.");
            return null;
        }

        foreach (array_values($this->_notifications[$notification_type]) as $callback) {
            if ($notification_callback == $callback) {
                // Note: anonymous methods sent with the same body will be re-added.
                // Only variable and object methods can be checked for duplication
                $this->_logger->log(Logger::DEBUG, "Callback already added for notification type '{$notification_type}'.");
                return -1;
            }
        }

        $this->_notifications[$notification_type][$this->_notificationId] = $notification_callback;
        $this->_logger->log(Logger::INFO, "Callback added for notification type '{$notification_type}'.");
        $returnVal = $this->_notificationId++;
        return $returnVal;
    }

    /**
     * Removes notification callback from the notification center
     *
     * @param int $notification_id notification ID
     *
     * @return true   When callback removed
     *         false  When no callback found for the given notification ID
     */
    public function removeNotificationListener($notification_id)
    {
        foreach ($this->_notifications as $notification_type => $notifications) {
            foreach (array_keys($notifications) as $id) {
                if ($notification_id == $id) {
                    unset($this->_notifications[$notification_type][$id]);
                    $this->_logger->log(Logger::INFO, "Callback with notification ID '{$notification_id}' has been removed.");
                    return true;
                }
            }
        }

        $this->_logger->log(Logger::DEBUG, "No Callback found with notification ID '{$notification_id}'.");
        return false;
    }

    /**
     * Removes all notification callbacks for the given notification type
     *
     * @param string $notification_type One of the constants defined in NotificationType
     */
    public function clearNotifications($notification_type)
    {
        if (!NotificationType::isNotificationTypeValid($notification_type)) {
            $this->_logger->log(Logger::ERROR, "Invalid notification type.");
            $this->_errorHandler->handleError(new InvalidNotificationTypeException('Invalid notification type.'));
            return;
        }

        $this->_notifications[$notification_type] = [];
        $this->_logger->log(Logger::INFO, "All callbacks for notification type '{$notification_type}' have been removed.");
    }

    /**
     * Removes all notifications for all notification types
     * from the notification center
     */
    public function cleanAllNotifications()
    {
        foreach (array_values(NotificationType::getAll()) as $type) {
            $this->_notifications[$type] = [];
        }
    }

    /**
     * Executes all registered callbacks for the given notification type
     *
     * @param string $notification_type One of the constants defined in NotificationType
     * @param array  $args              Array of items to pass as arguments to the callback
     */
    public function sendNotifications($notification_type, array $args = [])
    {
        if (!isset($this->_notifications[$notification_type])) {
            // No exception thrown and error logged since this method will be called from
            // within the SDK
            return;
        }

        /**
         * Note: Before PHP 7, if the callback in call_user_func is called with less number of arguments than expected,
         * a warning is issued but the method is still executed with assigning null to the remaining
         * arguments. From PHP 7, ArgumentCountError is thrown in such case and the method isn't executed.
         * Therefore, we set error handler for warnings so that we raise an exception and notify the
         * user that the registered callback has more number of arguments than
         * expected. This should be done to keep a consistent behavior across all PHP versions.
         */

        set_error_handler(array($this, 'reportArgumentCountError'), E_WARNING);

        foreach (array_values($this->_notifications[$notification_type]) as $callback) {
            try {
                call_user_func_array($callback, $args);
            } catch (ArgumentCountError $e) {
                $this->reportArgumentCountError();
            } catch (Exception $e) {
                $this->_logger->log(Logger::ERROR, "Problem calling notify callback.");
            }
        }

        restore_error_handler();
    }

    /**
     * Logs and raises an exception when registered callback expects more number of arguments when executed
     */
    public function reportArgumentCountError()
    {
        $this->_logger->log(Logger::ERROR, "Problem calling notify callback.");
        $this->_errorHandler->handleError(
            new InvalidCallbackArgumentCountException('Registered callback expects more number of arguments than the actual number')
        );
    }
}
