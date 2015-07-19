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
    self::$Cielo->login(array('password' => Config::$api_password));
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
    $result = self::$Cielo->login(array('securekey' => Config::$api_key));
    $this->assertNull( self::$Cielo->lastError );
    $this->assertTrue(is_object($result));
    $this->assertTrue(is_string($result->ApiToken));
  }

  /*
  public function testLogout(){
    self::$Cielo->logout();
    $errors = self::$Cielo->lastError;
    $token = self::$Cielo->$token;
    $this->assertTrue($errors == null);
    $this->assertTrue($token == null);    
    // log back in for the other tests...
    self::$Cielo->login();
  }

  public function testGenerateKey(){
    $result = self::$Cielo->generate_key();
    $this->assertTrue(is_object($result));
    $this->assertTrue(is_string($result->ApiKey));
  }
  

  public function testUpdatePassword(){
    self::$Cielo->update_password(self::$pass);
    $errors = self::$Cielo->lastError;
    $this->assertTrue($errors == null);
  }

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
    $job = self::_newJob();
    $result = self::$Cielo->job_authorize(array('job_id' => $job->JobId));
    $this->assertNull( self::$Cielo->lastError );
  }

  public function testJobDelete(){
    $job = self::_newJob();
    $result = self::$Cielo->job_delete(array('job_id' => $job->JobId));
    $this->assertNull( self::$Cielo->lastError );
  }

  public function testJobList(){
    $result = self::$Cielo->job_list();
    $this->assertNull( self::$Cielo->lastError );
    $this->assertTrue(is_object($result));
    $this->assertTrue(isset($result->ActiveJobs));
  }

  public function testUpload(){
    $result = self::$Cielo->job_list();
    $this->assertNull( self::$Cielo->lastError );
    $this->assertTrue(is_object($result));
    $this->assertTrue(isset($result->ActiveJobs));
  }

/*
  public function testPerformTranscription(){
    $job_id = self::_getJob();
    $options = array(
      'job_id' => $job_id,
      'transcription_fidelity' => 'MECHANICAL',
      'priority' => 'ECONOMY'
    );
    $result = self::$Cielo->perform_transcription($options);

    // @@todo - must add media before you can perform transcription!

    self::_debug('test perform_transcription');
    self::_debug($result);
    $this->assertNull( self::$Cielo->lastError );
    $this->assertTrue(is_object($result));
    $this->assertTrue(isset($result->Tasks[0]->TaskId));
  }

  public function testGetTranscript(){
    $job_id = self::_getJob();
    $result = self::$Cielo->get_transcript(array('job_id' => $job_id));
    self::_debug($result);
    $this->assertNull( self::$Cielo->lastError );
    $this->assertTrue(is_object($result));
    $this->assertTrue(isset($result->Tasks[0]->TaskId));
  }

  public function testGetCaption(){
    $job_id = self::_getJob();
    $result = self::$Cielo->get_caption(array('job_id' => $job_id));
    self::_debug($result);
    $this->assertNull( self::$Cielo->lastError );
    $this->assertTrue(is_object($result));
    $this->assertTrue(isset($result->Tasks[0]->TaskId));
  }

*/



}
