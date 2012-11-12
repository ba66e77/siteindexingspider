SiteIndexingSpider
==================

Description
-----------
Given a url, the spider will walk the url indexing all links to content on
on the site. Off-site links are ignored, as are javascript links (e.g.,
href="javascript:..."). Results are populated into the $assets attribute,
which is an array where each href is a key and the value is an array of
links to that href.

Dependencies
-------------
The spider makes use of some functionality provided by Michael Schrenk for his
book '[Webbots, Spiders, and Screen Scrapers](http://webbotsspidersscreenscrapers.com/)'. 
Download the files from the book's webpage and put them into a WSS directory. And buy the book.

Usage Example
-------------
    
    require_once('SiteIndexingSpider.php');
    
    $start_url = "http://d6.localhost"; 
    
    $spider = new SiteIndexingSpider();
    $spider->loggingLevel = 1;
    $spider->spider($start_url);
    
    foreach ($spider->assets as $url => $links) {
      echo "$url linked to from " . count($links) . " locations" . PHP_EOL;
    }
