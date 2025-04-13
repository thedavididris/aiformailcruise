<?php

namespace Rubbylab\AiContentToolkit\Services;

use Rubbylab\AiContentToolkit\Utils\WebContentGetter;

/**
 * The SpamScore class provides functionality for checking the spam score of an email address
 * using a third-party provider. It encapsulates the provider and language settings and provides
 * methods to get and set them. The getPageUrl() method returns the URL of the spam score page
 * for the given email address, using the configured provider and language settings. The class
 * enforces constraints on the provider, language, and email address inputs, and ensures that
 * they are properly sanitized to prevent bugs and vulnerabilities.
 */
class SpamScore
{
    /**
     * The spam score provider to use (default: 'mailtester')
     *
     * @var string
     */
    private $provider;

    /**
     * The provider configuration options
     *
     * @var array
     */
    public $providerConfig;

    /**
     * The language to use for the spam score page (default: 'en')
     *
     * @var string
     */
    private $lang;

    /**
     * Create a new instance of the SpamScore class
     *
     * @param string $provider The spam score provider to use (default: 'mailtester')
     * @param string $lang The language to use for the spam score page (default: 'en')
     */

    public function __construct(array $providerConfig = [])
    {
        $provider = $providerConfig['provider'] ?? 'mailtester';
        $lang = $providerConfig['lang'] ?? 'en';

        $this->providerConfig = $providerConfig;

        if ($provider == 'mailtester') {
            if (empty($this->providerConfig['cacheDir'])) {
                throw new \InvalidArgumentException("cacheDir is required for " . $provider);
            }
        }

        $this->setProvider($provider);
        $this->setLanguage($lang);
    }

    /**
     * Get the current spam score provider
     *
     * @return string The spam score provider
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Set the spam score provider
     *
     * @param string $provider The spam score provider to use
     * @throws \InvalidArgumentException if the provider is invalid
     */
    public function setProvider($provider)
    {
        // Ensure that the provider is valid
        $validProviders = ['mailtester'];
        if (!in_array($provider, $validProviders)) {
            throw new \InvalidArgumentException("Invalid provider: $provider");
        }

        $this->provider = $provider;
    }

    /**
     * Get the current language setting
     *
     * @return string The language setting
     */
    public function getLanguage()
    {
        return $this->lang;
    }

    /**
     * Set the language to use for the spam score page
     *
     * @param string $lang The language to use
     * @throws \InvalidArgumentException if the language is invalid
     */
    public function setLanguage($lang)
    {
        $validLanguages = ['en', 'fr', 'es'];
        if (!in_array($lang, $validLanguages)) {
            throw new \InvalidArgumentException("Invalid language: $lang");
        }

        $this->lang = $lang;
    }

    /**
     * Get the URL of the spam score page for the given email address
     *
     * @param string $email The email address to test
     * @return string The URL of the spam score page
     * @throws \InvalidArgumentException if the email address is invalid
     */
    public function getPageUrl($email)
    {
        $emailId = explode("@", $email)[0];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email address: $email");
        }

        $emailId = urlencode($emailId);

        return "https://www.mail-tester.com/$emailId?lang={$this->lang}&provider={$this->provider}";
    }

    /**
     * Get spam score with full report.
     * Currently support mail-tester and needs only email address. 
     * Basically you send your email to a unique mial-tester address and visit mail-tester for the score.
     * See https://www.mail-tester.com for more details on how it works.
     *
     * @param string $email
     * @return array
     */
    public function getScore($email)
    {
        $url = $this->getPageUrl($email);

        // Create a new WebContentGetter object to crawl the page
        $crawler = new WebContentGetter($url, $this->providerConfig['cacheDir']);

        // Crawl the page and remove unwanted elements
        $crawler->crawlWithSocket(request()->ip(), request()->userAgent(), 'spamscore');
        $html = $crawler->getHtml();
        $selectors = array(
            '/html/body/div[@id="lang_select"]',
            '/html/body/div[@id="share-me"]',
            '/html/body/div[@id="footer"]',
            '//div[@id="separator"]/*/a',
            '/html/body/div[contains(@class,"cc-window")]',
            '//*[@class="back"]',
        );
        $html = $crawler->deleteElementsWithSelectors($html, $selectors);
        $html = $crawler->removeMetaTags($html, ['refresh']);
        $html = trim($crawler->inlineResources($html, true)); //load all linked resource through url into inline.

        // Extract the spam score from the page
        $score = trim($crawler->extractSection($html, '//div[@id="header"][1]/span[contains(@class,"score")][1]/text()'));

        // Create an array with the spam score and full HTML content of the page
        return [
            'score' => $score,
            'full_html' => $html,
            'url' => $url,
        ];
    }
}
