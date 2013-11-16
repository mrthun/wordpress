<?php
class JobamaticSearchResult {
  protected $title;
  protected $url;
  protected $date;
  protected $start_index;
  protected $num_results;
  protected $total_results;
  protected $total_visible;
  protected $items;
  protected $current;

  public function __construct($title, $url = '', $date = 0, $start_index = -1, $num_results = 0, $total_results = 0, $total_visible = 0) {
   $this->title = trim($title);
   $this->url = trim($url);
   $this->date = intval($date);
   $this->start_index = intval($start_index);
   $this->num_results = intval($num_results);
   $this->total_results = intval($total_results);
   $this->total_visible = intval($total_visible);
   $this->items = array();
   $this->current = 0;
  }

  public function getTitle() {
    return $this->title;
  }

  public function getURL() {
    return $this->url;
  }

  public function getDate() {
    return $this->date;
  }

  public function getStartIndex() {
    return $this->start_index;
  }

  public function getNumResults() {
    return $this->num_results;
  }

  public function getTotalResults() {
    return $this->getTotalAvailable();
  }

  public function getTotalVisible() {
    return $this->total_visible;
  }

  public function addJob(JobamaticJob $item) {
    if(!is_array($this->items)) {
      $this->items = array();
    }
    foreach($this->items as $obj) {
      if($item == $obj) return;
    }
    $this->items[] = $item;
  }

  public function getJob($idx) {
    return !isset($this->items[$idx]) ? FALSE : $this->items[$idx];
  }

  public function getAllJobs() {
    return $this->items;
  }

  public function getTotalPages() {
    if(!count($this->items)) return 0;
    return ceil($this->total_visible / $this->num_results);
  }

  public function getCurrentPage() {
    if(!count($this->items)) return -1;
    return ceil($this->start_index / $this->num_results)+1;
  }

  /*
   * This is the number of jobs available from SimplyHired.
   * THIS IS NOT THE NUMBER THAT WILL BE RETURNED VIA THE API.
   */
  public function getTotalAvailable() {
    return $this->total_results;
  }

  public function has_next() {
	$item = $this->getJob($this->current);
	$this->current++;
	return $item;
  }
}

class JobamaticJob {
  protected $title;
  protected $company;
  protected $source;
  protected $type;
  protected $location;
  protected $last_seen;
  protected $date_posted;
  protected $excerpt;

  public function __construct($title, $company = NULL, $source = NULL, $type = NULL, $location = NULL, $last_seen = NULL, $date_posted = NULL, $excerpt = NULL) {
    $this->setTitle($title);
    $this->setCompany($company);
    $this->setSource($source);
    $this->setType($type);
    $this->setLocation($location);
    $this->setDate('last_seen', $last_seen);
    $this->setDate('date_posted', $last_seen);
    $this->setExerpt($excerpt);
  }

  public function getTitle() {
    return $this->title;
  }

  public function getCompany($link = TRUE, $target = '_blank') {
    return ($link && isset($this->company['url']) && $this->company['url'] != '' ? '<a href="'.$this->company['url'].'" target="'.$target.'">'.$this->company['name'].'</a>' : $this->company['name']);
  }

  public function getSource($link = TRUE, $target = '_blank') {
  	return ($link && isset($this->source['url']) && $this->source['url'] != '' ? '<a href="'.$this->source['url'].'" target="'.$target.'">'.$this->source['name'].'</a>' : $this->source['name']);
  }

  public function getURL() {
  	return (isset($this->source['url']) ? $this->source['url'] : NULL);
  }

  public function getType() {
    return $this->type;
  }

  public function getLocation($raw = FALSE) {
    return !$raw ? $this->location : (isset($this->location['raw']) ? $this->location['raw'] : NULL);
  }

  public function getLastSeen() {
    return $this->last_seen;
  }

  public function getDatePosted() {
    return $this->date_posted;
  }

  public function getExcerpt() {
    return $this->excerpt;
  }

  protected function setTitle($val) {
    $val = trim((string) $val);
    $this->title = $val;
  }

  protected function setCompany($val) {
    if(is_array($val) && isset($val['name']) && isset($val['url'])) {
      $this->company = array('name' => trim($val['name']), 'url' => (empty($val['url']) ? NULL : trim($val['url'])));
    } else {
      $val = trim((string) $val);
      if(!empty($val)) {
        $this->company = array('name' => $val, 'URL' => NULL);
      } else {
        $this->company = array('name' => NULL, 'URL' => NULL);
      }
    }
  }

  protected function setType($val) {
    $val = trim((string) $val);
    if(!empty($val)) {
      $this->type = $val;
    } else {
      $this->type = NULL;
    }
  }

  protected function setLocation($val) {
    if(!is_null($val) && !is_array($val)) {
      throw new Exception('JobamaticJob::setLocation() expects an associative array with the following keys as a parameter - KEYS: raw, city, state, postal_code, country [county, region].', E_USER_ERROR);
    } elseif(is_null($val)) {
      $this->location = NULL;
    } else {
      $this->location = array();
      $required_keys = array('raw', 'city', 'state', 'postal_code', 'country');
      $keys = array_keys($val);
      foreach($required_keys as $k) {
        if(!in_array($k, $keys)) {
          throw new Exception('JobamaticJob::setLocation() expects an associative array with the following keys as a parameter - KEYS: raw, city, state, postal_code, country [county, region].', E_USER_ERROR);
        }
      }
      $valid_keys = array_merge($required_keys, array('county', 'region'));
      foreach($val as $k => $v) {
        if(in_array($k, $valid_keys)) {
          $this->location[$k] = trim($val[$k]);
        }
      }
    }
  }

  protected function setSource($val) {
    if(!is_null($val) && !is_array($val)) {
      throw new Exception('JobamaticJob::setSource() expects an associative array with the following keys as a prameter - KEYS: name, url');
    } elseif(is_null($val)) {
     $this->source = NULL;
    } else {
      if(!isset($val['name']) || !isset($val['url'])) {
        throw new Exception('JobamaticJob::setSource() expects an associative array with the following keys as a prameter - KEYS: name, url');
      }
      $this->source = array('name' => trim($val['name']), 'url' => trim($val['url']));
    }
  }

  protected function setExerpt($val) {
    $val = trim((string) $val);
    if(!empty($val)) {
      $this->excerpt = $val;
    } else {
      $this->excerpt = NULL;
    }
  }

  protected function setDate($which, $val) {
    if(!is_numeric($val)) {
      $val = strtotime($val);
    }
    if($which != 'last_seen' && $which != 'date_posted') {
      throw new Exception('Attempt to set invalid date property: ' . $which, E_USER_ERROR);
    }
    $this->$which = $val;
  }
}
