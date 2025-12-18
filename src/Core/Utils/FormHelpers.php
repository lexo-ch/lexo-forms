<?php
/**
 * Form Helper Functions
 *
 * Helper functions for rendering form fields with multi-language support.
 *
 * @package LEXO\LF
 * @since 1.0.0
 */

namespace LEXO\LF\Core\Utils;

class FormHelpers
{
	/**
	 * Get current language from session
	 *
	 * Supports centralized language control via filter 'lexo-forms/forms/language'
	 *
	 * @param int|null $form_id Optional form ID for form-specific language control
	 * @return string Language code (de, en, fr, it) - defaults to 'de'
	 */
	public static function getLanguage(?int $form_id = null): string
	{
		static $lang = null;

		if ($lang === null) {
			$lang = isset($_SESSION['jez']) ? $_SESSION['jez'] : 'de';
		}

		/**
		 * Filter the language used for form display
		 *
		 * Allows centralized control of form language. This affects:
		 * - Template names
		 * - Field labels
		 * - Placeholders
		 * - Email labels
		 * - Submit button text
		 *
		 * @param string $lang Current language code (de, en, fr, it)
		 * @param int|null $form_id Form ID if available
		 * @return string Filtered language code
		 *
		 * @since 1.0.0
		 */
		return apply_filters('lexo-forms/forms/language', $lang, $form_id);
	}

	/**
	 * Get translated text based on current language
	 *
	 * @param array $translations Array with language keys
	 * @param int|null $form_id Optional form ID for form-specific language
	 * @return string Translated text
	 */
	public static function getTranslatedText(array $translations, ?int $form_id = null): string
	{
		$lang = self::getLanguage($form_id);
		return isset($translations[$lang]) ? $translations[$lang] : (isset($translations['de']) ? $translations['de'] : reset($translations));
	}

	/**
	 * Get translated template name
	 *
	 * @param string|array $name Template name (string or multilingual array)
	 * @param int|null $form_id Optional form ID for form-specific language
	 * @return string Translated name
	 */
	public static function getTemplateName($name, ?int $form_id = null): string
	{
		// If already a string, return as-is
		if (is_string($name)) {
			return $name;
		}

		// If not an array, return empty string
		if (!is_array($name)) {
			return '';
		}

		// Get translated text using centralized language control
		return self::getTranslatedText($name, $form_id);
	}

	/**
	 * Render form field
	 *
	 * @param array $field Field configuration
	 * @return void
	 */
	public static function renderField(array $field): void
	{
		$field_id = $field['name'];
		$label_text = self::getTranslatedText($field['label']);
		$placeholder_text = self::getTranslatedText($field['placeholder']);
		$required_attr = !empty($field['required']) ? 'required' : '';
		$required_star = !empty($field['required']) ? ' <span class="required">*</span>' : '';
		$placeholder_text = !empty($field['required']) ? $placeholder_text . ' *' : $placeholder_text;

		?>
		<div class="form-field form-field-<?php echo esc_attr($field['name']); ?>">
			<label for="<?php echo esc_attr($field_id); ?>">
				<?php echo esc_html($label_text) . $required_star; ?>
			</label>
			<?php
			switch ($field['html_type']) {
				case 'textarea':
					?>
					<textarea name="<?php echo esc_attr($field['name']); ?>" id="<?php echo esc_attr($field_id); ?>" placeholder="<?php echo esc_attr($placeholder_text); ?>" <?php echo $required_attr; ?>></textarea>
					<?php
					break;

				case 'select':
					?>
					<select name="<?php echo esc_attr($field['name']); ?>" id="<?php echo esc_attr($field_id); ?>" <?php echo $required_attr; ?>>
						<?php if (isset($field['options']) && is_array($field['options'])) { ?>
							<?php foreach ($field['options'] as $option_value => $option_label) { ?>
								<option value="<?php echo esc_attr($option_value); ?>">
									<?php echo esc_html(self::getTranslatedText($option_label)); ?>
								</option>
							<?php } ?>
						<?php } ?>
					</select>
					<?php
					break;

				case 'input':
				default:
					$input_type = isset($field['input_type']) ? $field['input_type'] : 'text';
					?>
					<input type="<?php echo esc_attr($input_type); ?>" name="<?php echo esc_attr($field['name']); ?>" id="<?php echo esc_attr($field_id); ?>" placeholder="<?php echo esc_attr($placeholder_text); ?>" <?php echo $required_attr; ?>>
					<?php
					break;
			}
			?>
		</div>
		<?php
	}
}
