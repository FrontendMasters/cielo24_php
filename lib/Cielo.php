<?php

namespace Cielo;

use \Exception as Exception;


/**
 * Client library for Cielo24 API
 * @see  http://cielo24.readthedocs.org/en/latest/index.html
 */
class API {

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


  public function __construct($o=array()){
    // initialize stuff
    if (isset($o['user']))    $this->user = $o['user'];
    if (isset($o['key']))     $this->key = $o['key'];
    if (isset($o['token']))   $this->token = $o['token'];
    if (isset($o['version'])) $this->version = $o['version'];
    if (isset($o['test']))    $this->test = $o['test'];
  }

  /**
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
   * is an array.
   * @param  array $options
   * @param  array $params
   * @return array $options
   */
  public function _merge($options, $params=array()){
    if(!isset($options['query'])){
      $options['query'] = array();
    }
    if (!is_array($options['query'])){
      throw new Exception('The query must be an array');
    }
    if (!isset($params['api_token'])){
      $params['api_token'] = $this->getToken();
    }
    $options['query'] = $params + $options['query'];
    return $options;
  }

  /**
   * @param array options
   */
  public function _setDefaults($options){
    $defaults = array(
      'method' => null,
      'url' => $this->getBaseUrl(),
      'api_token' => $this->getToken(),
      'version' => $this->version,
      'query' => array(),
      'data' => null,
      'contentType' => 'text/plain',
      'headers' => array(),
      'cURL' => array()
    );
    $options += $defaults;

    return $options;
  }

  private function _call($method, $options){
    $options['method'] = $method;
    $options = $this->_setDefaults($options);
    $results = \Cielo\Request::call($options);

    $this->lastError = null;
    if ($results['error'] != null){
      $this->lastError = $results['error'];
    }

    return $results['json'];
  }

  /**
   * Access Control
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
  
  public function logout($params=array(), $options=array()){
    $options = $this->_merge($options, $params);
    $result = $this->_call('account/logout', $options);
    if ($this->lastError == null){
      // clear the token now that it's invalid
      $this->token = null;
    }
    return $result;
  }

  public function generate_key($params=array(), $options=array()){
    if (!isset($params['account_id'])){
      $params['account_id'] = $this->user;
    } 
    $options = $this->_merge($options, $params);
    return $this->_call('account/generate_api_key', $options);
  }
  
  public function update_password($params=array(), $options=array()){
    if (!isset($params['new_password'])){
      throw new Exception('new_password is required');
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

  public function remove_api_key($params=array(), $options=array()){
    if (!isset($params['secure_key'])){
      $params['secure_key'] = $this->key;
    }
    $options = $this->_merge($options, $params);
    return $this->_call('account/remove_api_key', $options);
  }





  /**
   * Job Control
   */

  public function job_create($params=array(), $options=array()){
    $options = $this->_merge($options, $params);
    return $this->_call('job/new', $options);
  }

  public function job_authorize($params=array(), $options=array()){
    if (!isset($params['job_id'])){
      throw new Exception('job_id is required');
    }
    $options = $this->_merge($options, $params);
    return $this->_call('job/authorize', $options);
  }

  public function job_delete($params=array(), $options=array()){
    if (!isset($params['job_id'])){
      throw new Exception('job_id is required');
    }
    $options = $this->_merge($options, $params);
    return $this->_call('job/del', $options);
  }

  public function job_info($params=array(), $options=array()){
    if (!isset($params['job_id'])){
      throw new Exception('job_id is required');
    }
    $options = $this->_merge($options, $params);
    return $this->_call('job/info', $options);
  }

  public function job_list($params=array(), $options=array()){
    $options = $this->_merge($options, $params);
    return $this->_call('job/list', $options);
  }

  /**
   * Perform transcription for a specific Job
   * 
   * @param  array params Cielo24 API parameters
   * @param  array options Options to pass to Request::call
   * @return stdClass API results
   */
  public function perform_transcription($params=array(), $options=array()){
    if (!isset($params['job_id'])){
      throw new Exception('job_id is required');
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
   * Get the transcript for a specific Job
   * 
   * @param  array params Cielo24 API parameters
   * @param  array options Options to pass to Request::call
   * @return stdClass API results
   */
  public function get_transcript($params=array(), $options=array()){
    if (!isset($params['job_id'])){
      throw new Exception('job_id is required');
    }
    $options = $this->_merge($options, $params);
    return $this->_call('job/get_transcript', $options);
  }

  /**
   * Get the caption for a specific Job
   * 
   * @param  array params Cielo24 API parameters
   * @param  array options Options to pass to Request::call
   * @return stdClass API results
   */
  public function get_caption($params=array(), $options=array()){
    if (!isset($params['job_id'])){
      throw new Exception('job_id is required');
    }
    // set default values
    $params += array('caption_format' => 'SRT');

    $options = $this->_merge($options, $params);
    return $this->_call('job/get_caption', $options);
  }



}
