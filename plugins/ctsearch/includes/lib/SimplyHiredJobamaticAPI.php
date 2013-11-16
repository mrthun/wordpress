<?php
require_once(dirname(__FILE__) . '/RemoteConnector.php');
require_once(dirname(__FILE__) . '/JobamaticXMLParser.php');

define('JOBAMATIC_INVALID_URL_ERROR', 3000);
define('JOBAMATIC_XMLPARSER_ERROR', 3001);

class SimplyHiredJobamaticAPI {
  protected $pshid;
  protected $jbd;
  protected $ssty;
  protected $cflg;
  protected $clip;
  protected $error;
  protected $code;
  protected $data;

  const JOBAMATIC_ENDPOINT = 'http://api.simplyhired.com/a/jobs-api/xml-v2/';

  public function __construct($pshid, $jbd) {
    $this->pshid = trim($pshid);
    $this->jbd = trim($jbd);
    $this->ssty = 2;
    $this->cflg = 'r';
    $this->clip = $_SERVER['REMOTE_ADDR'];
    $this->error = NULL;
    $this->code = NULL;
    $this->data = NULL;
  }

  public function search($query, $size = 10, $page = 0, $location = '', $miles = 5, $sort = 'rd') {
    $params = array(
      'q' => rawurlencode(trim($query)),
      'sb' => $sort,
      'ws' => $size,
      'pn' => (intval($page) < 1 ? 0 : intval($page)),
    );

    if (!is_null($location) && $location != '') {
      $params['l'] = rawurlencode($location);
    }

    if (!is_null($location) && intval($miles) > 0) {
      $params['m'] = $miles;
    }

    $this->call($params);
    if($this->code == 200) {
      return $this->data;
    } else {
      return FALSE;
    }
  }

  protected function call($criteria) {
    $url = self::JOBAMATIC_ENDPOINT . '%s?';

    $api_identity = array(
      'pshid' => $this->pshid,
      'jbd' => $this->jbd,
      'ssty' => $this->ssty,
      'clip' => $this->clip,
      'cflg' => $this->cflg,
    );

    $identity_string = array();
    foreach ($api_identity as $key => $value) {
      $identity_string[] = $key . '=' . $value;
    }

    $url .= implode('&', $identity_string);

    $params = array();

    foreach ($criteria as $key => $value) {
      $params[] = $key . '-' . $value;
    }

    $url = sprintf($url, implode('/', $params));

    try {
      $connector = new RemoteConnector($url);
      $this->code = $connector->getStatus();
    } catch (Exception $e) {
      $this->error = $e->getMessage();
      $this->code = JOBAMATIC_INVALID_URL_ERROR;
      return;
    }

    $this->parseXML($connector->__toString());
  }

  protected function parseXML($data) {
    $parser = JobamaticXMLParser::getParser();
    if(!$this->data = $parser->parse($data)) {
      $this->code = JOBAMATIC_XMLPARSER_ERROR;
      $this->error = 'JobamaticXMLParser error: ' . $parser->getErrorMessage();
    }
  }
}
