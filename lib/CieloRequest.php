<?php

namespace Cielo;

use \Exception as Exception;

/**
 * Cielo API Request class. Requires cURL
 *
 */
class Request {

  /**
   * A list of available API actions
   * @var array
   */
  private static $actions = [
    "account/login",
    "account/logout",
    "account/update_password",
    "account/generate_api_key",
    "account/remove_api_key",
    "job/new",
    "job/authorize",
    "job/del",
    "job/info",
    "job/list",
    "job/add_media",
    "job/add_media_url",
    "job/media",
    "job/perform_transcription",
    "job/get_transcript",
    "job/get_caption",
    "job/get_elementlist",
    "job/list_elementlists",
  ];

  /**
   * Default cURL options
   * 
   * @var array
   */
  public static $curlOptions = array(
    CURLOPT_USERAGENT       => 'Mozilla/5.0',
    CURLOPT_POST            => false,
    CURLOPT_RETURNTRANSFER  => true,
    CURLOPT_HEADER          => false,
    CURLOPT_FAILONERROR     => false,
    CURLOPT_SSL_VERIFYPEER  => false,
    CURLOPT_FOLLOWLOCATION  => true,
    CURLOPT_MAXREDIRS       => 5
  );
  
  
  /**
   * Call the Cielo API $method based on $options
   * 
   * @param array $options
   *
   * @return stdClass API response data
   */
  public static function call($options = array()){

    // check required 'method' option, is it valid?
    if($options['method'] == null || !in_array($options['method'], self::$actions) ){
      throw new Exception('A valid method must be provided.');
    }

    // build the request url
    $options['url'] = self::buildUrl($options);

    // initialize curl with the new url
    $c = curl_init($options['url']);

    // Overload the default curl options
    $curlOptions = (array) $options['cURL'] + self::$curlOptions;

    // set post data
    if(isset($options['data'])){
      $curlOptions[CURLOPT_POST] = true;
      $curlOptions[CURLOPT_POSTFIELDS] = $options['data'];
      // set a default content-type if one hasn't been set
      $options['headers'] = $options['headers'] + array("Content-Type: " . $options['contentType']);
    }

    // set optional http headers
    if (isset($options['headers']) && is_array($options['headers'])){
      $curlOptions[CURLOPT_HTTPHEADER] = $options['headers'];
    }

    // set cURL options
    curl_setopt_array($c, $curlOptions);

    // execute request, save extra info and log any errors
    $raw = curl_exec($c);
    $info = curl_getinfo($c);
    $response = compact('raw', 'info') + array(
      'code' => $info['http_code'],
      'json' => json_decode(utf8_encode($raw)),
      'curl_error' => curl_error($c),
      'error' => null
    );

    $response = self::setError($response);
    
    curl_close($c);

    return $response;

  }

  /**
   * Set the error property on the response object, if there is an error
   * @param  array response object
   * @return array response object
   */
  public static function setError($response){
    $r = $response['json'];
    if ($response['code'] > 399 || 
        isset($r->ErrorType) || 
        isset($r->ErrorComment) ){
      $response['error'] = $r;
    }
    return $response;
  }
  
  /**
   * @param  array of options
   * @return string the request url
   */
  public static function buildUrl($o=array()){
    return $o['url'] . $o['method'] . '?&v=' . $o['version'] . self::queryString($o['query']);
  }

  /**
   * Generate a query string from an array or string. 
   * Similar to PHPs http_build_query but with `rawurlencode`
   * 
   * @param string|array $query 
   * @return string urlencoded query string
   */
  public static function queryString($query=null){
    if($query === null){
      return '';
    }
    if(is_string($query)){
      return '&' . $query;
    }
    $results = '';
    foreach($query as $key => $value){
      $results .= '&' . urlencode($key) .'='. rawurlencode($value);
    }
    return $results;
  }

  
}
