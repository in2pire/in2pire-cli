IN2PIRE CLI FRAMEWORK
===========

A Simple PHP CLI Framework provides

Installation
-----
Add ```in2pire/cli``` to your composer.json and run ```composer install```

Structure
-----
* Application
* Command
* Task

TBD

Constants
-----
* ```APP_NAME```: Your app name
* ```APP_PATH```: Path to your cli application
* ```APP_CONF_PATH```: Path to directory that stores config files for your application

How to build your cli application
-----
1. Create project with composer and prepare all the information (or add ```in2pire/cli``` to your composer.json)
2. Prepare config directory and config files. The directory must be inside the project.
3. Create your cli application. Define needed constants
4. Add command and task

For examples

```php
#!/usr/bin/env php
<?php

/**
 * Memcached CLI Application
 */

if (PHP_SAPI !== 'cli') {
    echo 'Warning: memcached-cli should be invoked via the CLI version of PHP, not the ' . PHP_SAPI . ' SAPI' . PHP_EOL;
    exit(1);
}

define('APP_NAME', 'memcached-cli');
define('APP_PATH', __DIR__);
define('APP_CONF_PATH', APP_PATH . '/../conf/' . APP_NAME);

// Add class loader.
require APP_PATH . '/../vendor/autoload.php';

// Run application.
$app = new In2pire\Cli\CliApplication();
$app->run();
```

How to compile your cli application
-----
The compiler is distributed in installation directory of ```in2pire/cli```. It uses Phar to put entire application into a single file for easy distribution and installation

Arguments
* ```--config```: The path to config directory of your cli application
* ```--bin```: The path to main executable of your cli application
* ```--no-compress```: Do not compress php files
* ```--no-optimize```: Do not optimize class loaders
* ```--no-phar```: Do not add .phar extension
* ```--executable```: Create executable file

Requirements
* You need to run compiler in your git repository. It helps to detect application version
* You need Phar in order to compile your application
* ```zlib``` or ```bzip2``` is required if you want to reduce size of your binary file

For example, in ```memcached-cli```. The binary is compiled by running

```bash
./bin/compile --bin=bin/memcached-cli --config=conf/memcached-cli --executable --no-phar
```

Examples
-----
* [Memcached CLI](https://github.com/in2pire/memcached-cli)

Dependencies
-----
* PHP >= 5.4
* Symfony YAML (symfony/yaml) >= 2.6
* Symfony Console (symfony/console) >= 2.6
* IN2PIRE Utilities (in2pire/utility) stable version}

Roadmap
-----
* Better documentation
* Official website
* Generator that helps to create class and config files
* Improve compiler to detect config directory
