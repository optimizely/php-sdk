## 2.1.0
June 29th, 2018

- Introduces support for bot filtering ([#110](https://github.com/optimizely/php-sdk/pull/110)).

## 2.0.1
June 19th, 2018

- Fix: send impression event for Feature Test when Feature is disabled ([#114](https://github.com/optimizely/php-sdk/pull/114)).

## 2.0.0
April 12th, 2018

This major release introduces APIs for Feature Management. It also introduces some breaking changes listed below.

### New Features
- Introduced the `isFeatureEnabled` API to determine whether to show a feature to a user or not.
```
$isEnabled = $optimizelyClient->isFeatureEnabled('my_feature_key', 'my_user', $userAttributes);
```

- You can also get all the enabled features for the user by calling:
```
$enabledFeatures = $optimizelyClient->getEnabledFeatures('my_user', $userAttributes);
```

- Introduced Feature Variables to configure or parameterize a feature. There are four variable types: `String`, `Integer`, `Double`, `Boolean`.
```
$stringVariable = $optimizelyClient->getFeatureVariableString('my_feature_key', 'string_variable_key', 'my_user');
$integerVariable = $optimizelyClient->getFeatureVariableInteger('my_feature_key', 'integer_variable_key', 'my_user');
$doubleVariable = $optimizelyClient->getFeatureVariableDouble('my_feature_key', 'double_variable_key', 'my_user');
$booleanVariable = $optimizelyClient->getFeatureVariableBoolean('my_feature_key', 'boolean_variable_key', 'my_user');
```

### Breaking changes
- The `track` API with revenue value as a stand-alone parameter has been removed. The revenue value should be passed in as an entry in the event tags dict. The key for the revenue tag is `revenue` and the passed in value will be treated by Optimizely as the value for computing results.
```
$eventTags = ['revenue' => 4200];

$optimizelyClient->track('event_key', 'user_id', $userAttributes, $eventTags);
```

## 2.0.0-beta1
May 29th, 2018

This beta release introduces APIs for Feature Management. It also introduces some breaking changes listed below.

### New Features
- Introduced the `isFeatureEnabled` API to determine whether to show a feature to a user or not.
```
$isEnabled = $optimizelyClient->isFeatureEnabled('my_feature_key', 'my_user', $userAttributes);
```

- You can also get all the enabled features for the user by calling:
```
$enabledFeatures = $optimizelyClient->getEnabledFeatures('my_user', $userAttributes);
```

- Introduced Feature Variables to configure or parameterize a feature. There are four variable types: `String`, `Integer`, `Double`, `Boolean`.
```
$stringVariable = $optimizelyClient->getFeatureVariableString('my_feature_key', 'string_variable_key', 'my_user');
$integerVariable = $optimizelyClient->getFeatureVariableInteger('my_feature_key', 'integer_variable_key', 'my_user');
$doubleVariable = $optimizelyClient->getFeatureVariableDouble('my_feature_key', 'double_variable_key', 'my_user');
$booleanVariable = $optimizelyClient->getFeatureVariableBoolean('my_feature_key', 'boolean_variable_key', 'my_user');
```

### Breaking changes
- The `track` API with revenue value as a stand-alone parameter has been removed. The revenue value should be passed in as an entry in the event tags dict. The key for the revenue tag is `revenue` and the passed in value will be treated by Optimizely as the value for computing results.
```
$eventTags = ['revenue' => 4200];

$optimizelyClient->track('event_key', 'user_id', $userAttributes, $eventTags);
```

## 1.5.0
- Added support for notification listeners.
- Added support for IP anonymization.

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
