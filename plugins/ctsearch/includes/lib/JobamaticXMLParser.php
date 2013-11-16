<?php
define('JOBAMATIC_XML_PARSER_ERROR', 2000);

require_once(dirname(__FILE__) . '/JobamaticSearchResult.php');

class JobamaticXMLParser {
  protected static $instance = NULL;
  
  protected $error;
  protected $code;
  
  protected function __construct() {
    return $this;
  }
  
  public static function getParser() {
    if(is_null(self::$instance)) {
      self::$instance = new JobamaticXMLParser();
    }
    return self::$instance;
  }
  
  public function parse($str) {
    $result = FALSE;
    
    if(!$xml = @simplexml_load_string($str)) {
      $this->code = JOBAMATIC_XML_PARSER_ERROR;
      $this->error = 'Could not parse XML';
    } else {
      if($xml->error) {
        $attribs = $xml->error->attributes();
        $this->error = trim($attribs['type']);
        $this->code = trim($attribs['code']);
      } else {
        $result = $this->xml2Object($xml);
      }
    }
    
    return $result;
  }
  
  public function getErrorMessage() {
    return $this->error;
  }
  
  protected function xml2Object(SimpleXMLElement $el) {
    $request = $el->rq;
    $attributes = $request->attributes();
    $obj = new JobamaticSearchResult((string) $request->t, $attributes['url'], strtotime((string) $request->dt), (int) $request->si, (int) $request->rpd, (int) $request->tr, (int) $request->tv);
    
    foreach($el->rs->r as $child) {
      $obj->addJob($this->parseJob($child));
    }
    
    return $obj;
  }
  
  protected function parseJob(SimpleXMLElement $el) {
    $attr = $el->cn->attributes();
    $company = array('name' => (string)$el->cn, 'url' => trim((string) $attr['url']));
    $attr = $el->src->attributes();
    $source = array('name' => (string) $el->src, 'url' => trim((string) $attr['url']));
    $attr = $el->loc->attributes();
    
    $location = array(
      'raw' => (string) $el->loc,
      'city' => trim((string) $attr['cty']),
      'state' => (string) $attr['st'],
      'postal_code' => (string) $attr['postal'],
      'country' => (string) $attr['country']
    );
    
    $location['county'] = trim((string) $attr['county']);
    
    $location['region'] = trim((string) $attr['region']);
    
    /*
     * Sometimes if a job is posted for Washington, DC, the
     * city will be empty, so this fixes it.
     */
    if($location['state'] == 'DC' && empty($location['city'])) {
      $location['city'] = 'Washington';
    }
    
    unset($attr);
    
    return new JobamaticJob((string) $el->jt, $company, $source, (string) $el->ty, $location, (string) $el->ls, (string) $el->dp, (string) $el->e);
  }
}
