# Optimizely PHP SDK
[![Build Status](https://travis-ci.org/optimizely/php-sdk.svg?branch=master)](https://travis-ci.org/optimizely/php-sdk)
[![Coverage Status](https://coveralls.io/repos/github/optimizely/php-sdk/badge.svg?branch=master)](https://coveralls.io/github/optimizely/php-sdk?branch=master)
[![Total Downloads](https://poser.pugx.org/optimizely/optimizely-sdk/downloads)](https://packagist.org/packages/optimizely/optimizely-sdk)
[![Apache 2.0](https://img.shields.io/github/license/nebula-plugins/gradle-extra-configurations-plugin.svg)](http://www.apache.org/licenses/LICENSE-2.0)

This repository houses the PHP SDK for use with Optimizely Full Stack and Optimizely Rollouts.

Optimizely Full Stack is A/B testing and feature flag management for product development teams. Experiment in any application. Make every feature on your roadmap an opportunity to learn. Learn more at https://www.optimizely.com/platform/full-stack/, or see the [documentation](https://docs.developers.optimizely.com/full-stack/docs).

Optimizely Rollouts is free feature flags for development teams. Easily roll out and roll back features in any application without code deploys. Mitigate risk for every feature on your roadmap. Learn more at https://www.optimizely.com/rollouts/, or see the [documentation](https://docs.developers.optimizely.com/rollouts/docs).

## Getting Started

### Installing the SDK

The Optimizely PHP SDK can be installed through [Composer](https://getcomposer.org/). Please use the following command: 

```
php composer.phar require optimizely/optimizely-sdk
```

### Feature Management Access
To access the Feature Management configuration in the Optimizely dashboard, please contact your Optimizely account executive.

### Using the SDK

#### Initialization
Create the Optimizely client, for example:

```
$optimizely = new Optimizely(<<DATAFILE>>);
```

You can also provide `ProjectConfigManagerInterface` in the constructor:

```
$configManager = new HTTPProjectConfigManager(<<SDK_KEY>>);
$optimizely = new Optimizely(<<DATAFILE>>, null, null, null, false, $configManager);
```

#### ProjectConfigManagerInterface
[`ProjectConfigManagerInterface`](https://github.com/optimizely/php-sdk/blob/master/src/Optimizely/ProjectConfigManager/ProjectConfigManagerInterface.php) exposes method for retrieving `ProjectConfig` instance.

#### HTTPProjectConfigManager

[`HTTPProjectConfigManager`](https://github.com/optimizely/php-sdk/blob/master/src/Optimizely/ProjectConfigManager/HTTPProjectConfigManager.php)
is an implementation of `ProjectConfigManagerInterface` interface.

The `fetch` method makes a blocking HTTP GET request to the configured URL to download the
project datafile and initialize an instance of the ProjectConfig.

Calling `fetch` will update the internal ProjectConfig instance that will be returned by `getConfig`.

##### Use HTTPProjectConfigManager

```
$configManager = new HTTPProjectConfigManager(<<SDK_KEY>>);
```

##### SDK key
Optimizely project SDK key; required unless source URL is overridden.

#### Documentation
See the Optimizely Full Stack [developer documentation](https://developers.optimizely.com/x/solutions/sdks/reference/?language=php) to learn how to set up your first Full Stack project and use the SDK.

## Development

### Unit tests

##### Running all tests
You can run all unit tests with:

```
./vendor/bin/phpunit
```

### Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md).
