<?php

namespace Rubbylab\AiContentToolkit\Utils;

class ContentGenerator
{
    // Private properties to store the API key, AI platform, and platform configuration
    private $apiKey;
    private $platform;
    private $platformConfig;
    public $cache = false;

    // Constructor method to set the API key, AI platform, and platform configuration
    public function __construct($apiKey, $platform = 'openai', $platformConfig = [])
    {
        $this->platform = $platform;
        $this->apiKey = $apiKey;
        $this->platformConfig = $platformConfig;
    }

    // Public method to generate text using the specified AI platform
    public function generateText($prompt, $context, $length, $numResponses, $options = [])
    {
        // Determine which AI platform to use based on the specified platform type
        if ($this->platform == 'openai') {
            // If using OpenAI, call the generateTextOpenAI method
            return $this->generateTextOpenAI($prompt, $context, $length, $numResponses, $options);
        } else {
            // Throw an exception if an invalid AI platform is specified
            throw new \Exception('Invalid AI platform specified');
        }
    }

    // Private method to generate text using OpenAI
    private function generateTextOpenAI($prompt, $context, $length, $numResponses, $config = [])
    {
        // Get the API key, model type, and temperature from the platform configuration
        $apiKey = $this->apiKey;
        $config = array_merge($this->platformConfig, $config);
        $model = $config['model'] ?? 'text-davinci-002';
        $temperature = $config['temperature'] ?? 1;

        // Set the URL for the OpenAI API endpoint
        $endpoint = 'completions';

        // Set the data payload to send to the OpenAI API
        $data = [
            'model' => $model,
            'prompt' => "Context: $context" . "\nGenerate a text: " . $prompt,
            'temperature' => (float)$temperature,
            'max_tokens' => (int)$length,
            'n' => (int)$numResponses,
        ];

        //set user if provided
        if (!empty($config['user'])) {
            $data['user'] = (string)$config['user'];
        }

        //chat completion models
        $chatMode = $model === 'gpt-3.5-turbo';
        $sessionKey = "cache_$model";
        $chatHistory = !empty($_SESSION[$sessionKey]) ? $_SESSION[$sessionKey] : [];
        if ($chatMode) {

            $endpoint = 'chat/completions';

            $data['messages'] = array_merge($chatHistory, !empty($config['messages']) ? $config['messages'] : [
                ["role" => "user", "content" => $data['prompt']]
            ]);

            $chatHistory = $data['messages'];
            unset($data['prompt']);
        }


        if ($this->cache && !empty($_SESSION[$endpoint])) return $_SESSION[$endpoint];

        // Set the headers for the HTTP request to the OpenAI API
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ];

        // Encode the data payload as JSON
        $dataString = json_encode($data);

        // Create a cURL request to the OpenAI API endpoint
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/$endpoint");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Execute the cURL request and get the response
        $result = curl_exec($ch);

        // Close the cURL connection
        curl_close($ch);

        // Decode the JSON response from the OpenAI API
        $response = json_decode($result);
        if (!empty($response->error->message)) {

            throw new \Exception("OpenAI Error:" . $response->error->message);
        }


        // Parse the text from the response and store it in an array
        $output = array();
        for ($i = 0; $i < $numResponses; $i++) {
            $text = '';
            if (!empty($response->choices)) {
                if (!empty($response->choices[$i]->message)) { //chat completion
                    $message = $response->choices[$i]->message;
                    $chatHistory[] = (array)$message;
                    if ($message->role == 'assistant')
                        $text = $message->content;
                } else {

                    $text = $response->choices[$i]->text;
                }
            }

            $text = str_ireplace(['Subject:', 'Subject Line:'], '', $text);
            $text = trim($text);
            $text = trim($text, "\"");
            if (!empty($text))
                $output[] = $text;
        }

        if ($this->cache) {
            $_SESSION[$endpoint] = $output;
        }

        //cache in session for futurue request
        if ($chatMode) {
            $_SESSION[$sessionKey] = $chatHistory;
        }

        // Return the generated text as an array
        return $output;
    }
}