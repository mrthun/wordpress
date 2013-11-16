<?php
/**
 * @file RemoteConnector.php
 * 
 * Retrieves remote file
 */
define('REMOTE_CALL_METHOD_DIRECT',              0);
define('REMOTE_CALL_METHOD_CURL',                1);
define('REMOTE_CALL_METHOD_SOCKET',              2);
define('REMOTE_CALL_CURL_ERROR',              1000);
define('REMOTE_CALL_SOCKET_UNKNOWN_ERROR',    1001);
define('REMOTE_CALL_SOCKET_CONNECTION_ERROR', 1002);

class RemoteConnector {
  /**
   * The remote URL to retrieve
   */
  protected $url;
  
  protected $remote_file;
  
  protected $error;
  
  protected $url_parts;
  
  protected $status;
  
  protected $method;
  
  public function __construct($url) {
    $this->url = trim($url);
    
    $this->checkURL($this->url);
    
    if(ini_get('allow_url_fopen')) {
      $this->accessDirect();
    } elseif (function_exists('curl_init')) {
      $this->useCurl();
    } else {
      $this->useSocket();
    }
  }
  
  public function __toString() {
    if(!$this->remote_file) {
      $this->remote_file = '';
    }
    return $this->remote_file;
  }
  
  public function getStatus() {
    return $this->status;
  }
  
  public function getErrorMessage() {
    if (is_null($this->_error)) {
      $this->setErrorMessage();
    }
    return $this->error;
  }
  
  protected function checkURL($str) {
    $flags = FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED;
    $urlOK = filter_var($str, FILTER_VALIDATE_URL, $flags);
    $this->url_parts = parse_url($str);
    $schemeOK = preg_match('/^http(s)?$/i', $this->url_parts['scheme']);
    $domainOK = preg_match('/^[^.]+?\.\w{2}/', $this->url_parts['host']);
    if(!$urlOK || !$schemeOK || !$domainOK) {
      throw new Exception($str . ' is not a valid URL. Only HTTP or HTTPS protocols are supported.', E_USER_ERROR);
    }
  }
  
  protected function accessDirect() {
   $this->remote_file = @file_get_contents($this->url);
   $headers = @get_headers($this->url);
   $this->method = REMOTE_CALL_METHOD_DIRECT;
   if($headers) {
     preg_match('/\d{3}/', $headers[0], $m);
     $this->status = intval($m[0]);
   } 
  }
  
  protected function useCurl() {
    $this->method = REMOTE_CALL_METHOD_CURL;
    
    if($session = curl_init($this->url)) {
      curl_setopt($session, CURLOPT_HEADER, FALSE);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
      $this->remote_file = curl_exec($session);
      $this->status = curl_getinfo($session, CURLINFO_HTTP_CODE);
      curl_close($session);
    } else {
      $this->status = REMOTE_CALL_CURL_ERROR;
      $this->error = 'Could not establish cURL session';
    }
  }
  
  protected function useSocket() {
    $this->method = REMOTE_CALL_METHOD_SOCKET;
    
    $port = (isset($this->url_parts['port']) ? $this->url_parts['port'] : 80);
    $remote = @fsockopen($this->url_parts['host'], $port, $errno, $errstr, 30);
    if(!$remote) {
      $this->remote_file = FALSE;
      $this->code = REMOTE_CALL_SOCKET_CONNECTION_ERROR;
      $this->error = 'Could not open socket connect.';
      if($errstr) {
       $this->status = $errno;
       $this->error .= '  Error: ' . $errno . ' - ' . $errstr;
      } else {
        $this->status = REMOTE_CALL_SOCKET_UNKNOWN_ERROR;
        $this->error .= ' Check the domain name or IP address.';
      }
    } else {
      $path = (isset($this->url_parts['path']) ? $this->url_parts['path'] : '/');
      
      if(isset($this->url_parts['query'])) {
        $path .= ('?' . $this->url_parts['query']);
      }
      
      $out = "GET $path HTTP/1.1\r\n";
      $out .= "Host: {$this->_url_parts['host']}\r\n";
      $out .= "Connection: Close\r\n\r\n";
      // Send the headers
      fwrite($remote, $out);
      // Capture the response
      $this->_remoteFile = stream_get_contents($remote);
      fclose($remote);
      if ($this->_remoteFile) {
        $this->removeHeaders();
      }
    }
  }
  
  protected function removeHeaders() {
    $parts = preg_split('#\r\n\r\n|\n\n#', $this->_remoteFile);
     if (is_array($parts)) {
        $headers = array_shift($parts);
        $file = implode("\n\n", $parts);
        if (preg_match('#HTTP/1\.\d\s+(\d{3})#', $headers, $m)) {
          $this->_status = $m[1];
        }
        if (preg_match('#Content-Type:([^\r\n]+)#i', $headers, $m)) {
          if (stripos($m[1], 'xml') !== false || stripos($m[1], 'html') !== false) {
            if (preg_match('/<.+>/s', $file, $m)) {
              $this->_remoteFile = $m[0];
            } else {
              $this->_remoteFile = trim($file);
            }
          } else {
            $this->_remoteFile = trim($file);
          }
        } else {
          $this->_remoteFile = trim($file);
        }
     }
  }
  
  protected function setErrorMessage() {
    if ($this->status == 200 && $this->_remoteFile) {
      $this->error = '';
    } else {
      switch ($this->status) {
        case 200:
        case 204:
        $this->error = 'Connection OK, but file is empty.';
        break;
        case 301:
        case 302:
        case 303:
        case 307:
        case 410:
        $this->error = 'File has been moved or does not exist.';
        break;
        case 305:
        $this->error = 'File must be accessed through a proxy.';
        break;
        case 400:
        $this->error = 'Malformed request.';
        break;
        case 401:
        case 403:
        $this->error = 'You are not authorized to access this page.';
        break;
        case 404:
        $this->error = 'File not found.';
        break;
        case 407:
        $this->error = 'Proxy requires authentication.';
        break;
        case 408:
        $this->error = 'Request timed out.';
        break;
        case 500:
        $this->error = 'The remote server encountered an internal error.';
        break;
        case 503:
        $this->error = 'The server cannot handle the request at the moment.';
        break;
        default:
        $this->error = 'Undefined error. Check URL and domain name.';
        break;
      }
    }
  }
    
}

