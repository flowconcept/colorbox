<?php

/**
 * @file
 * Colorbox theme functions.
 */

/**
 * Returns HTML for an Colorbox image field formatter.
 *
 * @param $variables
 *   An associative array containing:
 *   - item: An array of image data.
 *   - image_style: An optional image style.
 *   - path: An array containing the link 'path' and link 'options'.
 *
 * @ingroup themeable
 */
function theme_colorbox_formatter($variables) {
  $item = $variables['item'];
  $entity = $variables['entity'];
  $settings = $variables['settings'];
  $entity_title = $entity->label();
  $id = $entity->id();
  $entity_type = $entity->bundle();
  $entity_id = !empty($id) ? $entity_type . '-' . $id : 'entity-id';
  $field_name = $item->getParent()->getName();

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
  if (((strpos($colorbox_style, 'colorbox/example') !== FALSE) || $config->get('colorbox_caption_trim')) && (drupal_strlen($caption) > $trim_length)) {
    $caption = drupal_substr($caption, 0, $trim_length - 5) . '...';
  }

  // Build the gallery id.
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

  $img_uri = $item->entity->getFileUri();
  if (!empty($settings['colorbox_image_style'])) {
    $style = entity_load('image_style', $settings['colorbox_image_style']);
    $path = $style->buildUrl($img_uri);
  }
  else {
    $path = file_create_url($img_uri);
  }

  // Extract field item attributes for the theme function, and unset them
  // from the $item so that the field template does not re-render them.
  $item_attributes = $item->_attributes;
  unset($item->_attributes);

  $class = array('colorbox');

  if ($settings['colorbox_node_style'] == 'hide') {
    $item = NULL;
    $class[] = 'js-hide';
  }

  $link_attributes = array(
    'attributes' => array(
      'title' => $caption,
      'class' => $class,
      'rel' => $gallery_id,
    ),
  );
  $link = array('path' => $path, 'options' => $link_attributes);

  $build = array(
    '#theme' => 'image_formatter',
    '#item' => $item,
    '#item_attributes' => $item_attributes,
    '#image_style' => $settings['colorbox_node_style'],
    '#path' => $link,
  );

  return drupal_render($build);
}
