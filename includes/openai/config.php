<?php
class Configuration {
    public $apiKey;

    public function __construct($options) {
        $this->apiKey = $options['apiKey'];
    }
}