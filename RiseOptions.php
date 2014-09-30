<?php
/**
 * RiseOptions - Live Theme Customizer for WordPress
 * @author Rise Ledger
 * @website http://riseledger.com
 *
 * Date: 25.08.2014
 * Release Under GPL3 License
 */

class RiseOptions {

	private static $wp_customize;
	private static $prefix         = 'rl_';
	private static $sections       = array();
	private static $fields         = array();
	private static $priority       = 200;
	private static $transport      = 'refresh';
	private static $supportedTypes = array();
	private static $baseTypes      = array('text', 'checkbox', 'radio', 'select', 'pages');
	private static $advanceTypes   = array('image', 'color', 'upload');
	private static $customTypes    = array('textarea', 'number', 'date');
	private static $toRemove       = array();

	public static function init ( $wp_customize ) {
		self::$wp_customize = $wp_customize;
		self::registerSections();
		self::registerFields();
		self::removeOptions();
	}

	public static function __callStatic($name, $args) {
		self::$supportedTypes = self::getSupportedTypes();
		if(!in_array($name, self::$supportedTypes)) return;

		if($name === 'pages') {
			$name = 'dropdown-pages';
		}

		self::addField($name, $args);
	}

	private static function registerSections() {
		foreach (self::$sections as $section) {
			self::$wp_customize->add_section( $section->id , array(
				'title'       => $section->name,
				'priority'    => $section->priority,
				'description' => $section->desc
			) );
		}
	}

	private static function registerFields() {
		foreach (self::$fields as $field) {
			$opts = array(
				'label'    => $field->label,
				'type'     => $field->type,
				'section'  => $field->section,
				'settings' => $field->id,
				'choices'  => $field->choices
			);

			self::$wp_customize->add_setting($field->id, array(
				'default'   => $field->default,
				'transport' => self::$transport
			));

			if(in_array($field->type, self::$advanceTypes)) {
				$className = 'WP_Customize_' . ucfirst($field->type) . '_Control';
				unset($opts['type']);

				self::$wp_customize->add_control(
					new $className(self::$wp_customize, $field->id, $opts)
				);
			}
			elseif(in_array($field->type, self::$customTypes)) {
				self::$wp_customize->add_control(
					new RiseOptionsCustomControl(self::$wp_customize, $field->id, $opts)
				);
			}
			else {
				self::$wp_customize->add_control($field->id, $opts);
			}
		}
	}

	private static function addField($type, $args) {
		$field = new stdClass();
		$field->id      = self::$prefix . $args[0];
		$field->type    = $type;
		$field->label   = @$args[1];
		$field->default = @$args[2];
		$field->section = end(self::$sections)->id;
		$field->choices = @$args[3];

		self::$fields[] = $field;
	}

	private static function removeOptions() {
		foreach (self::$toRemove as $id) {
			self::$wp_customize->remove_section($id);
		}
	}

	// helpers
	private static function toID($name) {
		return strtolower( str_replace(" ", "_", $name) );
	}

	public static function section($name, $desc = null) {
		$section           = new stdClass();
		$section->id       = self::toID($name);
		$section->name     = $name;
		$section->desc     = $desc;
		$section->priority = self::$priority;

		self::$sections[] = $section;
		self::$priority++;
	}

	public static function removeSection($id) {
		self::$toRemove[] = $id;
	}

	public static function get($id, $default = null) {
		return (!is_null($default)) ? get_theme_mod(self::$prefix . $id, $default) : get_theme_mod(self::$prefix . $id);
	}

	public static function getSupportedTypes() {
		return array_merge(self::$baseTypes, self::$advanceTypes, self::$customTypes);
	}
}

add_action( 'customize_register' , array('RiseOptions', 'init') );

if(!class_exists('WP_Customize_Control')) return;
class RiseOptionsCustomControl extends WP_Customize_Control {
	public function render_content() {
		$funcName = $this->type . 'HTML';
		$this->$funcName();
	}

	private function textareaHTML() {
		?>
		<label>
            <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
            <textarea rows="5" style="width:100%;" <?php $this->link(); ?>><?php echo esc_textarea( $this->value() ); ?></textarea>
        </label>
		<?php
	}

	private function numberHTML() {
		?>
		<label>
            <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
            <input type="number" step="1" min="0" value="<?php echo esc_attr(  $this->value() ); ?>">
        </label>
		<?php
	}

	private function dateHTML() {
		?>
		<label>
            <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
            <input type="date" value="<?php echo esc_attr(  $this->value() ); ?>">
        </label>
		<?php
	}
}