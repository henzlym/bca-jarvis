<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) exit;

if (!class_exists('Jarvis_Admin_Callbacks')) {
    class Jarvis_Admin_Callbacks
    {
        public $options;
        
        public function __construct()
        {
            $this->options = get_option( 'jarvis_options' );
            do_action( 'qm/debug', $this->options );

        }
        
        public function page_section($args)
        {
            echo '<hr />';
        }
        public function input_field($args)
        {

			$value = (isset($this->options[$args['name']])) ? $this->options[$args['name']] : $args['default'];

            if ( isset($args['name']) && $args['option_group'] !== $args['name'] ) {
                $name = isset($args['name']) ? $args['option_group'] . '[' . $args['name'] . ']' : false;
            } else {
                $name = $args['name'];
            }
            $type = isset($args['type']) ? $args['type'] : 'text';
            $disabled = isset($args['disabled']) && $args['disabled'] ? 'disabled="disabled"' : false;

            if (!$name) return null;

            $attributes = '';
            $attributes_args = array();
            if ($type == 'checkbox') {
                $check = $value;
				$value = true;
                $attributes_args[] = checked($check, true, false);
                $attributes .= implode(' ', $attributes_args);
            }
            echo '<input type="' . $type . '" id="' . $name . '" name="' . $name . '" value="' . $value . '" ' . $attributes . $disabled .'/>';
        }
        public function submit_button($args)
        {
            $option = isset($args['option_group']) ? get_option($args['option_group']) : false;
            if ( isset($args['name']) && $args['option_group'] !== $args['name'] ) {
                $value = (isset($option[$args['name']])) ? $option[$args['name']] : $args['default'];
                $name = isset($args['name']) ? $args['option_group'] . '[' . $args['name'] . ']' : false;
                $type = isset($args['type']) ? $args['type'] : 'text';
            } else {
                $value = ( $option ) ? $option : $args['default'];
                $name = $args['name'];
                $type = isset($args['type']) ? $args['type'] : 'text';
            }
            $title = isset($args['title']) ? $args['title'] : '';
			$class = isset($args['button_class']) ? $args['button_class'] : '';
            if (!$name) return null;

            $attributes = '';
            $attributes_args = array();
            if ($type == 'checkbox') {
                $attributes_args[] = checked($value, true, false);
                $attributes .= implode(' ', $attributes_args);
            }
            submit_button( $title, $class, $args['name'] );
        }
    }

}