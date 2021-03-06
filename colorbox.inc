<?php

/**
 * @file
 * Colorbox theme functions.
 */

use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\Unicode;

/**
 * Prepares variables for colorbox formatter templates.
 *
 * Default template: colorbox-formatter.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - item: An ImageItem object.
 *   - item_attributes: An optional associative array of html attributes to be
 *     placed in the img tag.
 *   - entity: An entity object.
 *   - settings: Formatter settings array.
 */
function template_preprocess_colorbox_formatter(&$variables) {
  $item = $variables['item'];
  $item_attributes = isset($variables['item_attributes']) ? $variables['item_attributes'] : array();

  $entity = $variables['entity'];
  $settings = $variables['settings'];
  $variables = array(); // Reset the $variables parameter.

  // Build the caption.
  $entity_title = $entity->label();
  $entity_type = $entity->bundle();

  switch ($settings['colorbox_caption']) {
     case 'auto':
      // If the title is empty use alt or the entity title in that order.
      if (!empty($item->title)) {
        $caption = $item->title;
      }
      elseif (!empty($item->alt)) {
        $caption = $item->alt;
      }
      elseif (!empty($entity_title)) {
        $caption = $entity_title;
      }
      else {
        $caption = '';
      }
      break;
    case 'title':
      $caption = $item->title;
      break;
    case 'alt':
      $caption = $item->alt;
      break;
    case 'entity_title':
      $caption = $entity_title;
      break;
    case 'custom':
      $token_service = \Drupal::token();
      $caption = $token_service->replace($settings['colorbox_caption_custom'], array($entity_type => $entity, 'file' => $item), array('clear' => TRUE));
      break;
    default:
      $caption = '';
  }

  // Shorten the caption for the example styles or when caption shortening is active.
  $config = \Drupal::config('colorbox.settings');
  $colorbox_style = $config->get('colorbox_style');
  $trim_length = $config->get('colorbox_caption_trim_length');
  if (((strpos($colorbox_style, 'colorbox/example') !== FALSE) || $config->get('colorbox_caption_trim')) && (Unicode::strlen($caption) > $trim_length)) {
    $caption = drupal_substr($caption, 0, $trim_length - 5) . '...';
  }

  // Build the gallery id.
  $id = $entity->id();
  $entity_id = !empty($id) ? $entity_type . '-' . $id : 'entity-id';
  $field_name = $item->getParent()->getName();

  switch ($settings['colorbox_gallery']) {
    case 'post':
      $gallery_id = 'gallery-' . $entity_id;
      break;
    case 'page':
      $gallery_id = 'gallery-all';
      break;
    case 'field_post':
      $gallery_id = 'gallery-' . $entity_id . '-' . $field_name;
      break;
    case 'field_page':
      $gallery_id = 'gallery-' . $field_name;
      break;
    case 'custom':
      $token_service = \Drupal::token();
      $gallery_id = $token_service->replace($settings['colorbox_gallery_custom'], array($entity_type => $entity, 'file' => $item), array('clear' => TRUE));
      break;
    default:
      $gallery_id = '';
  }

  // Set up the $variables['image'] parameter.
  if ($settings['style_first']) {
    $variables['image'] = array(
      '#theme' => 'image_style',
      '#style_name' => $settings['colorbox_node_style_first'],
    );
  }
  elseif (!empty($settings['colorbox_node_style'])) {
    $variables['image'] = array(
      '#theme' => 'image_style',
      '#style_name' => $settings['colorbox_node_style'],
    );
  }
  else {
    $variables['image'] = array(
      '#theme' => 'image',
    );
  }
  $variables['image']['#attributes'] = $item_attributes;

  // Do not output an empty 'title' attribute.
  if (Unicode::strlen($item->title) != 0) {
    $variables['image']['#title'] = $item->title;
  }

  foreach (array('width', 'height', 'alt') as $key) {
    $variables['image']["#$key"] = $item->$key;
  }

  $image_uri = $item->entity->getFileUri();
  $variables['image']['#uri'] = empty($item->uri) ? $image_uri : $item->uri;

  if (!empty($settings['colorbox_image_style'])) {
    $style = entity_load('image_style', $settings['colorbox_image_style']);
    $variables['url'] = $style->buildUrl($image_uri);
  }
  else {
    $variables['url'] = file_create_url($image_uri);
  }

  $class = array('colorbox');
  if ($settings['colorbox_node_style'] == 'hide') {
    $class[] = 'js-hide';
  }
  $variables['attributes'] = new Attribute();
  $variables['attributes']['title'] = $caption;
  $variables['attributes']['rel'] = $gallery_id;
  $variables['attributes']['class'] = $class;
}
