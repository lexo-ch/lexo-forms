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
	 * @return string Language code (de, en, fr, it)
	 */
	public static function getLanguage(): string
	{
		static $lang = null;

		if ($lang === null) {
			$lang = isset($_SESSION['jez']) ? $_SESSION['jez'] : 'de';
		}

		return $lang;
	}

	/**
	 * Get translated text based on current language
	 *
	 * @param array $translations Array with language keys
	 * @return string Translated text
	 */
	public static function getTranslatedText(array $translations): string
	{
		$lang = self::getLanguage();
		return isset($translations[$lang]) ? $translations[$lang] : reset($translations);
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

		?>
		<div class="form-field form-field-<?php echo esc_attr($field['name']); ?>">
			<label for="<?php echo esc_attr($field_id); ?>">
				<?php echo esc_html($label_text) . $required_star; ?>
			</label>
			<?php
			switch ($field['html_type']) {
				case 'textarea':
					?>
					<textarea name="<?php echo esc_attr($field['name']); ?>" id="<?php echo esc_attr($field_id); ?>" class="send_field" placeholder="<?php echo esc_attr($placeholder_text); ?>" <?php echo $required_attr; ?>></textarea>
					<?php
					break;

				case 'select':
					?>
					<select name="<?php echo esc_attr($field['name']); ?>" id="<?php echo esc_attr($field_id); ?>" class="send_field" <?php echo $required_attr; ?>>
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
					<input type="<?php echo esc_attr($input_type); ?>" name="<?php echo esc_attr($field['name']); ?>" id="<?php echo esc_attr($field_id); ?>" class="send_field" placeholder="<?php echo esc_attr($placeholder_text); ?>" <?php echo $required_attr; ?>>
					<?php
					break;
			}
			?>
		</div>
		<?php
	}
}
