<?php

namespace Cielo;

/**
 * Client library for Cielo24 API
 * @see  http://cielo24.readthedocs.org/en/latest/index.html
 */
class Api {

  // @var string set to true to enable testing mode (uses the sandbox api)
  public $test = false;

  // @var string The version of the API to use
  public $version = 1;

  // @var string The Cielo API user to be used for requests.
  public $user;

  // @var string The Cielo API secure key, generated for your account.
  public $key;

  // @var string The Cielo API access token to be used for requests
  public $token;

  // @var string The base URL for the Cielo API.
  public $base_url = 'https://api.cielo24.com/api/';

  // @var string The base URL for the Cielo API.
  public $test_url = 'https://sandbox.cielo24.com/api/';

  //  @var null|object the error message of the last API call or null
  public $lastError = null;

  public $lastResponse = null;

  /**
   * Create an instance of the Cielo API
   * 
   * @param array o options to instantiate the class with
   */
  public function __construct($o=array()){
    // initialize stuff
    if (isset($o['user']))    $this->user = $o['user'];
    if (isset($o['key']))     $this->key = $o['key'];
    if (isset($o['token']))   $this->token = $o['token'];
    if (isset($o['version'])) $this->version = $o['version'];
    if (isset($o['test']))    $this->test = $o['test'];
  }

  /**
   * Get the API base url to use for requests
   * 
   * @return string the base url to use for requests
   */
  public function getBaseUrl(){
    $url = $this->base_url;
    if ( $this->test == true ){
      $url = $this->test_url;
    }
    return $url;
  }

  /**
   * Get the API token to use for requests
   * 
   * @return string the token to use for requests
   */
  public function getToken(){
    $token = $this->token;
    if ($token == null){
      $token = $this->key;
    }
    return $token;
  }

  /**
   * Merge $params with $options['query'] and ensure that $options['query']
   * is an array. Also, set the api_token if it isn't already set
   * 
   * @param  array $options
   * @param  array $params
   * @return array $options
   */
  public function _merge($options, $params=array()){
    if(!isset($options['query'])){
      $options['query'] = array();
    }
    if (!is_array($options['query'])){
      throw new \Exception('The query must be an array');
    }
    if (!isset($params['api_token'])){
      $params['api_token'] = $this->getToken();
    }
    $options['query'] = $params + $options['query'];
    return $options;
  }

  /**
   * Set the default options, merge with supplied options
   * 
   * @param array options Optional User supplied options
   * @return  array options The default options
   */
  public function _setDefaults($options=array()){
    $defaults = array(
      'method' => null,
      'url' => $this->getBaseUrl(),
      'api_token' => $this->getToken(),
      'version' => $this->version,
      'query' => array(),
      'data' => null,
      'binary' => null,
      'contentType' => 'text/plain',
      'headers' => array(),
      'cURL' => array()
    );
    $options += $defaults;
    return $options;
  }

  /**
   * A wrapper around Cielo\Request::call, if there are any errors
   * they will be set to $this->lastError
   * 
   * @param  string method The API url to call
   * @param  array options Options to pass to Cielo\Request::call
   * @return stdClass API results
   */
  private function _call($method, $options){
    $options['method'] = $method;
    $options = $this->_setDefaults($options);
    $results = \Cielo\Request::call($options);
    $this->lastResponse = $results;
    $this->lastError = null;
    if ($results['error'] != null){
      $this->lastError = $results['error'];
    }

    // return the JSON or raw response body
    $result = $results['json'];
    if (isset($options['raw']) && $options['raw'] == true){
      $result = $results['raw'];
    }

    return $result;
  }

  /** ------------------------------------------------------------
   * Cielo API :: Access Control
   */

  /**
   * Login with a username and either a password or a securekey.
   * sets $this->token to the returned ApiToken, if successful.
   *
   * Subsequent api calls will use the returned token.
   * 
   * @param  array params Cielo24 API parameters
   * @param  array options Options to pass to Request::call
   * @return stdClass API results
   */
  public function login($params=array(), $options=array()){
    if (!isset($params['username'])){
      $params['username'] = $this->user;
    }
    $options = $this->_merge($options, $params);
    $result = $this->_call('account/login', $options);

    if (isset($result->ApiToken)){
      $this->token = $result->ApiToken;
    }
    return $result;
  }
  
  /**
   * Logout, invalidates the current session and token.
   * If there were no errors $this->token is set to null.
   * 
   * @param  array params Cielo24 API parameters
   * @param  array options Options to pass to Request::call
   * @return stdClass API results
   */
  public function logout($params=array(), $options=array()){
    $options = $this->_merge($options, $params);
    $result = $this->_call('account/logout', $options);
    if ($this->lastError == null){
      // clear the token now that it's invalid
      $this->token = null;
    }
    return $result;
  }

  /**
   * Creates a long term use API key to use in lieu of a password.
   * 
   * @param  array params Cielo24 API parameters
   * @param  array options Options to pass to Request::call
   * @return stdClass API results
   */
  public function generate_key($params=array(), $options=array()){
    if (!isset($params['account_id'])){
      $params['account_id'] = $this->user;
    } 
    $options = $this->_merge($options, $params);
    return $this->_call('account/generate_api_key', $options);
  }
  
  /**
   * Update your password. required params: 'new_password'
   * 
   * @param  array params Cielo24 API parameters
   * @param  array options Options to pass to Request::call
   * @return stdClass API results
   */
  public function update_password($params=array(), $options=array()){
    if (!isset($params['new_password'])){
      throw new \Exception('new_password is required');
    }
    // merge params with required default params for the post body
    $body = $params + array(
      'v' => $this->version,
      'api_token' => $this->getToken()
    );
    // set to `data` means it will be POST'ed
    $options['data'] = \Cielo\Request::queryString($body);
    $options = $this->_merge($options, $params);

    return $this->_call('account/update_password', $options);
  }

  /**
   * Invalidates an API Key. It will no longer work as a login credential
   * 
   * @param  array params Cielo24 API parameters
   * @param  array options Options to pass to Request::call
   * @return stdClass API results
   */
  public function remove_api_key($params=array(), $options=array()){
    if (!isset($params['secure_key'])){
      $params['secure_key'] = $this->key;
    }
    $options = $this->_merge($options, $params);
    return $this->_call('account/remove_api_key', $options);
  }





  /** ------------------------------------------------------------
   * Cielo API :: Job Control
   */

  /**
   * Create a new job. A job is a container into which you can upload 
   * media and request that transcription be performed. Creating a job 
   * is prerequisite for virtually all other methods.
   * 
   * @param  array params Cielo24 API parameters
   * @param  array options Options to pass to Request::call
   * @return stdClass API results
   */
  public function job_create($params=array(), $options=array()){
    $options = $this->_merge($options, $params);
    return $this->_call('job/new', $options);
  }

  /**
   * Authorize an existing job. If you have enabled the 
   * "customer authorization" feature in the settings for your
   * account, this will authorize a job.
   * 
   * @param  array params Cielo24 API parameters
   * @param  array options Options to pass to Request::call
   * @return stdClass API results
   */
  public function job_authorize($params=array(), $options=array()){
    if (!isset($params['job_id'])){
      throw new \Exception('job_id is required');
    }
    $options = $this->_merge($options, $params);
    return $this->_call('job/authorize', $options);
  }

  /**
   * Delete a job. Jobs can only be deleted before they have 
   * started processing, when their status is “Authorizing” or “Pending”.
   * 
   * @param  array params Cielo24 API parameters
   * @param  array options Options to pass to Request::call
   * @return stdClass API results
   */
  public function job_delete($params=array(), $options=array()){
    if (!isset($params['job_id'])){
      throw new \Exception('job_id is required');
    }
    $options = $this->_merge($options, $params);
    return $this->_call('job/del', $options);
  }

  /**
   * Get a list of all tasks associated with an existing job.
   * 
   * @param  array params Cielo24 API parameters
   * @param  array options Options to pass to Request::call
   * @return stdClass API results
   */
  public function job_info($params=array(), $options=array()){
    if (!isset($params['job_id'])){
      throw new \Exception('job_id is required');
    }
    $options = $this->_merge($options, $params);
    return $this->_call('job/info', $options);
  }

  /**
   * Get a list of all ACTIVE jobs associated with the user account
   * that generated the given API Token. 
   * 
   * @param  array params Cielo24 API parameters
   * @param  array options Options to pass to Request::call
   * @return stdClass API results
   */
  public function job_list($params=array(), $options=array()){
    $options = $this->_merge($options, $params);
    return $this->_call('job/list', $options);
  }

  /**
   * Add a piece of media to an existing job. Use 'media_url' to
   * specify a media, or 'media_path' for a local file upload.
   * 
   * @param  array params Cielo24 API parameters
   * @param  array options Options to pass to Request::call
   * @return stdClass API results
   */
  public function add_media($params=array(), $options=array()){
    if (!isset($params['job_id'])){
      throw new \Exception('job_id is required');
    }
    // add media file from file (binary file upload via POST)
    if (!isset($params['media_url'])){
      if (!isset($params['media_path'])){
        throw new \Exception('A media_url or media_path is required');
      }
      // get the realpath to the file to be uploaded
      $file = realpath( $params['media_path'] );

      if (is_file($file) == false){
        throw new \Exception('unable to read media_path');
      }
      $options['binary'] = true;
      $options['data'] = array('file' => $file);
      $options['headers'] = array(
        'Content-Length: ' . filesize($file)
      );
      // php caches calls to is_file, filesize, etc, like a boss?
      clearstatcache();
      unset($params['media_path']);
    }
    $options = $this->_merge($options, $params);

    return $this->_call('job/add_media', $options);
  }

  /**
   * Add a piece of media to an existing job via a non-direct URL
   * 
   * @param  array params Cielo24 API parameters
   * @param  array options Options to pass to Request::call
   * @return stdClass API results
   */
  public function add_media_url($params=array(), $options=array()){
    if (!isset($params['job_id'])){
      throw new \Exception('job_id is required');
    }
    if (!isset($params['media_url'])){
      throw new \Exception('media_url is required');
    }
    $options = $this->_merge($options, $params);
    return $this->_call('job/add_media_url', $options);
  }

  /**
   * Get a URL to the media for an existing job. If the media was 
   * directly uploaded to the job, no URL will be returned.
   * 
   * @param  array params Cielo24 API parameters
   * @param  array options Options to pass to Request::call
   * @return stdClass API results
   */
  public function get_media($params=array(), $options=array()){
    if (!isset($params['job_id'])){
      throw new \Exception('job_id is required');
    }
    $options = $this->_merge($options, $params);
    return $this->_call('job/media', $options);
  }

  /**
   * Perform transcription for a specific Job, you must add 
   * media to the Job before you can perform transcription.
   * 
   * @param  array params Cielo24 API parameters
   * @param  array options Options to pass to Request::call
   * @return stdClass API results
   */
  public function perform_transcription($params=array(), $options=array()){
    if (!isset($params['job_id'])){
      throw new \Exception('job_id is required');
    }
    // set default values
    $params += array(
      'transcription_fidelity' => 'PREMIUM',
      'priority' => 'STANDARD'
    );

    $options = $this->_merge($options, $params);
    return $this->_call('job/perform_transcription', $options);
  }

  /**
   * Get the transcript for a specific Job, the job must have
   * completed transcription before a transcript can be downloaded.
   * 
   * @param  array params Cielo24 API parameters
   * @param  array options Options to pass to Request::call
   * @return string transcript
   */
  public function get_transcript($params=array(), $options=array()){
    if (!isset($params['job_id'])){
      throw new \Exception('job_id is required');
    }
    $options['raw'] = true;
    $options = $this->_merge($options, $params);
    return $this->_call('job/get_transcript', $options);
  }

  /**
   * Get the caption for a specific Job, the job must have
   * completed transcription before a transcript can be downloaded.
   * 
   * @param  array params Cielo24 API parameters
   * @param  array options Options to pass to Request::call
   * @return stdClass|string API results
   */
  public function get_caption($params=array(), $options=array()){
    if (!isset($params['job_id'])){
      throw new \Exception('job_id is required');
    }
    // set default values
    $params += array('caption_format' => 'SRT');

    // if build_url isn't set or is false, return raw results instead
    // of json
    if (!isset($params['build_url']) || $params['build_url'] == false){
      $options['raw'] = true;
    }

    $options = $this->_merge($options, $params);
    return $this->_call('job/get_caption', $options);
  }

  /**
   * Get the ElementList for a specific Job, the job must have
   * completed transcription before a transcript can be downloaded.
   * 
   * @param  array params Cielo24 API parameters
   * @param  array options Options to pass to Request::call
   * @return stdClass API results
   */
  public function get_elementlist($params=array(), $options=array()){
    if (!isset($params['job_id'])){
      throw new \Exception('job_id is required');
    }
    $options = $this->_merge($options, $params);
    return $this->_call('job/get_elementlist', $options);
  }

  /**
   * Get the list of ElementLists for a specific Job, the job must have
   * completed transcription before a transcript can be downloaded.
   * 
   * @param  array params Cielo24 API parameters
   * @param  array options Options to pass to Request::call
   * @return stdClass API results
   */
  public function list_elementlists($params=array(), $options=array()){
    if (!isset($params['job_id'])){
      throw new \Exception('job_id is required');
    }
    $options = $this->_merge($options, $params);
    return $this->_call('job/list_elementlists', $options);
  }



}
