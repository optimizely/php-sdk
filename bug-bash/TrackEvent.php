<?php

namespace Optimizely\BugBash;

require_once '../vendor/autoload.php';
require_once '../bug-bash/_bug-bash-autoload.php';

use Monolog\Logger;
use Optimizely\Logger\DefaultLogger;
use Optimizely\Notification\NotificationType;
use Optimizely\Optimizely;
use Optimizely\OptimizelyFactory;
use Optimizely\OptimizelyUserContext;

// 1. Change this SDK key to your project's SDK Key
const SDK_KEY = '<your-sdk-key>';

// 2. Add an event to your project, adding it to your Experiment flag as a metric, then set the key here
const EVENT_KEY = '<your-event-key>';

// 3. Uncomment each scenario 1 by 1 modifying the contents of the method
// to test additional scenarios.

$test = new TrackEventTests();
$test->checkTrackNotificationListenerProducesEvent();
// $test->checkConversionEventLogDispatchedOnTrackEvent();
// $test->checkConversionEventLogIsNOTDispatchedOnTrackEventForInvalidEventName();
// $test->testEventTagsShowInDispatchedEventAndAppOptimizelyCom();

// 4. Change the current folder into the bug-bash directory if you've not already
// cd bug-bash/

// 5. Run the following command to execute the uncommented tests above:
// php TrackEvent.php

// https://docs.developers.optimizely.com/feature-experimentation/docs/track-event-php
class TrackEventTests
{
    // check that track notification listener produces event with event key
    public function checkTrackNotificationListenerProducesEvent(): void
    {
        $this->optimizelyClient->notificationCenter->addNotificationListener(
            NotificationType::TRACK,
            $this->onTrackEvent  // ⬅️ This should be called with a valid EVENT_NAME
        );

        // ...send track event.
        $this->userContext->trackEvent(EVENT_KEY);
    }

    // check that conversion event in the dispatch logs contains event key below
    public function checkConversionEventLogDispatchedOnTrackEvent(): void
    {
        $logger = new DefaultLogger(Logger::DEBUG);
        $localOptimizelyClient = new Optimizely(datafile: null, logger: $logger, sdkKey: SDK_KEY);
        $localUserContext = $localOptimizelyClient->createUserContext($this->userId);

        $localUserContext->trackEvent(EVENT_KEY);
    }

    // check that event is NOT dispatched if invalid event key is used
    // test changing event key in the UI and in the code
    public function checkConversionEventLogIsNOTDispatchedOnTrackEventForInvalidEventName(): void
    {
        $logger = new DefaultLogger(Logger::DEBUG);
        $localOptimizelyClient = new Optimizely(datafile: null, logger: $logger, sdkKey: SDK_KEY);
        $localUserContext = $localOptimizelyClient->createUserContext($this->userId);
        $this->optimizelyClient->notificationCenter->addNotificationListener(
            NotificationType::TRACK,
            $this->onTrackEvent  // ⬅️ There should not be a Notification Listener OnTrackEvent called on invalid event name
        );

        // You should not see any "Optimizely.DEBUG: Dispatching conversion event" but instead see
        // "Optimizely.INFO: Not tracking user "{user-id}" for event "an_invalid_event_name_not_in_the_project".
        $localUserContext->trackEvent("an_invalid_event_name_not_in_the_project");
    }

    // try adding event tags (in the project and in the line below) and see if they show in the event body
    public function testEventTagsShowInDispatchedEventAndAppOptimizelyCom(): void
    {
        $logger = new DefaultLogger(Logger::DEBUG);
        $localOptimizelyClient = new Optimizely(datafile: null, logger: $logger, sdkKey: SDK_KEY);
        $localUserContext = $localOptimizelyClient->createUserContext($this->userId);
        $custom_tags = [
            'shoe_size_paris_points' => 44,
            'shoe_size_us_size' => 11.5,
            'use_us_size' => false,
            'color' => 'blue'
        ];

        // Dispatched event should have the tags added to the payload `params { ... }` and also
        // should show on app.optimizely.com Reports tab after 5-10 minutes
        $localUserContext->trackEvent(EVENT_KEY, $custom_tags);
    }

    private Optimizely $optimizelyClient;
    private string $userId;
    private ?OptimizelyUserContext $userContext;
    private string $outputTag = "Track Event";
    private \Closure $onTrackEvent;

    public function __construct()
    {
        $this->optimizelyClient = OptimizelyFactory::createDefaultInstance(SDK_KEY);

        $this->userId = 'user-' . mt_rand(10, 99);
        $attributes = ['age' => 19, 'country' => 'bangledesh', 'has_purchased' => true];
        $this->userContext = $this->optimizelyClient->createUserContext($this->userId, $attributes);

        $this->onTrackEvent = function ($type, $userId, $attributes, $decisionInfo) {
            print ">>> [$this->outputTag] OnTrackEvent:
                type: $type,
                userId: $userId,
                attributes: " . print_r($attributes, true) . "
                decisionInfo: " . print_r($decisionInfo, true) . "\r\n";
        };
    }
}
