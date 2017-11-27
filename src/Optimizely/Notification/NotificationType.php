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

class NotificationType
{
    // format is EVENT: list of parameters to callback.
    const ACTIVATE = "ACTIVATE:experiment, user_id, attributes, variation, event";
    const TRACK = "TRACK:event_key, user_id, attributes, event_tags, event";

    public static function isNotificationTypeValid($notification_type)
    {
        $oClass = new \ReflectionClass(__CLASS__);
        $notificationTypeList = array_values($oClass->getConstants());

        return in_array($notification_type, $notificationTypeList);
    }

    public static function getAll()
    {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }
}
