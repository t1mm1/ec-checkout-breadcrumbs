<?php

namespace Drupal\ec_checkout_breadcrumbs\Breadcrumb;

use Drupal\commerce_checkout\CheckoutOrderManagerInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Provides a breadcrumb builder for checkout pages.
 */
class CheckoutBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The checkout order manager.
   *
   * @var CheckoutOrderManagerInterface
   */
  protected CheckoutOrderManagerInterface $checkoutOrderManager;

  /**
   * Constructs a new CheckoutBreadcrumbBuilder.
   *
   * @param CheckoutOrderManagerInterface $checkout_order_manager
   *   The checkout order manager.
   */
  public function __construct(
    CheckoutOrderManagerInterface $checkout_order_manager
  ) {
    $this->checkoutOrderManager = $checkout_order_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match): bool {
    // Quick check: is this a checkout route?
    if ($route_match->getRouteName() !== 'commerce_checkout.form') {
      return FALSE;
    }

    // Get order from route.
    $order = $route_match->getParameter('commerce_order');
    if (!$order) {
      return FALSE;
    }

    // Get checkout flow.
    $checkout_flow = $this->checkoutOrderManager->getCheckoutFlow($order);
    if (!$checkout_flow) {
      return FALSE;
    }

    // Check if breadcrumb links are enabled in configuration.
    // This check must be in applies() to allow default breadcrumb builder
    // to work when this feature is disabled.
    $configuration = $checkout_flow->getPlugin()->getConfiguration();
    if (empty($configuration['display_checkout_progress_breadcrumb_links'])) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match): Breadcrumb {
    // Get order (validated in applies()).
    $order = $route_match->getParameter('commerce_order');

    // Get checkout flow (validated in applies()).
    $checkout_flow = $this->checkoutOrderManager->getCheckoutFlow($order);

    // Get visible steps.
    $visible_steps = $checkout_flow->getPlugin()->getVisibleSteps();

    // Create breadcrumb object.
    $breadcrumb = $this->getBreadcrumb();

    // Add order and checkout flow as cacheable dependency.
    $breadcrumb->addCacheableDependency($order);
    $breadcrumb->addCacheableDependency($checkout_flow);

    // Build links array.
    $links = $this->getLinks();

    // Get current step ID using checkout order manager (same as block).
    $requested_step_id = $route_match->getParameter('step');
    $current_step_id = $this->checkoutOrderManager->getCheckoutStepId($order, $requested_step_id);

    // Get current step index.
    $step_ids = array_keys($visible_steps);
    $current_step_index = array_search($current_step_id, $step_ids);

    // If step not found, return breadcrumb with just Home and Cart.
    if ($current_step_index === FALSE) {
      $breadcrumb->setLinks($links);
      return $breadcrumb;
    }

    // Build checkout breadcrumb steps.
    $index = 0;
    foreach ($visible_steps as $step_id => $step_definition) {
      $position = $this->getPosition($index, $current_step_index);
      $index++;

      // Hide hidden steps until they are reached.
      if (!empty($step_definition['hidden']) && $position !== 'current') {
        continue;
      }

      // Add previous steps.
      switch ($position) {
        case 'previous':
          // If order is completed, show previous steps as text (no links).
          // Because clicking them will redirect back to complete page anyway.
          if ($current_step_id === 'complete') {
            $links[] = $this->getLink(Url::fromRoute('<nolink>'), $step_definition['label'], $current_step_id);
          }
          else {
            // Order not completed yet, show as clickable links.
            $links[] = $this->getLink(Url::fromRoute('commerce_checkout.form', [
              'commerce_order' => $order->id(),
              'step' => $step_id,
            ]), $step_definition['label'], $position);
          }
          break;

        default:
          $links[] = $this->getLink(Url::fromRoute('<nolink>'), $step_definition['label'], $position);
          break;
      }
    }

    $breadcrumb->setLinks($links);
    return $breadcrumb;
  }

  /**
   * Help function for create basic links.
   *
   * @return array
   */
  function getLinks(): array {
    return [
      Link::createFromRoute($this->t('Home'), '<front>'),
      Link::createFromRoute($this->t('Shopping cart'), 'commerce_cart.page'),
    ];
  }

  /**
   * Help function for create basic breadcrumb.
   *
   * @return Breadcrumb
   */
  function getBreadcrumb(): Breadcrumb {
    // Create breadcrumb object.
    $breadcrumb = new Breadcrumb();

    // Add cache contexts.
    $breadcrumb->addCacheContexts([
      'route',
      'url.path',
      'url.query_args',
      'user',
    ]);

    return $breadcrumb;
  }

  /**
   * Help function for get position of link.
   *
   * @param string $index
   * @param string $current
   *
   * @return string
   */
  function getPosition(string $index, string $current): string {
    // Determine position.
    if ($index < $current) {
      $position = 'previous';
    }
    elseif ($index == $current) {
      $position = 'current';
    }
    else {
      $position = 'next';
    }

    return $position;
  }

  /**
   * Help function for create link.
   *
   * @param Url $url
   * @param string $label
   * @param string $position
   *
   * @return Link
   */
  function getLink(Url $url, string $label, string $position): Link {
    $url->setOption('attributes', [
      'class' => ['breadcrumb-checkout-item-' . $position],
    ]);

    return Link::fromTextAndUrl($label, $url);
  }

}
