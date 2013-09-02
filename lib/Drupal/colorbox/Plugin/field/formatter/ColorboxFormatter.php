<?php

/**
 * @file
 * Contains \Drupal\colorbox\Plugin\field\formatter\ColorboxFormatter.
 */

namespace Drupal\colorbox\Plugin\field\formatter;

use Drupal\field\Annotation\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the 'colorbox' formatter.
 *
 * @FieldFormatter(
 *   id = "colorbox",
 *   module = "colorbox",
 *   label = @Translation("Colorbox"),
 *   field_types = {
 *     "image"
 *   },
 *   settings = {
 *     "colorbox_node_style" = "",
 *     "colorbox_image_style" = "",
 *     "colorbox_gallery" = "post",
 *     "colorbox_gallery_custom" = "",
 *     "colorbox_caption" = "auto",
 *     "colorbox_caption_custom" = "",
 *     "colorbox_multivalue_index" = NULL
 *   }
 * )
 */
class ColorboxFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $image_styles = image_style_options(FALSE);
    $image_styles_hide = $image_styles;
    $image_styles_hide['hide'] = t('Hide (do not display image)');
    $element['colorbox_node_style'] = array(
      '#title' => t('Content image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('colorbox_node_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles_hide,
      '#description' => t('Image style to use in the content.'),
    );
    $element['colorbox_image_style'] = array(
      '#title' => t('Colorbox image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('colorbox_image_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
      '#description' => t('Image style to use in the Colorbox.'),
    );

    $gallery = array(
      'post' => t('Per post gallery'),
      'page' => t('Per page gallery'),
      'field_post' => t('Per field in post gallery'),
      'field_page' => t('Per field in page gallery'),
      'custom' => t('Custom'),
      'none' => t('No gallery'),
    );
    $element['colorbox_gallery'] = array(
      '#title' => t('Gallery (image grouping)'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('colorbox_gallery'),
      '#options' => $gallery,
      '#description' => t('How Colorbox should group the image galleries.'),
    );
    $element['colorbox_gallery_custom'] = array(
      '#title' => t('Custom gallery'),
      '#type' => 'machine_name',
      '#maxlength' => 32,
      '#default_value' => $this->getSetting('colorbox_gallery_custom'),
      '#description' => t('All images on a page with the same gallery value (rel attribute) will be grouped together. It must only contain lowercase letters, numbers, and underscores.'),
      '#required' => FALSE,
      '#machine_name' => array(
        'exists' => 'colorbox_gallery_exists',
        'error' => t('The custom gallery field must only contain lowercase letters, numbers, and underscores.'),
      ),
      '#states' => array(
        'visible' => array(
          ':input[name$="[settings_edit_form][settings][colorbox_gallery]"]' => array('value' => 'custom'),
        ),
      ),
    );

    $caption = array(
      'auto' =>  t('Automatic'),
      'title' => t('Title text'),
      'alt' => t('Alt text'),
      'node_title' => t('Content title'),
      'custom' => t('Custom (with tokens)'),
      'none' => t('None'),
    );
    $element['colorbox_caption'] = array(
      '#title' => t('Caption'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('colorbox_caption'),
      '#options' => $caption,
      '#description' => t('Automatic will use the first none empty value of the title, the alt text and the content title.'),
    );
    $element['colorbox_caption_custom'] = array(
      '#title' => t('Custom caption'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('colorbox_caption_custom'),
      '#states' => array(
        'visible' => array(
          ':input[name$="[settings_edit_form][settings][colorbox_caption]"]' => array('value' => 'custom'),
        ),
      ),
    );
    $element['colorbox_token'] = array(
      '#type' => 'fieldset',
      '#title' => t('Replacement patterns'),
      '#description' => '<strong class="error">' . t('For token support the <a href="@token_url">token module</a> must be installed.', array('@token_url' => 'http://drupal.org/project/token')) . '</strong>',
      '#states' => array(
        'visible' => array(
          ':input[name$="[settings_edit_form][settings][colorbox_caption]"]' => array('value' => 'custom'),
        ),
      ),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    if (isset($image_styles[$this->getSetting('colorbox_node_style')])) {
      $summary[] = t('Content image style: @style', array('@style' => $image_styles[$this->getSetting('colorbox_node_style')]));
    }
    elseif ($this->getSetting('colorbox_node_style') == 'hide') {
      $summary[] = t('Content image style: Hide');
    }
    else {
      $summary[] = t('Content image style: Original image');
    }

    if (isset($image_styles[$this->getSetting('colorbox_image_style')])) {
      $summary[] = t('Colorbox image style: @style', array('@style' => $image_styles[$this->getSetting('colorbox_image_style')]));
    }
    else {
      $summary[] = t('Colorbox image style: Original image');
    }

    $gallery = array(
      'post' => t('Per post gallery'),
      'page' => t('Per page gallery'),
      'field_post' => t('Per field in post gallery'),
      'field_page' => t('Per field in page gallery'),
      'custom' => t('Custom'),
      'none' => t('No gallery'),
    );
    if ($this->getSetting('colorbox_gallery')) {
      $summary[] = t('Colorbox gallery type: @type', array('@type' => $gallery[$this->getSetting('colorbox_gallery')])) . ($this->getSetting('colorbox_gallery') == 'custom' ? ' (' . $this->getSetting('colorbox_gallery_custom') . ')' : '');
    }

    $caption = array(
      'auto' =>  t('Automatic'),
      'title' => t('Title text'),
      'alt' => t('Alt text'),
      'node_title' => t('Content title'),
      'custom' => t('Custom (with tokens)'),
      'none' => t('None'),
    );

    if ($this->getSetting('colorbox_caption')) {
      $summary[] = t('Colorbox caption: @type', array('@type' => $caption[$this->getSetting('colorbox_caption')]));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $element = array();
    $index = $this->getSetting('colorbox_multivalue_index');

    foreach ($items as $delta => $item) {
      if ($index === NULL || $index === '' || $index === $delta) {
        $element[$delta] = array(
          '#theme' => 'colorbox_image_formatter',
          '#item' => $item,
          '#entity_type' => $entity->getType(),
          '#entity' => $entity,
          '#node' => $entity, // Left for legacy support.
          '#field' => $this->fieldDefinition,
          '#display_settings' => $this->getSettings(),
        );
      }
    }

    return $element;
  }

}
