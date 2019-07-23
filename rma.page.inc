<?php

/**
 * @file
 * Contains rma.page.inc.
 *
 * Page callback for RMA entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for RMA templates.
 *
 * Default template: rma.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_rma(array &$variables) {
  // Fetch RMA Entity Object.
  $rma = $variables['elements']['#rma'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
