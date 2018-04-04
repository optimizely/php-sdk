<?php
/**
 * Copyright 2017-2018, Optimizely
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
namespace Optimizely\Tests;

use Monolog\Logger;

use Optimizely\Event\Builder\EventBuilder;
use Optimizely\ErrorHandler\NoOpErrorHandler;
use Optimizely\Logger\DefaultLogger;
use Optimizely\Logger\NoOpLogger;
use Optimizely\Notification\NotificationCenter;
use Optimizely\Notification\NotificationType;
use Optimizely\Exceptions\InvalidCallbackArgumentCountException;
use Optimizely\Exceptions\InvalidNotificationTypeException;

class NotificationCenterTest extends \PHPUnit_Framework_TestCase
{
    private $notificationCenterObj;
    private $loggerMock;

    public function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(NoOpLogger::class)
            ->setMethods(array('log'))
            ->getMock();

        $this->errorHandlerMock = $this->getMockBuilder(NoOpErrorHandler::class)
            ->setMethods(array('handleError'))
            ->getMock();

        $this->notificationCenterObj = new NotificationCenter($this->loggerMock, $this->errorHandlerMock);
    }

    public function testAddNotificationWithInvalidParams()
    {
        // should log, throw an exception  and return null if invalid notification type given
        $invalid_type = "HelloWorld";

        $this->errorHandlerMock->expects($this->at(0))
            ->method('handleError')
            ->with(new InvalidNotificationTypeException('Invalid notification type.'));

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::ERROR, "Invalid notification type.");

        $this->assertNull($this->notificationCenterObj->addNotificationListener($invalid_type, function () {
        }));

        // should log and return null if invalid callable given
        $invalid_callable = "HelloWorld";
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::ERROR, "Invalid notification callback.");

        $this->assertNull($this->notificationCenterObj->addNotificationListener(NotificationType::ACTIVATE, $invalid_callable));
    }

    public function testAddNotificationWithValidTypeAndCallback()
    {
        $notificationType = NotificationType::ACTIVATE;
        $this->notificationCenterObj->cleanAllNotifications();

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //  === should add, log and return notification ID when a plain function is passed as an argument === //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $simple_method = function () {
        };
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, "Callback added for notification type '{$notificationType}'.");
        $this->assertSame(
            1,
            $this->notificationCenterObj->addNotificationListener($notificationType, $simple_method)
        );
        // verify that notifications length has incremented by 1
        $this->assertSame(
            1,
            sizeof($this->notificationCenterObj->getNotifications()[$notificationType])
        );

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // === should add, log and return notification ID when an anonymous function is passed as an argument === //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, "Callback added for notification type '{$notificationType}'.");
        $this->assertSame(
            2,
            $this->notificationCenterObj->addNotificationListener(
                $notificationType,
                function () {
                }
            )
        );
        // verify that notifications length has incremented by 1
        $this->assertSame(
            2,
            sizeof($this->notificationCenterObj->getNotifications()[$notificationType])
        );

        ///////////////////////////////////////////////////////////////////////////////////////////////////////
        // === should add, log and return notification ID when an object method is passed as an argument === //
        ///////////////////////////////////////////////////////////////////////////////////////////////////////
        $eBuilder = new EventBuilder(new NoOpLogger());
        $callbackInput = array($eBuilder, 'createImpressionEvent');

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, "Callback added for notification type '{$notificationType}'.");
        $this->assertSame(
            3,
            $this->notificationCenterObj->addNotificationListener($notificationType, $callbackInput)
        );
        // verify that notifications length has incremented by 1
        $this->assertSame(
            3,
            sizeof($this->notificationCenterObj->getNotifications()[$notificationType])
        );
    }

    public function testAddNotificationForMultipleNotificationTypes()
    {
        $this->notificationCenterObj->cleanAllNotifications();

        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // === should add, log and return notification ID when a valid callback is added for each notification type === //
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, sprintf("Callback added for notification type '%s'.", NotificationType::ACTIVATE));
        $this->assertSame(
            1,
            $this->notificationCenterObj->addNotificationListener(
                NotificationType::ACTIVATE,
                function () {
                }
            )
        );

        // verify that notifications length for NotificationType::ACTIVATE has incremented by 1
        $this->assertSame(
            1,
            sizeof($this->notificationCenterObj->getNotifications()[NotificationType::ACTIVATE])
        );

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, sprintf("Callback added for notification type '%s'.", NotificationType::TRACK));
        $this->assertSame(
            2,
            $this->notificationCenterObj->addNotificationListener(
                NotificationType::TRACK,
                function () {
                }
            )
        );

        // verify that notifications length for NotificationType::TRACK has incremented by 1
        $this->assertSame(
            1,
            sizeof($this->notificationCenterObj->getNotifications()[NotificationType::TRACK])
        );
    }

    public function testAddNotificationForMultipleCallbacksForASingleNotificationType()
    {
        $this->notificationCenterObj->cleanAllNotifications();

        ///////////////////////////////////////////////////////////////////////////////////////
        // === should add, log and return notification ID when multiple valid callbacks
        //  are added for a single notification type ===                                     //
        ///////////////////////////////////////////////////////////////////////////////////////
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, sprintf("Callback added for notification type '%s'.", NotificationType::ACTIVATE));
        $this->assertSame(
            1,
            $this->notificationCenterObj->addNotificationListener(
                NotificationType::ACTIVATE,
                function () {
                }
            )
        );

        // verify that notifications length for NotificationType::ACTIVATE has incremented by 1
        $this->assertSame(
            1,
            sizeof($this->notificationCenterObj->getNotifications()[NotificationType::ACTIVATE])
        );

        $this->assertSame(
            2,
            $this->notificationCenterObj->addNotificationListener(
                NotificationType::ACTIVATE,
                function () {
                    echo "HelloWorld";
                }
            )
        );

        // verify that notifications length for NotificationType::ACTIVATE has incremented by 1
        $this->assertSame(
            2,
            sizeof($this->notificationCenterObj->getNotifications()[NotificationType::ACTIVATE])
        );

        $this->assertSame(
            3,
            $this->notificationCenterObj->addNotificationListener(
                NotificationType::ACTIVATE,
                function () {
                    $a = 1;
                }
            )
        );

        // verify that notifications length for NotificationType::ACTIVATE has incremented by 1
        $this->assertSame(
            3,
            sizeof($this->notificationCenterObj->getNotifications()[NotificationType::ACTIVATE])
        );
    }

    public function testAddNotificationThatAlreadyAddedCallbackIsNotReAdded()
    {
        // Note: anonymous methods sent with the same body will be re-added.
        // Only variable and object methods can be checked for duplication
        
        $functionToSend = function () {
        };
        $this->notificationCenterObj->cleanAllNotifications();

        ///////////////////////////////////////////////////////////////////////////
        // ===== verify that a variable method with same body isn't re-added ===== //
        ///////////////////////////////////////////////////////////////////////////
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, sprintf("Callback added for notification type '%s'.", NotificationType::ACTIVATE));

        // verify that notification ID 1 is returned
        $this->assertSame(
            1,
            $this->notificationCenterObj->addNotificationListener(NotificationType::ACTIVATE, $functionToSend)
        );

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, sprintf("Callback already added for notification type '%s'.", NotificationType::ACTIVATE));

        // verify that -1 is returned when adding the same callback
        $this->assertSame(
            -1,
            $this->notificationCenterObj->addNotificationListener(NotificationType::ACTIVATE, $functionToSend)
        );

        // verify that same method is added for a different notification type
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, sprintf("Callback added for notification type '%s'.", NotificationType::TRACK));
        $this->assertSame(
            2,
            $this->notificationCenterObj->addNotificationListener(NotificationType::TRACK, $functionToSend)
        );
        
        /////////////////////////////////////////////////////////////////////////
        // ===== verify that an object method with same body isn't re-added ===== //
        /////////////////////////////////////////////////////////////////////////
        $eBuilder = new EventBuilder(new NoOpLogger());
        $callbackInput = array($eBuilder, 'createImpressionEvent');

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, sprintf("Callback added for notification type '%s'.", NotificationType::ACTIVATE));
        $this->assertSame(
            3,
            $this->notificationCenterObj->addNotificationListener(NotificationType::ACTIVATE, $callbackInput)
        );

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, sprintf("Callback already added for notification type '%s'.", NotificationType::ACTIVATE));

        // verify that -1 is returned when adding the same callback
        $this->assertSame(
            -1,
            $this->notificationCenterObj->addNotificationListener(NotificationType::ACTIVATE, $callbackInput)
        );

        // verify that same method is added for a different notification type
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, sprintf("Callback added for notification type '%s'.", NotificationType::TRACK));
        $this->assertSame(
            4,
            $this->notificationCenterObj->addNotificationListener(NotificationType::TRACK, $callbackInput)
        );
    }

    public function testRemoveNotification()
    {
        $this->notificationCenterObj->cleanAllNotifications();

        // add a callback for multiple notification types
        $this->assertSame(
            1,
            $this->notificationCenterObj->addNotificationListener(
                NotificationType::ACTIVATE,
                function () {
                }
            )
        );
        $this->assertSame(
            2,
            $this->notificationCenterObj->addNotificationListener(
                NotificationType::TRACK,
                function () {
                }
            )
        );
        // add another callback for NotificationType::ACTIVATE
        $this->assertSame(
            3,
            $this->notificationCenterObj->addNotificationListener(
                NotificationType::ACTIVATE,
                function () {
                    //doSomething
                }
            )
        );

        // Verify that notifications length for NotificationType::ACTIVATE is 2
        $this->assertSame(
            2,
            sizeof($this->notificationCenterObj->getNotifications()[NotificationType::ACTIVATE])
        );

        // Verify that notifications length for NotificationType::TRACK is 1
        $this->assertSame(
            1,
            sizeof($this->notificationCenterObj->getNotifications()[NotificationType::TRACK])
        );


        ///////////////////////////////////////////////////////////////////////////////
        // === Verify that no callback is removed for an invalid notification ID === //
        ///////////////////////////////////////////////////////////////////////////////
        $invalid_id = 4;
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, sprintf("No Callback found with notification ID '%s'.", $invalid_id));
        $this->assertFalse($this->notificationCenterObj->removeNotificationListener($invalid_id));

        /////////////////////////////////////////////////////////////////////
        // === Verify that callback is removed for a valid notification ID //
        /////////////////////////////////////////////////////////////////////
        
        $valid_id = 3;
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::INFO, sprintf("Callback with notification ID '%s' has been removed.", $valid_id));
        $this->assertTrue($this->notificationCenterObj->removeNotificationListener($valid_id));

        // verify that notifications length for NotificationType::ACTIVATE is now 1
        $this->assertSame(
            1,
            sizeof($this->notificationCenterObj->getNotifications()[NotificationType::ACTIVATE])
        );

        //verify that notifications length for NotificationType::TRACK remains same
        $this->assertSame(
            1,
            sizeof($this->notificationCenterObj->getNotifications()[NotificationType::TRACK])
        );

        /////////////////////////////////////////////////////////////////////////////////////////////////
        // === Verify that no callback is removed once a callback has been already removed against a notification ID === //
        /////////////////////////////////////////////////////////////////////////////////////////////////
        $valid_id = 3;
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::DEBUG, sprintf("No Callback found with notification ID '%s'.", $valid_id));
        $this->assertFalse($this->notificationCenterObj->removeNotificationListener($valid_id));

        //verify that notifications lengths for NotificationType::ACTIVATE and NotificationType::TRACK remain same
        $this->assertSame(
            1,
            sizeof($this->notificationCenterObj->getNotifications()[NotificationType::ACTIVATE])
        );
        $this->assertSame(
            1,
            sizeof($this->notificationCenterObj->getNotifications()[NotificationType::TRACK])
        );
    }

    public function testClearNotifications()
    {
        // ensure that notifications length is zero for each notification type
        $this->notificationCenterObj->cleanAllNotifications();
        
        // add a callback for multiple notification types
        $this->notificationCenterObj->addNotificationListener(
            NotificationType::ACTIVATE,
            function () {
            }
        );
        $this->notificationCenterObj->addNotificationListener(
            NotificationType::TRACK,
            function () {
            }
        );

        // add another callback for NotificationType::ACTIVATE
        $this->notificationCenterObj->addNotificationListener(
            NotificationType::ACTIVATE,
            function () {
            }
        );

        // Verify that notifications length for NotificationType::ACTIVATE is 2
        $this->assertSame(
            2,
            sizeof($this->notificationCenterObj->getNotifications()[NotificationType::ACTIVATE])
        );

        // Verify that notifications length for NotificationType::TRACK is 1
        $this->assertSame(
            1,
            sizeof($this->notificationCenterObj->getNotifications()[NotificationType::TRACK])
        );

        /////////////////////////////////////////////////////////////////////////////////////////
        // === Verify that no notifications are removed given an invalid notification type === //
        /////////////////////////////////////////////////////////////////////////////////////////

        $invalid_type = "HelloWorld";

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::ERROR, "Invalid notification type.");

        $this->errorHandlerMock->expects($this->at(0))
            ->method('handleError')
            ->with(new InvalidNotificationTypeException('Invalid notification type.'));

        $this->assertNull($this->notificationCenterObj->clearNotifications($invalid_type));

        // Verify that notifications length for NotificationType::ACTIVATE is still 2
        $this->assertSame(
            2,
            sizeof($this->notificationCenterObj->getNotifications()[NotificationType::ACTIVATE])
        );

        // Verify that notifications length for NotificationType::TRACK is still 1
        $this->assertSame(
            1,
            sizeof($this->notificationCenterObj->getNotifications()[NotificationType::TRACK])
        );

        ///////////////////////////////////////////////////////////////////////////////////////
        // === Verify that all notifications are removed given a valid notification type === //
        ///////////////////////////////////////////////////////////////////////////////////////
         
        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(
                Logger::INFO,
                sprintf("All callbacks for notification type '%s' have been removed.", NotificationType::ACTIVATE)
            );

        $this->notificationCenterObj->clearNotifications(NotificationType::ACTIVATE);

        // Verify that notifications length for NotificationType::ACTIVATE is now 0
        $this->assertSame(
            0,
            sizeof($this->notificationCenterObj->getNotifications()[NotificationType::ACTIVATE])
        );

        // Verify that notifications length for NotificationType::TRACK is still 1
        $this->assertSame(
            1,
            sizeof($this->notificationCenterObj->getNotifications()[NotificationType::TRACK])
        );

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////
        // == Verify that no error is thrown when clearNotification is called again for the same notification type === //
        ///////////////////////////////////////////////////////////////////////////////////////////////////////////
        $this->notificationCenterObj->clearNotifications(NotificationType::ACTIVATE);
    }


    public function testCleanAllNotifications()
    {
        // using a new notification center object to avoid using the method being tested,
        // to reset notifications list
        $notificationCenterA = new NotificationCenter($this->loggerMock, $this->errorHandlerMock);

        // verify that for each of the notification types, the notifications length is zero
        $this->assertSame(
            0,
            sizeof($notificationCenterA->getNotifications()[NotificationType::ACTIVATE])
        );
        $this->assertSame(
            0,
            sizeof($notificationCenterA->getNotifications()[NotificationType::TRACK])
        );

        // add a callback for multiple notification types
        $notificationCenterA->addNotificationListener(
            NotificationType::ACTIVATE,
            function () {
            }
        );
        $notificationCenterA->addNotificationListener(
            NotificationType::ACTIVATE,
            function () {
            }
        );
        $notificationCenterA->addNotificationListener(
            NotificationType::ACTIVATE,
            function () {
            }
        );

        $notificationCenterA->addNotificationListener(
            NotificationType::TRACK,
            function () {
            }
        );
        $notificationCenterA->addNotificationListener(
            NotificationType::TRACK,
            function () {
            }
        );

        // verify that notifications length for each type reflects the just added callbacks
        $this->assertSame(
            3,
            sizeof($notificationCenterA->getNotifications()[NotificationType::ACTIVATE])
        );
        $this->assertSame(
            2,
            sizeof($notificationCenterA->getNotifications()[NotificationType::TRACK])
        );

        ////////////////////////////////////////////////////////////////////////////////////////////////////
        // === verify that cleanAllNotifications removes all notifications for each notification type === //
        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $notificationCenterA->cleanAllNotifications();

        // verify that notifications length for each type is now set to 0
        $this->assertSame(
            0,
            sizeof($notificationCenterA->getNotifications()[NotificationType::ACTIVATE])
        );
        $this->assertSame(
            0,
            sizeof($notificationCenterA->getNotifications()[NotificationType::TRACK])
        );

        ///////////////////////////////////////////////////////////////////////////////////////
        //=== verify that cleanAllNotifications doesn't throw an error when called again === //
        ///////////////////////////////////////////////////////////////////////////////////////
        $notificationCenterA->cleanAllNotifications();
    }

    public function testsendNotificationsGivenLessThanExpectedNumberOfArguments()
    {
        $clientObj = new FireNotificationTester;
        $this->notificationCenterObj->cleanAllNotifications();
        
        // add a notification callback with arguments
        $this->notificationCenterObj->addNotificationListener(
            NotificationType::ACTIVATE,
            array($clientObj, 'decision_callback_with_args')
        );

        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // === Verify that an exception is thrown and message logged when less number of args passed than expected === //
        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $this->errorHandlerMock->expects($this->at(0))
            ->method('handleError')
            ->with(new InvalidCallbackArgumentCountException('Registered callback expects more number of arguments than the actual number'));

        $this->loggerMock->expects($this->at(0))
            ->method('log')
            ->with(Logger::ERROR, "Problem calling notify callback.");

        $this->notificationCenterObj->sendNotifications(NotificationType::ACTIVATE, array("HelloWorld"));
    }

    public function testsendNotificationsAndVerifyThatAllCallbacksWithoutArgsAreCalled()
    {
        $clientMock = $this->getMockBuilder(FireNotificationTester::class)
            ->setMethods(array('decision_callback_no_args', 'decision_callback_no_args_2', 'track_callback_no_args'))
            ->getMock();

        $this->notificationCenterObj->cleanAllNotifications();

        //add notification callbacks
        $this->notificationCenterObj->addNotificationListener(
            NotificationType::ACTIVATE,
            array($clientMock, 'decision_callback_no_args')
        );
        $this->notificationCenterObj->addNotificationListener(
            NotificationType::ACTIVATE,
            array($clientMock, 'decision_callback_no_args_2')
        );
        $this->notificationCenterObj->addNotificationListener(
            NotificationType::TRACK,
            array($clientMock, 'track_callback_no_args')
        );

        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // === Verify that all callbacks for NotificationType::ACTIVATE are called and no other callbacks are called === //
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        $clientMock->expects($this->exactly(1))
            ->method('decision_callback_no_args');
        $clientMock->expects($this->exactly(1))
            ->method('decision_callback_no_args_2');

        $clientMock->expects($this->never())
            ->method('track_callback_no_args');

        $this->notificationCenterObj->sendNotifications(NotificationType::ACTIVATE);

        ////////////////////////////////////////////////////////////////////////////////////////////
        // === Verify that none of the callbacks are called given an invalid NotificationType === //
        ////////////////////////////////////////////////////////////////////////////////////////////
        
        $clientMock->expects($this->never())
            ->method('decision_callback_no_args');
        $clientMock->expects($this->never())
            ->method('decision_callback_no_args_2');

        $clientMock->expects($this->never())
            ->method('track_callback_no_args');

        $this->notificationCenterObj->sendNotifications("abacada");
    }

    public function testsendNotificationsAndVerifyThatAllCallbacksWithArgsAreCalled()
    {
        $clientMock = $this->getMockBuilder(FireNotificationTester::class)
            ->setMethods(array('decision_callback_with_args', 'decision_callback_with_args_2', 'track_callback_no_args'))
            ->getMock();

        $this->notificationCenterObj->cleanAllNotifications();

        //add notification callbacks with args
        $this->notificationCenterObj->addNotificationListener(
            NotificationType::ACTIVATE,
            array($clientMock, 'decision_callback_with_args')
        );
        $this->notificationCenterObj->addNotificationListener(
            NotificationType::ACTIVATE,
            array($clientMock, 'decision_callback_with_args_2')
        );
        $this->notificationCenterObj->addNotificationListener(
            NotificationType::TRACK,
            array($clientMock, 'track_callback_no_args')
        );

        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // === Verify that all callbacks for NotificationType::ACTIVATE are called and no other callbacks are called === //
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        $clientMock->expects($this->exactly(1))
            ->method('decision_callback_with_args')
            ->with(
                5,
                5.5,
                'string',
                array(5,6),
                function () {
                }
            );
        $clientMock->expects($this->exactly(1))
            ->method('decision_callback_with_args_2')
            ->with(
                5,
                5.5,
                'string',
                array(5,6),
                function () {
                }
            );
        $clientMock->expects($this->never())
            ->method('track_callback_no_args');

        $this->notificationCenterObj->sendNotifications(
            NotificationType::ACTIVATE,
            array(5, 5.5, 'string', array(5,6), function () {
            })
        );
    }
}
