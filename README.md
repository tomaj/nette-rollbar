nette-rollbar
=============

Rollbar composer notifier for Nette framework

Instalation
===========

Install package via composer:

```
$ composer require tomaj/nette-rollbar:@dev
```

Add line to ```bootstrap.php``` for Rollbar initialization:

```
\Tomaj\Rollbar\RollbarDebugger::init($container, $container->parameters['rollbar']['sendErrors']);
```

And setup config on config.neon:

```
rollbar:
	sendErrors: true
	environment: production
	access_token: --your access token--
```

You can specify all Rollbar configration via this config. See all configuration posibilities in Rollbar website: [https://rollbar.com/tomaj/Najpes/docs/notifier/rollbar-php/](https://rollbar.com/tomaj/Najpes/docs/notifier/rollbar-php/) in *Configuration Reference* section.

todo
====

* Deployment notification
* Send info about logged user
