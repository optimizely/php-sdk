# Optimizely PHP SDK
[![Packagist](https://badgen.net/packagist/v/optimizely/optimizely-sdk)](https://packagist.org/packages/optimizely/optimizely-sdk)
[![Build Status](https://travis-ci.org/optimizely/php-sdk.svg?branch=master)](https://travis-ci.org/optimizely/php-sdk)
[![Coverage Status](https://coveralls.io/repos/github/optimizely/php-sdk/badge.svg?branch=master)](https://coveralls.io/github/optimizely/php-sdk?branch=master)
[![Total Downloads](https://poser.pugx.org/optimizely/optimizely-sdk/downloads)](https://packagist.org/packages/optimizely/optimizely-sdk)
[![Apache 2.0](https://img.shields.io/github/license/nebula-plugins/gradle-extra-configurations-plugin.svg)](http://www.apache.org/licenses/LICENSE-2.0)

This repository houses the PHP SDK for use with Optimizely Feature Experimentation and Optimizely Full Stack (legacy).

Optimizely Feature Experimentation is an A/B testing and feature management tool for product development teams that enables you to experiment at every step. Using Optimizely Feature Experimentation allows for every feature on your roadmap to be an opportunity to discover hidden insights. Learn more at [Optimizely.com](https://www.optimizely.com/products/experiment/feature-experimentation/), or see the [developer documentation](https://docs.developers.optimizely.com/experimentation/v4.0.0-full-stack/docs/welcome).

Optimizely Rollouts is [free feature flags](https://www.optimizely.com/free-feature-flagging/) for development teams. You can easily roll out and roll back features in any application without code deploys, mitigating risk for every feature on your roadmap.

## Get Started

Refer to the [PHP SDK's developer documentation](https://docs.developers.optimizely.com/experimentation/v4.0.0-full-stack/docs/php-sdk) for detailed instructions on getting started with using the SDK.

### Requirements

To access the Feature Management configuration in the Optimizely dashboard, please contact your Optimizely account executive.

### Install the SDK

The Optimizely PHP SDK can be installed through [Composer](https://getcomposer.org/). Please use the following command:

```bash
php composer.phar require optimizely/optimizely-sdk
```

## Use the PHP SDK

### Initialization

Create the Optimizely client, for example:

```php
$optimizely = new Optimizely(<<DATAFILE>>);
```

Or you may also use OptimizelyFactory method to create an optimizely client using your SDK key, an optional fallback datafile and an optional datafile access token. Using this method internally creates an HTTPProjectConfigManager. See [HTTPProjectConfigManager](#use-httpprojectconfigmanager) for further detail.

```php
$optimizelyClient = OptimizelyFactory::createDefaultInstance("your-sdk-key", <<DATAFILE>>, <<DATAFILE_AUTH_TOKEN>>);
```
To access your HTTPProjectConfigManager:

```php
$configManager = $optimizelyClient->configManager;
```

Or you can also provide an implementation of the [`ProjectConfigManagerInterface`](https://github.com/optimizely/php-sdk/blob/master/src/Optimizely/ProjectConfigManager/ProjectConfigManagerInterface.php) in the constructor:

```php
$configManager = new HTTPProjectConfigManager(<<SDK_KEY>>);
$optimizely = new Optimizely(<<DATAFILE>>, null, null, null, false, null, $configManager);
```

### ProjectConfigManagerInterface
[`ProjectConfigManagerInterface`](https://github.com/optimizely/php-sdk/blob/master/src/Optimizely/ProjectConfigManager/ProjectConfigManagerInterface.php) exposes `getConfig` method for retrieving `ProjectConfig` instance.

### <a name="http_config_manager"></a> HTTPProjectConfigManager

[`HTTPProjectConfigManager`](https://github.com/optimizely/php-sdk/blob/master/src/Optimizely/ProjectConfigManager/HTTPProjectConfigManager.php)
is an implementation of `ProjectConfigManagerInterface` interface.

The `fetch` method makes a blocking HTTP GET request to the configured URL to download the
project datafile and initialize an instance of the ProjectConfig.

Calling `fetch` will update the internal ProjectConfig instance that will be returned by `getConfig`.

### Use HTTPProjectConfigManager

```php
$configManager = new HTTPProjectConfigManager(<<SDK_KEY>>);
```

### SDK key
Optimizely project SDK key; required unless source URL is overridden.

A notification will be triggered whenever a _new_ datafile is fetched and ProjectConfig is updated. To subscribe to these notifications, use the `$notificationCenter->addNotificationListener(NotificationType::OPTIMIZELY_CONFIG_UPDATE, $updateCallback)`.

## SDK Development

### Unit Tests

You can run all unit tests with:

```bash
./vendor/bin/phpunit
```

### Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md).

### Other Optimizely SDKs

- Agent - https://github.com/optimizely/agent

- Android - https://github.com/optimizely/android-sdk

- C# - https://github.com/optimizely/csharp-sdk

- Flutter - https://github.com/optimizely/optimizely-flutter-sdk

- Go - https://github.com/optimizely/go-sdk

- Java - https://github.com/optimizely/java-sdk

- JavaScript - https://github.com/optimizely/javascript-sdk

- PHP - https://github.com/optimizely/php-sdk

- Python - https://github.com/optimizely/python-sdk

- React - https://github.com/optimizely/react-sdk

- Ruby - https://github.com/optimizely/ruby-sdk

- Swift - https://github.com/optimizely/swift-sdk
