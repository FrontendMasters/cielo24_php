<?php

$config_file = dirname(__FILE__) . '/_config.php';

if (!file_exists($config_file)){
  throw new Exception('Missing _config.php file, please rename _config.default.php to _config.php');
}
// load the config file
require( $config_file );

class CieloTest extends PHPUnit_Framework_TestCase {

  /**
   * holds a list of jobs that are created and need to be
   * deleted after the tests
   */
  public static $jobs = array();
  public static $api_key = null;
  public static $pass = null;
  public static $performedTranscription = false;

  /**
   * holds the Cielo Api instance
   * @var Cielo\Api
   */
  public static $Cielo = null;

  /**
   * Helper method to create a new job and save a reference to it
   */
  public static function _newJob(){
    $job = self::$Cielo->job_create();
    if (self::$Cielo->lastError != null){
      return self::$Cielo->lastError;
    }
    self::$jobs[] = $job->JobId;
    return $job;
  }

  public static function _getJob(){
    if (!isset(self::$jobs[0])){
      self::_newJob();
    }
    return self::$jobs[0];
  }


  /**
   * This is called only one time... before all the tests start.
   */
  public static function setUpBeforeClass(){
    
    self::$Cielo = new Cielo\Api(array(
      'test' => true,
      'user' => Config::$api_username,
      'key'  => Config::$api_key
    ));
    self::$api_key = Config::$api_key;
    self::$pass = Config::$api_password;
    self::$Cielo->login(array('password' => Config::$api_password));

    // upload some media initially for use with other tests...
    $url = "http://youtu.be/5m5MPiL99Nc";
    // create a job
    $job_id = self::_getJob();
    // upload media to the job
    self::$Cielo->add_media_url(array(
      'job_id' => $job_id,
      'media_url' => $url
    ));

  }

  public static function tearDownAfterClass(){
    foreach(self::$jobs as $job){
      self::$Cielo->job_delete(array('job_id' => $job));
    }
  }


  public static function _debug($data){
    fwrite(STDERR, print_r($data, TRUE));
  }


  public function testLoginWithPassword(){
    $result = self::$Cielo->login(array('password' => Config::$api_password));
    $this->assertNull( self::$Cielo->lastError );
    $this->assertTrue(is_object($result));
    $this->assertTrue(is_string($result->ApiToken));
  }

  public function testLoginWithKey(){
    $result = self::$Cielo->login(array('securekey' => self::$api_key));
    $this->assertNull( self::$Cielo->lastError );
    $this->assertTrue(is_object($result));
    $this->assertTrue(is_string($result->ApiToken));
  }

  public function testLogout(){
    self::$Cielo->logout();
    $errors = self::$Cielo->lastError;
    $token = self::$Cielo->token;
    $this->assertTrue($errors == null);
    $this->assertTrue($token == null);    
    // log back in for the other tests...
    self::$Cielo->login(array('password' => Config::$api_password));
  }

  public function testGenerateKey(){
    $result = self::$Cielo->generate_key();
    $this->assertTrue(is_object($result));
    $this->assertTrue(is_string($result->ApiKey));
    self::$api_key = $result->ApiKey;
  }
  
  /*
  public function testUpdatePassword(){
    self::$Cielo->update_password(array(
      'new_password' => self::$pass
    ));
    $errors = self::$Cielo->lastError;
    $this->assertTrue($errors == null);
  }*/

  /*
  public function testRemoveApiKey(){
    // make a new key so we can then remove it ;-)
    $key = self::$Cielo->generate_key();
    self::$Cielo->remove_api_key($key->ApiKey);
    $errors = self::$Cielo->lastError;
    $this->assertTrue($errors == null);
  }
  */
  

  /**
   * Job related tests
   */

  public function testJobCreate(){
    $result = self::_newJob();
    $this->assertNull( self::$Cielo->lastError );
    $this->assertTrue(is_object($result));
    $this->assertTrue(isset($result->JobId));
  }

  public function testJobAuthorize(){
    $job_id = self::_getJob();
    $result = self::$Cielo->job_authorize(array('job_id' => $job_id));
    $this->assertNull( self::$Cielo->lastError );
  }

  public function testJobDelete(){
    $job_id = self::_getJob();
    $result = self::$Cielo->job_delete(array('job_id' => $job_id));
    $this->assertNull( self::$Cielo->lastError );
  }

  public function testJobInfo(){
    $job_id = self::_getJob();
    $result = self::$Cielo->job_info(array(
      'job_id' => $job_id
    ));
    $this->assertNull( self::$Cielo->lastError );
    $this->assertTrue(is_object($result));
    $this->assertTrue(isset($result->JobName));
  }

  public function testJobList(){
    $result = self::$Cielo->job_list();
    $this->assertNull( self::$Cielo->lastError );
    $this->assertTrue(is_object($result));
    $this->assertTrue(isset($result->ActiveJobs));
    $this->assertTrue(is_array($result->ActiveJobs));
  }

  public function testAddMediaToJobFromUrl(){
    $url = "http://techslides.com/demos/sample-videos/small.mp4";
    $job = self::_newJob();
    $result = self::$Cielo->add_media(array(
      'job_id' => $job->JobId,
      'media_url' => $url
    ));
    $this->assertNull( self::$Cielo->lastError );
    $this->assertTrue(is_object($result));
    $this->assertTrue(isset($result->TaskId));
    $this->assertTrue(is_string($result->TaskId));
  }

  public function testAddMediaUrlToJobFromUrl(){
    $url = "http://youtu.be/5m5MPiL99Nc";
    $job = self::_newJob();
    $result = self::$Cielo->add_media_url(array(
      'job_id' => $job->JobId,
      'media_url' => $url
    ));

    $this->assertNull( self::$Cielo->lastError );
    $this->assertTrue(is_object($result));
    $this->assertTrue(isset($result->TaskId));
    $this->assertTrue(is_string($result->TaskId));
  }

  public function testGetMediaFromJob(){
    $job_id = self::_getJob();

    // get media from job
    $result = self::$Cielo->get_media(array(
      'job_id' => $job_id
    ));

    // probably the media is still being processed...
    if (self::$Cielo->lastError){
      $this->assertTrue( $result->ErrorType == 'ITEM_NOT_FOUND' );
    } else {
      $this->assertNull( self::$Cielo->lastError );
      $this->assertTrue(is_object($result));
      $this->assertTrue(isset($result->MediaUrl));
      $this->assertTrue(is_string($result->MediaUrl));
    }

  }

  public function testAddMediaToJobFromFile(){
    $sample = dirname(__FILE__) . '/data/sample.mp4';
    
    $job = self::_newJob();
    $result = self::$Cielo->add_media(array(
      'job_id' => $job->JobId,
      'media_path' => $sample
    ));

    $this->assertNull( self::$Cielo->lastError );
    $this->assertTrue(is_object($result));
    $this->assertTrue(isset($result->TaskId));
    $this->assertTrue(is_string($result->TaskId));
  }


  public function testPerformTranscription(){
    $job_id = self::_getJob();
    // perform transcription
    $options = array(
      'job_id' => $job_id,
      'transcription_fidelity' => 'MECHANICAL',
      'priority' => 'ECONOMY'
    );

    $result = self::$Cielo->perform_transcription($options);

    self::$performedTranscription = true;

    $this->assertNull( self::$Cielo->lastError );
    $this->assertTrue(is_object($result));
    $this->assertTrue(isset($result->TaskId));
  }

  public function testGetTranscript(){
    $job_id = self::_getJob();

    if (self::$performedTranscription == false){
      // perform transcription
      $options = array(
        'job_id' => $job_id,
        'transcription_fidelity' => 'MECHANICAL',
        'priority' => 'ECONOMY'
      );
      self::$Cielo->perform_transcription($options);
    }

    // get the transcript
    $result = self::$Cielo->get_transcript(array('job_id' => $job_id));

    $this->assertNull( self::$Cielo->lastError );
    $this->assertTrue(is_string($result));
  }

  public function testGetCaption(){
    
    $job_id = self::_getJob();

    if (self::$performedTranscription == false){
      // perform transcription
      $options = array(
        'job_id' => $job_id,
        'transcription_fidelity' => 'MECHANICAL',
        'priority' => 'ECONOMY'
      );
      self::$Cielo->perform_transcription($options);
    }

    // get raw body with caption text
    $result = self::$Cielo->get_caption(array('job_id' => $job_id));

    $this->assertNull( self::$Cielo->lastError );
    $this->assertTrue(is_string($result));

    // get json with caption url
    $result2 = self::$Cielo->get_caption(array(
      'job_id' => $job_id,
      'build_url' => true
    ));

    $this->assertNull( self::$Cielo->lastError );
    $this->assertTrue(is_object($result2));
    $this->assertTrue(isset($result2->CaptionUrl));

  }

  public function testGetElementList(){
    
    $job_id = self::_getJob();
    
    if (self::$performedTranscription == false){
      // perform transcription
      $options = array(
        'job_id' => $job_id,
        'transcription_fidelity' => 'MECHANICAL',
        'priority' => 'ECONOMY'
      );
      self::$Cielo->perform_transcription($options);
    }

    $result = self::$Cielo->get_elementlist(array('job_id' => $job_id));

    $this->assertNull( self::$Cielo->lastError );
    $this->assertTrue(is_object($result));
    $this->assertTrue(isset($result->segments));

  }

  public function testListElementLists(){
    
    $job_id = self::_getJob();
    
    if (self::$performedTranscription == false){
      // perform transcription
      $options = array(
        'job_id' => $job_id,
        'transcription_fidelity' => 'MECHANICAL',
        'priority' => 'ECONOMY'
      );
      self::$Cielo->perform_transcription($options);
    }

    $result = self::$Cielo->list_elementlists(array('job_id' => $job_id));

    $this->assertNull( self::$Cielo->lastError );
    $this->assertTrue(is_array($result));
  }


}
