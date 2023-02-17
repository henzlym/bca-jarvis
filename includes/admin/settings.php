<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) exit;

if (!class_exists('Jarvis_Settings')) {
    class Jarvis_Settings
    {

		public $Jarvis_Admin_Callbacks;
        public $settings;
        public $sections;
        public $fields;
        
        public function __construct()
        {
			$this->Jarvis_Admin_Callbacks = new Jarvis_Admin_Callbacks;
            $this->init_settings();
            add_action('init', array($this, 'init'));
        }
        public function init()
        {
            // register fields
            add_action('admin_init', array($this, 'register_settings_fields'));
        }
        public function init_settings()
        {
			$this->settings = array(
                array(
                    'option_group' => 'jarvis',
                    'option_name' => 'jarvis_options',
                    'page' => 'jarvis',
                )
            );

            $this->sections = array(
                array(
                    'id' => 'authorization',
                    'title' => 'Authorization',
                    'callback' => array($this->Jarvis_Admin_Callbacks, 'page_section'),
                    'page' => 'jarvis'
                )
            );

            $this->fields = array(
                array(
                    'id' => 'api_key',
                    'title' => 'API Key',
                    'callback' => array($this->Jarvis_Admin_Callbacks, 'input_field'),
                    'page' => 'jarvis',
                    'section' => 'authorization',
                    'args' => array(
                        'name' => 'api_key',
                        'label_for' => 'api_key',
                        'title' => 'API Key',
                        'class' => 'marketplace',
                        'description' => '',
                        'default' => '',
                        'type' => 'text',
                        'disabled' => false,
                        'option_group' => 'jarvis_options'
                    )
                )
            );
        }
        public function register_settings_fields()
        {

            if (is_array($this->settings) && !empty($this->settings)) {
                foreach ($this->settings as $key => $setting) {
                    register_setting(
                        $setting['option_group'],
                        $setting['option_name'],
                        isset( $setting['args'] ) ? $setting['args'] : array()
                    );
                }
            }
            if (is_array($this->sections) && !empty($this->sections)) {
                foreach ($this->sections as $key => $section) {
                    add_settings_section(
                        $section['id'],
                        __($section['title']),
                        $section['callback'],
                        $section['page']
                    );
                }
            }
            if (is_array($this->fields) && !empty($this->fields)) {
                foreach ($this->fields as $key => $field) {

                    add_settings_field(
                        $field['id'],
                        __($field['title']),
                        $field['callback'],
                        $field['page'], // add to this fields
                        $field['section'], // add to this section
                        $field['args']
                    );
                }
            }
        }
    }

    new Jarvis_Settings;

}