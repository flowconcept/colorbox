<?php

/**
 * @file
 * Definition of Drupal\colorbox\EventSubscriber\ColorboxSubscriber.
 */

namespace Drupal\colorbox\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * KernelEvents::REQUEST subscriber for colorbox loading.
 */
class ColorboxSubscriber implements EventSubscriberInterface {

  /**
   * The The module handler used to hook altering.
   *
   * @var Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $module_handler;

  /**
   * Construct the ColorboxSubscriber.
   *
   * @param Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler used to hook altering.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->module_handler = $module_handler;
  }

  /**
   * Loads Colorbox library.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function colorboxLoadLibrary(GetResponseEvent $event) {
    $config = config('colorbox.settings');
    if (!drupal_installation_attempted()) {
      static $already_added = FALSE;
      if ($already_added) {
        return; // Don't add the JavaScript and CSS multiple times.
      }
      if (!_colorbox_active()) {
        return; // Don't add the JavaScript and CSS on specified paths.
      }

      // Insert options and translated strings as javascript settings.
      if ($config->get('custom.activate')) {
        $js_settings = array(
          'transition' => $config->get('custom.transition_type'),
          'speed' => $config->get('custom.transition_speed'),
          'opacity' => $config->get('custom.opacity'),
          'slideshow' => $config->get('custom.slideshow.slideshow') ? TRUE : FALSE,
          'slideshowAuto' => $config->get('custom.slideshow.auto') ? TRUE : FALSE,
          'slideshowSpeed' => $config->get('custom.slideshow.speed'),
          'slideshowStart' => $config->get('custom.slideshow.text_start'),
          'slideshowStop' => $config->get('custom.slideshow.text_stop'),
          'current' => $config->get('custom.text_current'),
          'previous' => $config->get('custom.text_previous'),
          'next' => $config->get('custom.text_next'),
          'close' => $config->get('custom.text_close'),
          'overlayClose' => $config->get('custom.overlayclose') ? TRUE : FALSE,
          'maxWidth' => $config->get('custom.maxwidth'),
          'maxHeight' => $config->get('custom.maxheight'),
          'initialWidth' => $config->get('custom.initialwidth'),
          'initialHeight' => $config->get('custom.initialheight'),
          'fixed' => $config->get('custom.fixed') ? TRUE : FALSE,
          'scrolling' => $config->get('custom.scrolling') ? TRUE : FALSE,
          'mobiledetect' => $config->get('advanced.mobile_detect') ? TRUE : FALSE,
          'mobiledevicewidth' => $config->get('advanced.mobile_device_width'),
        );
      }
      else {
        $js_settings = array(
          'opacity' => '0.85',
          'current' => t('{current} of {total}'),
          'previous' => t('« Prev'),
          'next' => t('Next »'),
          'close' => t('Close'),
          'maxWidth' => '98%',
          'maxHeight' => '98%',
          'fixed' => TRUE,
          'mobiledetect' => $config->get('advanced.mobile_detect') ? TRUE : FALSE,
          'mobiledevicewidth' => $config->get('advanced.mobile_device_width'),
        );
      }

      $path = drupal_get_path('module', 'colorbox');
      $style = $config->get('custom.style');

      // Give other modules the possibility to override Colorbox settings and style.
      $data = &$js_settings;
      $this->module_handler->alter('colorbox_settings', $data, $style);

      drupal_add_js(array('colorbox' => $js_settings), array('type' => 'setting', 'scope' => JS_DEFAULT));

      // Add and initialise the Colorbox plugin.
      $variant = $config->get('advanced.compression_type');
      libraries_load('colorbox', $variant);
      drupal_add_js($path . '/js/colorbox.js');

      // Add JS and CSS based on selected style.
      switch ($style) {
        case 'none':
          break;
        case 'default':
        case 'plain':
        case 'stockholmsyndrome':
          drupal_add_css($path . '/styles/' . $style . '/colorbox_style.css');
          drupal_add_js($path . '/styles/' . $style . '/colorbox_style.js');
          break;
        default:
          drupal_add_css($style . '/colorbox.css');
      }

      if ($config->get('extra.load', 0)) {
        drupal_add_js($path . '/js/colorbox_load.js');
      }

      if ($config->get('extra.inline', 0)) {
        drupal_add_js($path . '/js/colorbox_inline.js');
      }

      $already_added = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('colorboxLoadLibrary', 50);
    return $events;
  }
}
