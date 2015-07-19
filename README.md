# Cielo24 PHP Client Library

A simple PHP library to access the [cielo24.com API](http://cielo24.readthedocs.org)

## Requirements

PHP 5.3.3 and later.

## Installation

Install the package `cielo24/cielo24_php` via composer, or download the source and `require '/path/to/cielo_php/init.php';`

## Sample Usage

```php
<?php
  
  $cielo = new Cielo::API(array(
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

to run the tests make sure you have [PHPUnit](https://phpunit.de/getting-started.html) installed.

rename `tests/_config.default.php` to `tests/_config.php` and add your sandbox API credentials.

then execute `phpunit` to run all the tests.
