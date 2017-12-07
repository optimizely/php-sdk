##1.5.0
- Notification listeners.
- Feature flags and rollouts.
- IP Anonymization.

## 1.4.0
- Added support for Numeric Metrics.
- Switched to new event API.

## 1.3.0
- Added the forced bucketing feature, which allows customers to force users into variations in real time for QA purposes without requiring datafile downloads from the network. The following APIs are introduced:
```
/**
* Force a user into a variation for a given experiment.
*
* @param $experimentKey string Key identifying the experiment.
* @param $userId string The user ID to be used for bucketing. 
* @param $variationKey string The variation key specifies the variation which the user  
* will be forced into. If null, then clear the existing experiment-to-variation mapping.
*
* @return boolean A boolean value that indicates if the set completed successfully. 
*/
```
```
public function setForcedVariation($experimentKey, $userId, $variationKey);

/**
* Gets the forced variation for a given user and experiment.
*
* @param $experimentKey string Key identifying the experiment.
* @param $userId string The user ID to be used for bucketing. 
*
* @return string|null The forced variation key.
*/
public function getForcedVariation($experimentKey, $userId);
```

- Added the bucketing ID feature allows decoupling bucketing from user identification so that a group of users that have the same bucketing ID are put into the same variation. 

## 1.2.0
- Add user profile service.

## 1.1.1
- Updated datafile parsing to be able to handle additional fields.

## 1.1.0
- Updated to send datafile revision information in log events.
- Gracefully handle empty entity IDs.
- Added event tags to track API to allow users to pass in event metadata.
- Deprecated the `eventValue` parameter from the track method. Should use `eventTags` to pass in event value instead.
- Relaxed restriction on monolog package.

## 1.0.1
- Updated to support more versions of json-schema package.

## 1.0.0
- General release of Optimizely X Full Stack PHP SDK. No breaking changes from previous version.
- Introduced curl based event dispatcher.

## 0.1.0
- Beta release of the Optimizely X Full Stack PHP SDK.
