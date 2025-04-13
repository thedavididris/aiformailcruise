<?php

namespace Rubbylab\AiContentToolkit\Services;

use Rubbylab\AiContentToolkit\Utils\WebContentGetter;

/**
 * The SubjectLineScore class provides functionality for checking the score of an email subject line
 * using a third-party provider. It encapsulates the provider setting and provides a method to get
 * the URL of the results page for the given subject line, name, and email address. The class ensures
 * that the inputs are properly encoded for use in a URL to prevent bugs and vulnerabilities.
 */
class SubjectLineScore
{
    /**
     * The provider to use for subject line scoring.
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
     * Creates a new instance of the SubjectLineScore class.
     *
     * @param string $provider The name of the provider to use for subject line scoring.
     */
    public function __construct(array $providerConfig = [])
    {
        $provider = $providerConfig['provider'] ?? 'sendcheckit';
        $this->providerConfig = $providerConfig;

        if ($provider == 'sendcheckit') {
            if (empty($this->providerConfig['name']) || empty($this->providerConfig['email']) || empty($this->providerConfig['cacheDir'])) {
                throw new \InvalidArgumentException("name, email and cacheDir is required for $provider");
            }
        }

        $this->setProvider($provider);
    }

    /**
     * Gets the URL of the results page for the given subject line, name, and email address.
     *
     * @param string $subject The subject line to score.
     * @param string $name The name of the email recipient.
     * @param string $email The email address of the recipient.
     *
     * @return string The URL of the results page for the given subject line, name, and email address.
     */
    public function getPageUrl(string $subject, string $name, string $email): string
    {
        // Sanitize and encode inputs for use in URL
        $subject = urlencode(trim($subject));
        $name = urlencode(trim($name));
        $email = urlencode(trim($email));

        // Construct URL using provider and encoded inputs
        return "https://$this->provider.com/email-subject-line-tester-results?subject=$subject&first_name=$name&email=$email&is_example=false";
    }


    /**
     * Set the subject line score provider
     *
     * @param string $provider The subject line score provider to use
     * @throws InvalidArgumentException if the provider is invalid
     */
    public function setProvider($provider)
    {
        // Ensure that the provider is valid
        $validProviders = ['sendcheckit'];
        if (!in_array($provider, $validProviders)) {
            throw new \InvalidArgumentException("Invalid provider: $provider");
        }

        $this->provider = $provider;
    }

    /**
     * Gets the name of the provider currently being used for subject line scoring.
     *
     * @return string The name of the provider currently being used for subject line scoring.
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Get the subject line score with full report.
     *
     * @param string $subject The subject line to get the report for
     * @return array
     */
    public function getScore(string $subject): array
    {
        // In future, check for provider and determine right means of getting subject score. 
        // At this time, we support only sendcheckit and its straight forward as below

        $url = $this->getPageUrl($subject, $this->providerConfig['name'], $this->providerConfig['email']);

        // Crawl the URL to get the HTML and extract the relevant sections.
        $crawler = new WebContentGetter($url, $this->providerConfig['cacheDir']);
        $crawler->crawl('subjectscore');
        $html = $crawler->getHtml();
        $selectors = array(
            '/html/body/nav',
            '/html/body/div[1]',
            '/html/body/div[3]',
            '//*[@id="dripSignupOverlay"]',
            '/html/body/footer',
            '//div[contains(@class,"masthead")]',
        );

        $html = $crawler->deleteElementsWithSelectors($html, $selectors);

        if (!empty($this->providerConfig['crawler']['css']))
            foreach ($this->providerConfig['crawler']['css'] as $css) {

                $html = $crawler->addInlineCssToHtml($html, $css);
            }

        $html = trim($crawler->inlineResources($html)); //load all linked resource through url into inline.

        // Extract the relevant data from the HTML.
        $previewHtml = $crawler->extractSection($html, '//li[@class="report-grade"][1]/div[1]');
        $grade = trim($crawler->extractSection($previewHtml, '//div[contains(@class,"bubble")]/text()'));
        $grade_html = trim($crawler->extractSection($previewHtml, '//div[contains(@class,"bubble")]'));
        $score = trim($crawler->extractSection($previewHtml, '//div[contains(@class, "points")][1]/strong[1]/text()'));

        // Create an array of data to return as JSON.
        return [
            'subject' => $subject,
            'grade' => $grade,
            'grade_html' => $grade_html,
            'score' => $score,
            'preview_html' => trim($previewHtml),
            'full_html' => $html,
        ];
    }
}