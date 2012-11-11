<?php
/**
 * @file
 *
 * Defines the functionality for a simple site indexing spider.
 *
 * Given a url, the spider will walk the url indexing all links to content on
 * on the site. Off-site links are ignored, as are javascript links (e.g.,
 * href="javascript:...").  Results are populated into the $assets attribute,
 * which is an array where each href is a key and the value is an array of
 * links to that href.
 *
 * Dependencies:
 * The spider makes use of some functionality provided by Max Schrenk for his 
 * book 'Webbots, Spiders, and Screen Scrapers.'  Download the files from the
 * book's webpage and put them into a WSS directory.  And buy the book.
 *
 * @todo: add a 'quick' option to not fetch headers for each linked page 
 */

require('WSS/LIB_resolve_addresses.php');
require('WSS/LIB_http.php');

class SiteIndexingSpider {

  public $assets = array();
  private $toCheck = array();
  private $checked = array();

  public $loggingLevel = 0;
  public $includeQueryString = FALSE;

  /**
   * Initiates spidering of the selected url.
   *
   * @param string $url
   */
  public function spider($url) {
  
    $this->processPage($url); 
 
    while ($this->toCheck) {
      $url = array_shift($this->toCheck);
      $this->processPage($url);
    }
  }

  /**
   * Processes an individual url.
   */
  public function processPage($url) {
    $output = $this->getPageLinks($url);
    $this->checked[] = $url;
  
    $this->processPageLinks($output);
  }
  
  /**
   * Fetches links from the given url
   *
   * @param string $url
   * @return array
   */
  private function getPageLinks($url) {

    if($this->loggingLevel) {
      echo "getting page links for $url" . PHP_EOL;
    }
  
    $page_links = array();
    
    $document = $this->getPageDocument($url);
    
    $links = $document->getElementsByTagName('a');
  
    foreach ($links as $link) {
      $href = trim($link->getAttribute('href'));
    
      // ignore anchors without hrefs or w javasript hrefs
      if (!$href || substr($href, 0, 10) == 'javascript') {
        continue;
      }
    
      // convert relative paths to full paths
      $resolved_href = resolve_address($href, $url);
      
      // strip fragment and query string
      $resolved_href = $this->cleanURL($resolved_href);
    
      // strip trailing slashes from resolved paths
      if (substr($resolved_href, -1) == '/') {
        $resolved_href = substr($resolved_href, 0, -1);
      }
      
      // ignore off-site links
      if (parse_url($resolved_href, PHP_URL_HOST) !== parse_url($url, PHP_URL_HOST)) {
        continue;
      }
    
      // get header information from the resolved path so we can tell file type
      $header = http_header($resolved_href, $url);
  
      $page_links[] = array(
        'href' => $resolved_href,
        'title' => $link->nodeValue,
        'content_type' => $this->getDataFromHeader('content_type', $header),
        'http_code' => $this->getDataFromHeader('http_code', $header),
      );
    }
    return array(
      'title' => $this->getPageTitle($document),
      'url'   => $url,
      'links' => $page_links,
    );
  }
 
  /**
   * Fetches page content and converts it to a DOM Document.
   *
   * @param string $url
   * @return DOMDocument $document;
   */
  function getPageDocument($url) {
    $input = @file_get_contents($url) or die("Could not access file: $url"); 
  
    $document = new DOMDocument();
    $document->recover = true;
    $document->strictErrorChecking = false;
    @$document->loadHTML($input); //disable warnings coming from malformed html
  
    return $document;
  }

  /**
   * Get the title element from the DOM Document.
   *
   * @param DOMDocument $document
   * @return string content of title element
   */
  function getPageTitle($document) {

    $xpath = new DOMXPath($document);
    $page_title_list = $xpath->query('/html/head/title');
    
    foreach($page_title_list as $page_title_element) {
      $page_title = $page_title_element->nodeValue ? $page_title_element->nodeValue : '-No Title-';
    }

    return $page_title;
  } 
  

  /**
   * Removes any fragement and, if set, query string from the provided url. 
   *
   * @todo: make PECL a dependcy and use http_build_url instead
   */
  function cleanURL($url) {
    $pieces = parse_url($url);
    $url = $pieces['scheme'] . '://' . $pieces['host'];
    if (array_key_exists('path', $pieces)) {
      $url .= $pieces['path'];
    }
    if ($this->includeQueryString && array_key_exists('query', $pieces)) {
      $url .= '?' . $pieces['query'];
    }
    return $url;
  }


  /**
   * Returns the requested data element for a given array of header items.
   *
   * @param string $dataElement 
   * @param array $header 
   * @return string
   */
  private function getDataFromHeader($dataElement, $header) {
    switch ($dataElement) {
      case 'content_type':
        $data = $header['STATUS']['content_type'];
        if (($pos = strpos($data, ';')) !== FALSE) {
          $data = substr($data, 0, $pos); 
        }
        break;
      case 'http_code':
        $data = $header['STATUS']['http_code'];
        break;
      default:
        $data = FALSE;
    }
    return trim($data);
  }
  
  /**
   * Manages results from link processing.
   *
   * @todo: strip query strings from URLs (?) - or should we?
   */
  private function processPageLinks($links) {
  
    foreach ($links['links'] as $link) {
      // add to assets listing
      $this->assets[$link['href']][] = array(
        'linked_from' => $links['title'],
        'linked_from_url' => $links['url'],
        'link_title' => $link['title'],
      );
  
      // if text/html, add to to_check listing
      if ($link['content_type'] == 'text/html' 
        && !in_array($link['href'], $this->checked) 
        && !in_array($link['href'], $this->toCheck)) {
          $this->toCheck[] = $link['href'];
      }
    } 
  }
}
?>
