# Optimizely PHP SDK Changelog

### New Features:

## 3.4.0
July 8th, 2020

### New Features:
- Introduced 2 APIs to interact with feature variables:
  - `getFeatureVariableJSON` allows you to get value for JSON variables related to a feature.
  - `getAllFeatureVariables` gets values for all variables under a feature.
- Added support for fetching authenticated datafiles. `HTTPProjectConfigManager` now accepts `datafileAccessToken` to be able to fetch datafiles belonging to secure environments.

### Bug Fixes:
- Adjusted log level for audience evaluation logs. ([#198](https://github.com/optimizely/php-sdk/pull/198))

## 3.3.1
May 28th, 2020

### Bug Fixes:
- This release adds an SDK key param to Optimizely constructor. The user can now create an Optimizely instance only using an SDK key. Previously the user was required to create a HTTPProjectConfigManager, and pass it as a config manager in the constructor. ([#189](https://github.com/optimizely/php-sdk/pull/189), [#193](https://github.com/optimizely/php-sdk/pull/193)) 

## 3.3.0
January 27th, 2020

### New Features:
- Added a new API to get project configuration static data.
  - Call `getOptimizelyConfig()` to get a snapshot of project configuration static data.
  - It returns an `OptimizelyConfig` instance which includes a datafile revision number, all experiments, and feature flags mapped by their key values.
  - For more details, refer to our documentation page: [https://docs.developers.optimizely.com/full-stack/docs/optimizelyconfig-php](https://docs.developers.optimizely.com/full-stack/docs/optimizelyconfig-php).

## 3.2.0
August 28th, 2019

### New Features:
* Added support for datafile management via [HTTPProjectConfigManager](https://github.com/optimizely/php-sdk/blob/master/src/Optimizely/ProjectConfigManager/HTTPProjectConfigManager.php):
  * The [HTTPProjectConfigManager](https://github.com/optimizely/php-sdk/blob/master/src/Optimizely/ProjectConfigManager/HTTPProjectConfigManager.php) is an implementation of the [ProjectConfigManagerInterface](https://github.com/optimizely/php-sdk/blob/master/src/Optimizely/ProjectConfigManager/ProjectConfigManagerInterface.php).
  * Users will have to initialize and pass in the config manager to be able to use it:
  ```
  $configManager = new HTTPProjectConfigManager(<<SDK_KEY>>);
  $optimizely = new Optimizely(<<DATAFILE>>, null, null, null, false, null, $configManager);  
  ```
  * The `fetch` method allows you to refresh the config. In order to update the config, you can do something like:
  ```
  $configManager->fetch();
  ```
  * Configuration updates can be subscribed to by adding the `OPTIMIZELY_CONFIG_UPDATE` notification listener.

## 3.1.0
May 3rd, 2019

### New Features:
- Introduced Decision notification listener to be able to record:
    - Variation assignments for users activated in an experiment.
    - Feature access for users.
    - Feature variable value for users.

### Bug Fixes:
- Feature variable APIs return default variable value when featureEnabled property is false. ([#159](https://github.com/optimizely/php-sdk/pull/159))

### Deprecated
- Activate notification listener is deprecated as of this release. Recommendation is to use the new Decision notification listener. Activate notification listener will be removed in the next major release.

## 3.0.1
April 19th, 2019

### Security Fix:
* Removed CurlEventDispatcher. ([#168](https://github.com/optimizely/php-sdk/pull/168))
    - ID: [opt-2019-0001](https://www.optimizely.com/security/advisories/opt-2019-0001)
    - Overall CVSSv3: 6.0
    - Optimizely Severity: High

## 3.0.0
March 6th, 2019

The 3.0 release improves event tracking and supports additional audience targeting functionality.
### New Features:
* Event tracking:
  * The `track` method now dispatches its conversion event _unconditionally_, without first determining whether the user is targeted by a known experiment that uses the event. This may increase outbound network traffic.
  * In Optimizely results, conversion events sent by 3.0 SDKs don't explicitly name the experiments and variations that are currently targeted to the user. Instead, conversions are automatically attributed to variations that the user has previously seen, as long as those variations were served via 3.0 SDKs or by other clients capable of automatic attribution, and as long as our backend actually received the impression events for those variations.
  * Altogether, this allows you to track conversion events and attribute them to variations even when you don't know all of a user's attribute values, and even if the user's attribute values or the experiment's configuration have changed such that the user is no longer affected by the experiment. As a result, **you may observe an increase in the conversion rate for previously-instrumented events.** If that is undesirable, you can reset the results of previously-running experiments after upgrading to the 3.0 SDK.
  * This will also allow you to attribute events to variations from other Optimizely projects in your account, even though those experiments don't appear in the same datafile.
  * Note that for results segmentation in Optimizely results, the user attribute values from one event are automatically applied to all other events in the same session, as long as the events in question were actually received by our backend. This behavior was already in place and is not affected by the 3.0 release.
* Support for all types of attribute values, not just strings.
  * All values are passed through to notification listeners.
  * Strings, booleans, and valid numbers are passed to the event dispatcher and can be used for Optimizely results segmentation. A valid number is a finite float or int in the inclusive range [-2⁵³, 2⁵³]. 
  * Strings, booleans, and valid numbers are relevant for audience conditions.
* Support for additional matchers in audience conditions:
  * An `exists` matcher that passes if the user has a non-null value for the targeted user attribute and fails otherwise.
  * A `substring` matcher that resolves if the user has a string value for the targeted attribute.
  * `gt` (greater than) and `lt` (less than) matchers that resolve if the user has a valid number value for the targeted attribute. A valid number is a finite float or int in the inclusive range [-2⁵³, 2⁵³].
  * The original (`exact`) matcher can now be used to target booleans and valid numbers, not just strings.
* Support for A/B tests, feature tests, and feature rollouts whose audiences are combined using `"and"` and `"not"` operators, not just the `"or"` operator.
* Datafile-version compatibility check: The SDK will remain uninitialized (i.e., will gracefully fail to activate experiments and features) if given a datafile version greater than 4.
* Updated Pull Request template and commit message guidelines.

### Breaking Changes:
* Conversion events sent by 3.0 SDKs don't explicitly name the experiments and variations that are currently targeted to the user, so these events are unattributed in raw events data export. You must use the new _results_ export to determine the variations to which events have been attributed.
* Previously, notification listeners were only given string-valued user attributes because only strings could be passed into various method calls. That is no longer the case. You may pass non-string attribute values, and if you do, you must update your notification listeners to be able to receive whatever values you pass in.
  * We're also renaming the `clearNotifications` and `cleanAllNotifications` methods. They're now called `clearNotificationListeners` and `clearAllNotificationListeners`, respectively. The original methods are now deprecated and will be dropped in a future release. ([#120](https://github.com/optimizely/php-sdk/pull/120))

### Bug Fixes:
* Experiments and features can no longer activate when a negatively targeted attribute has a missing, null, or malformed value.
  * Audience conditions (except for the new `exists` matcher) no longer resolve to `false` when they fail to find an legitimate value for the targeted user attribute. The result remains `null` (unknown). Therefore, an audience that negates such a condition (using the `"not"` operator) can no longer resolve to `true` unless there is an unrelated branch in the condition tree that itself resolves to `true`.
* All methods now validate that user IDs are strings and that experiment keys, feature keys, feature variable keys, and event keys are non-empty strings.
* Ignore the user's whitelisted variation if it has been stopped. ([#123](https://github.com/optimizely/php-sdk/pull/123))

## 2.2.1
November 14th, 2018

### Bug fixes
- fix(generateBucketValue): Avoid negative bucket number for PHP x86. ([#137](https://github.com/optimizely/php-sdk/pull/137))
- fix(phpdoc-notification-callback): Fixes phpdoc primitive type of notification-callback. ([#135](https://github.com/optimizely/php-sdk/pull/135))


## 2.2.0
October 29th, 2018

### New Features
- feat(isValid): Adds getter to access isValid attribute. ([#128](https://github.com/optimizely/php-sdk/pull/128))

### Bug fixes
- fix(datafile-parsing): Prevent newer versions datafile. ([#127](https://github.com/optimizely/php-sdk/pull/127))
- fix: Updating dependencies. ([#125](https://github.com/optimizely/php-sdk/pull/125))
- fix(track): Send decisions for all experiments using an event when using track. ([#124](https://github.com/optimizely/php-sdk/pull/124))

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
