<?php

/**
 * Text Control
 *
 * @package blank-plugin
 * @since 1.0.0
 */

namespace Blank_Plugin\Inc\Controls;

use WP_Customize_Control;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * A custom text control
 */
class Text extends WP_Customize_Control
{

    /**
     * The control type.
     *
     * @access public
     * @var string
     */
    public $type = 'blank-plugin-text-control';

    /**
     * Gather control props to pass for the React component
     */
    protected function get_props()
    {
        return [
            'setting'       => $this->settings['default']->id,
            'value'         => $this->value(),
            'defaultValue'  => $this->setting->default, // Explicit default value
            'label'         => $this->label,
            'description'   => $this->description,
            'customProp'    => isset($this->custom_prop) ? $this->custom_prop : null,
        ];
    }

    /**
     * Render content is still called, so be sure to override it with an empty function in your subclass as well.
     */
    public function render_content()
    {
        $props = $this->get_props();
?>
        <div id="blank-plugin-text-control-<?php echo esc_attr($this->id); ?>"
            data-props="<?php echo esc_attr(wp_json_encode($props)); ?>">></div>
<?php
    }
}
