<?php

/**
 * @file
 * Contains rma_item.page.inc.
 *
 * Page callback for RMA item entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for RMA item templates.
 *
 * Default template: rma_item.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_rma_item(array &$variables) {
  // Fetch RMAItem Entity Object.
  $rma_item = $variables['elements']['#rma_item'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
