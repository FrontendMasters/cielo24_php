# Cielo24 PHP Client Library (Unofficial)

A simple PHP library to access the [cielo24.com API](http://cielo24.readthedocs.org)

You might want to consider using the official [php library](https://github.com/Cielo24/cielo24-php) which requires PHP >= 5.5.0.

## Requirements

PHP 5.3.3 and later.

## Installation

Install with [Composer](https://getcomposer.org)

```bash
composer require mediaupstream/cielo24_php
```

then in your php application you can require the autoloader

```php
<?php
  require 'vendor/autoload.php';
```

Alternatively, you can download the source and simply require the init script

```php
<?php
  require '/path/to/cielo24_php/init.php';
```


## Sample Usage

```php
<?php

  require 'vendor/autoload.php';
  
  $cielo = new Cielo\Api(array(
    'user' => 'test_user',
    'key'  => 'ea9fc1ce98f5acf74a4f93de2bcfcfa5'
  ));

  // login
  $cielo->login(array('password' => 'test_password'));

  // create a new job
  $job = $cielo->job_create();

  // add some media to the job
  $cielo->add_media(array(
    'job_id' => $job->JobId,
    'media_url' => 'http://domain.com/media.mp4'
  ));

  // request that the media be transcribed
  $cielo->perform_transcription(array(
    'job_id' => $job->JobId,
    'transcription_fidelity' => 'MECHANICAL',
    'priority' => 'ECONOMY'
  ));

  // attempt to get the transcript for this job
  $cielo->get_transcript(array(
    'job_id' => $job->JobId
  ));

```


## Tests

To run tests first install [PHPUnit](https://phpunit.de/getting-started.html), then rename the test config file and edit it adding your sandbox API credentials from Cielo24.

```bash
mv tests/_config.default.php tests/_config.php
```

Now you can run the tests with:

```bash
phpunit
```

## License

The MIT License

