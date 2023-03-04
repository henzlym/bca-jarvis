<?php

/**
 * OpenAI class for making API requests to OpenAI's GPT-3 completion API.
 *
 * @since 1.0.0
 */
class OpenAI
{
    /**
     * Configuration options for the OpenAI API client.
     *
     * @since 1.0.0
     * @var array $config
     */
    private $config;

    /**
     * Constructor for the OpenAI class.
     *
     * @since 1.0.0
     * @param array $config Configuration options for the OpenAI API client.
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Makes a request to the OpenAI API to generate text completions.
     *
     * @since 1.0.0
     * @param array $options Options for the completion request.
     * @return array JSON decoded response from the OpenAI API.
     */
    public function create_completion($options)
    {
        $url = "https://api.openai.com/v1/completions";
        $data = array(
            "prompt" => $options['prompt'],
            "temperature" => $options['temperature'],
            'model' => $options['model'],
            'max_tokens' => $options['max_tokens'],
            'top_p' => $options['top_p'],
            'frequency_penalty' => $options['frequency_penalty'],
            'presence_penalty' => $options['presence_penalty']
        );

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer {$this->config->apiKey}",
            "Content-Type: application/json"
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }
}
