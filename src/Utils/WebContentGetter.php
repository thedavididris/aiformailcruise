<?php

namespace Rubbylab\AiContentToolkit\Utils;

class WebContentGetter
{
    private $url;
    private $response;
    private $dom;
    private $xpath;
    private $assetBaseUrl;
    private $cacheDir;

    public function __construct($url, $cacheDir, $assetBaseUrl = '')
    {
        $this->url = $url;

        if (empty($assetBaseUrl)) {

            $assetBaseUrl = parse_url($url);
            $assetBaseUrl = $assetBaseUrl['scheme'] . '://' . $assetBaseUrl['host'];
        }
        $this->assetBaseUrl = $assetBaseUrl;
        $this->cacheDir = $cacheDir;
    }

    public function crawl($className = '')
    {
        // Fetch the initial webpage
        $this->response = $this->loadResource($this->url, false);

        // Create a DOMDocument and load the HTML response
        $this->dom = $this->loadHTML($this->response);

        // Create a DOMXPath object to query the document
        $this->xpath = new \DOMXPath($this->dom);

        if (!empty($className)) {
            // Get the body element and add custom classname
            $body = $this->xpath->evaluate("/html/body")[0];
            $body->setAttribute("class", $body->getAttribute('class') . ' ' . $className);
        }
    }

    public function crawlWithSocket($ipAddress, $userAgent, $className = '')
    {
        // Fetch the initial webpage
        $this->response = $this->loadResourceSocket($this->url, $ipAddress, $userAgent);


        // Create a DOMDocument and load the HTML response
        $this->dom = $this->loadHTML($this->response);

        // Create a DOMXPath object to query the document
        $this->xpath = new \DOMXPath($this->dom);

        if (!empty($className)) {
            // Get the body element and add custom classname
            $body = $this->xpath->evaluate("/html/body")[0];
            $body->setAttribute("class", $body->getAttribute('class') . ' ' . $className);
        }
    }


    public function getHtml()
    {

        return $this->dom->saveHTML();
    }

    public function loadHTML($html)
    {
        // Load the HTML into a DOMDocument object.
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true); // Disable error reporting
        @$doc->loadHTML($html);
        libxml_use_internal_errors(false); // Re-enable error reporting
        return $doc;
    }

    public function extractSection($html, $xpathSelector)
    {
        // Create a new DOMDocument object and load the HTML
        $dom = $this->loadHTML($html);

        // Create a new DOMXPath object and select the elements matching the selector
        $xpath = new \DOMXPath($dom);
        $elements = $xpath->query($xpathSelector);

        // Create a new DOMDocument object to hold the extracted section
        $section = new \DOMDocument();

        // Add each matching element to the section document
        foreach ($elements as $element) {
            $section->appendChild($section->importNode($element, true));
        }

        // Save the section as HTML and return it
        $html = $section->saveHTML();
        return $html;
    }

    public function inlineResources($html = '', $includeJs = false)
    {
        if (empty($html)) {

            $dom = $this->dom;
            $xpath = $this->xpath;
        } else {

            // Create a new DOMDocument object and load the HTML
            $dom = $this->loadHTML($html);

            // Create a new DOMXPath object and select the elements matching the selector
            $xpath = new \DOMXPath($dom);
        }

        // Inline CSS stylesheets
        foreach ($xpath->query('//link[@rel="stylesheet" or @type="text/css"][@href]') as $link) {

            $href = $link->getAttribute('href');
            $href = stripos($href, '://') == false && !empty($this->assetBaseUrl) ? $this->assetBaseUrl . $href : $href;
            if (filter_var($href, FILTER_VALIDATE_URL)) {
                $css = $this->loadResource($href, true);
                $css = preg_replace('/\s+/', ' ', $css); // remove excess whitespace

                // Parse the CSS content to find url() declarations
                $parsedCss = preg_replace_callback(
                    '/url\([\'"]?([^\'")]+)[\'"]?\)/',
                    function ($matches) use ($href) {
                        $url = trim($matches[1], '\'".');
                        if (substr($url, 0, 1) != '/') $url = '/' . $url;

                        $url = stripos($url, '://') == false && !empty($this->assetBaseUrl) ? $this->assetBaseUrl . $url : $url;
                        if (filter_var($url, FILTER_VALIDATE_URL)) {
                            $data = base64_encode($this->loadResource($url, true));
                            $type = pathinfo($url, PATHINFO_EXTENSION);
                            if ($type == 'svg') $type = $type . '+xml';
                            return 'url(data:image/' . $type . ';base64,' . $data . ')';
                        }
                        return $matches[0]; // return the original url() declaration if the URL is invalid
                    },
                    $css
                );

                $style = $dom->createElement('style', $parsedCss);
                $style->setAttribute('type', 'text/css');
                $link->parentNode->replaceChild($style, $link);
            }
        }

        if ($includeJs) {

            // Inline JavaScript scripts
            foreach ($xpath->query('//script[@src]') as $script) {

                $src = $script->getAttribute('src');
                $src = stripos($src, '://') == false && !empty($this->assetBaseUrl) ? $this->assetBaseUrl . $src : $src;
                if (filter_var($src, FILTER_VALIDATE_URL)) {
                    $js = $this->loadResource($src, true);
                    //$js = preg_replace('/\s+/', ' ', $js); // remove excess whitespace
                    $script->removeAttribute('src');
                    $script->appendChild($dom->createTextNode($js));
                }
            }
        }

        // Replace all image elements with inline base64-encoded versions
        foreach ($xpath->query('//img[@src]') as $img) {

            $src = $img->getAttribute('src');
            $src = stripos($src, '://') == false && !empty($this->assetBaseUrl) ? $this->assetBaseUrl . $src : $src;
            if (filter_var($src, FILTER_VALIDATE_URL)) {
                $data = base64_encode($this->loadResource($src, true));
                $type = pathinfo($src, PATHINFO_EXTENSION);
                if ($type == 'svg') $type = $type . '+xml';
                $img->setAttribute('src', 'data:image/' . $type . ';base64,' . $data);
            }
        }

        // Return the modified HTML
        return $dom->saveHTML();
    }


    /**
     * Deletes the elements with the provided selectors from the HTML.
     * 
     * @param string $html The HTML to modify.
     * @param array $selectors The selectors of the elements to delete.
     * @return string The modified HTML.
     */
    function deleteElementsWithSelectors(string $html, array $selectors): string
    {
        $doc = $this->loadHTML($html);
        $xpath = new \DOMXPath($doc);

        // Remove the elements with the provided selectors.
        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            foreach ($nodes as $node) {
                $node->parentNode->removeChild($node);
            }
        }

        // Get the modified HTML.
        $newHtml = $doc->saveHTML();
        return $newHtml;
    }

    public function removeMetaTags($html, $metaTagTypes = [])
    {

        // Load the HTML content into the DOMDocument
        $dom = $this->loadHTML($html);

        // Get all the meta tags in the document
        $metaTags = $dom->getElementsByTagName('meta');
        $removeMetaTags = [];

        foreach ($metaTags as $metaTag) {
            if (empty($metaTagTypes)) {
                $removeMetaTags[] = $metaTag;
            } else if (in_array($metaTag->getAttribute('name'), $metaTagTypes) || in_array($metaTag->getAttribute('http-equiv'), $metaTagTypes)) {
                $removeMetaTags[] = $metaTag;
            }
        }

        foreach ($removeMetaTags as $metaTag) {
            $metaTag->parentNode->removeChild($metaTag);
        }

        // Return the updated HTML content
        return $dom->saveHTML();
    }

    /**
     * Adds custom CSS to the given HTML string.
     * 
     * @param string $html The HTML string to which the CSS will be added.
     * @param string $css The custom CSS to be added.
     * @return string The modified HTML string with the added custom CSS.
     */
    function addInlineCssToHtml($html, $css)
    {
        // Create a DOMDocument object to load the HTML string
        $doc = $this->loadHTML($html);

        // Create a new style element for the custom CSS and add it to the DOM
        $style = $doc->createElement('style', $css);
        $head = $doc->getElementsByTagName('head')->item(0);
        $head->appendChild($style);

        // Save the modified DOM as a string and return it
        $html_with_css = $doc->saveHTML();
        return $html_with_css;
    }


    public function loadResource($url, $cache = true)
    {

        $cacheFile = '';

        if ($cache) {

            $cacheDir = $this->cacheDir;

            if (!empty($cacheDir)) {

                $cacheFile = $cacheDir . '/' . md5($url);

                if (file_exists($cacheFile)) {
                    // Return cached resource
                    return file_get_contents($cacheFile);
                }
            }
        }

        // Initialize cURL
        $ch = curl_init();
        $ua = request()->userAgent();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, $ua);

        // Execute the cURL request
        $response = curl_exec($ch);

        // Check for errors
        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch));
        }

        // Close the cURL session
        curl_close($ch);

        if (!empty($cacheFile))
            file_put_contents($cacheFile, $response);

        return $response;
    }

    /**
     * Downloads the HTML content from the given URL using a socket and returns it.
     *
     * @param string $url The URL to download the HTML from.
     * @param string $clientIp The IP address to use as the client IP in the HTTP request headers.
     * @param string $userAgent The user agent string to use in the HTTP request headers.
     * @return string The HTML content of the web page, including the DOCTYPE declaration, if present.
     *                If the HTML content could not be extracted, returns an error message.
     */
    public function loadResourceSocket($url, $clientIp, $userAgent)
    {

        // Create the socket
        $socket = fsockopen('ssl://' . parse_url($url, PHP_URL_HOST), 443, $errno, $errstr);

        // Send the HTTP request headers
        fwrite($socket, "GET " . parse_url($url, PHP_URL_PATH) . " HTTP/1.1\r\n");
        fwrite($socket, "Host: " . parse_url($url, PHP_URL_HOST) . "\r\n");
        fwrite($socket, "User-Agent: $userAgent\r\n");
        fwrite($socket, "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9\r\n");
        fwrite($socket, "Accept-Language: en-US,en;q=0.9\r\n");
        fwrite($socket, "Referer: https://www.google.com/\r\n");
        fwrite($socket, "X-Forwarded-For: $clientIp\r\n");
        fwrite($socket, "Connection: close\r\n\r\n");

        // Read the response and display it
        $response = '';
        while (!feof($socket)) {
            $response .= fgets($socket, 1024);
        }
        fclose($socket);

        if (preg_match('/<!DOCTYPE[^>]*>(.*)<\/html>/si', $response, $matches)) {
            return $matches[0];
        } else {
            return $response; //'Error: could not extract HTML content';
        }
    }
}
