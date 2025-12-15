# EC Checkout Breadcrumbs

A Drupal module that provides enhanced breadcrumb navigation for Drupal Commerce checkout process, displaying checkout steps as interactive breadcrumb links.

## Overview

This module transforms the standard breadcrumb navigation on checkout pages into a step-by-step progress indicator. Each checkout step appears in the breadcrumb trail, with completed steps shown as clickable links, the current step highlighted, and upcoming steps displayed as inactive text.

## The Problem It Solves

Drupal Commerce's default breadcrumb implementation on checkout pages doesn't properly represent the checkout flow:
- Breadcrumb titles and links are often incorrect or misleading
- No clear indication of checkout progress
- Difficult to customize without extensive overrides

This module provides a clean, plugin-based solution that can be easily enabled or disabled per checkout flow without modifying core functionality.

## Features

- **Step-based breadcrumbs**: Displays all visible checkout steps in the breadcrumb trail
- **Interactive navigation**: Previously completed steps are clickable links, allowing users to navigate back
- **Progress indication**: Current step is visually highlighted, future steps are dimmed
- **Complete order handling**: When an order reaches "complete" status, previous steps become non-clickable text
- **Easy configuration**: Enable/disable per checkout flow via admin interface
- **Highly customizable**: Extend via CSS classes, Drupal templates, or class inheritance
- **Cache-aware**: Properly integrates with Drupal's cache system

## Requirements

- Drupal: ^10 || ^11
- Drupal Commerce Cart module
- Drupal Commerce Checkout module

## Installation

1. Download or clone this module to your Drupal installation's `modules/custom` or `modules/contrib` directory

2. Enable the module:
   ```bash
   drush en ec_checkout_breadcrumbs
   ```

   Or via the Drupal admin interface: `Administration > Extend`

3. Clear cache:
   ```bash
   drush cr
   ```

## Configuration

1. Navigate to your checkout flow settings:
   ```
   Administration > Commerce > Configuration > Checkout flows
   ```

2. Edit your desired checkout flow (e.g., "Default")

3. Enable the option:
   **"Display checkout progress breadcrumb as links"**

4. Save the configuration

The breadcrumb builder will now apply to that checkout flow with a high priority (2000), ensuring it takes precedence over the default breadcrumb builder.

## CSS Classes

The module assigns specific CSS classes to each breadcrumb item based on its position in the checkout flow:

### Classes

- **`breadcrumb-checkout-item-previous`**: Applied to completed steps (when order is not complete)
  - These steps are clickable links

- **`breadcrumb-checkout-item-current`**: Applied to the current active step
  - Styled as bold text by default

- **`breadcrumb-checkout-item-next`**: Applied to upcoming steps
  - Styled with reduced opacity (0.5) by default

- **`breadcrumb-checkout-item-complete`**: Applied to previous steps when order status is "complete"
  - These steps are non-clickable text

### Default Styling

The module includes basic CSS in `css/style.css`:

```css
.breadcrumb .breadcrumb-checkout-item-current {
  font-weight: bold;
  color: #000000;
}

.breadcrumb .breadcrumb-checkout-item-next {
  opacity: 0.5;
}
```

## Customization

### Override CSS Styles

Override the default styles in your theme's CSS file:

```css
.breadcrumb .breadcrumb-checkout-item-previous {
  color: #0066cc;
  text-decoration: underline;
}

.breadcrumb .breadcrumb-checkout-item-current {
  font-weight: bold;
  color: #ff6600;
  background: #fff3cd;
  padding: 2px 8px;
  border-radius: 3px;
}

.breadcrumb .breadcrumb-checkout-item-next {
  opacity: 0.4;
  color: #999;
}
```

### Override Templates

Use Drupal's template system to customize the breadcrumb output:

1. **Override the entire breadcrumb**: Copy `breadcrumb.html.twig` from core to your theme
2. **Preprocess functions**: Add preprocess hooks in your theme's `.theme` file

Example preprocess function:

```php
function MYTHEME_preprocess_breadcrumb(&$variables) {
  if (isset($variables['breadcrumb'])) {
    foreach ($variables['breadcrumb'] as &$item) {
      // Add custom logic here
    }
  }
}
```

### Extend the Builder Class

For more advanced customization, extend the `CheckoutBreadcrumbBuilder` class:

```php
<?php

namespace Drupal\my_custom_module\Breadcrumb;

use Drupal\ec_checkout_breadcrumbs\Breadcrumb\CheckoutBreadcrumbBuilder;
use Drupal\Core\Url;
use Drupal\Core\Link;

class CustomCheckoutBreadcrumbBuilder extends CheckoutBreadcrumbBuilder {

  /**
   * Override to add custom CSS classes.
   */
  protected function getLink(Url $url, string $label, string $position): Link {
    $classes = ['breadcrumb-checkout-item-' . $position];

    // Add your custom classes
    if ($position === 'current') {
      $classes[] = 'custom-active-step';
    }

    $url->setOption('attributes', [
      'class' => $classes,
    ]);

    return Link::fromTextAndUrl($label, $url);
  }

}
```

Then register your custom builder in `my_custom_module.services.yml` with a higher priority:

```yaml
services:
  my_custom_module.custom_breadcrumb_builder:
    class: Drupal\my_custom_module\Breadcrumb\CustomCheckoutBreadcrumbBuilder
    arguments:
      - '@commerce_checkout.checkout_order_manager'
    tags:
      - { name: breadcrumb_builder, priority: 2100 }
```

## How It Works

1. The module registers a breadcrumb builder service with priority 2000
2. On checkout pages, it checks if the "Display checkout progress breadcrumb as links" option is enabled
3. If enabled, it builds a breadcrumb containing:
  - Home link
  - Shopping cart link
  - All visible checkout steps with appropriate states and links
4. The breadcrumb builder respects hidden steps and only shows them when reached
5. Cache tags ensure the breadcrumb updates when the order or checkout flow changes

## Disabling the Module

If you no longer need this functionality:

1. Go to your checkout flow configuration
2. Uncheck "Display checkout progress breadcrumb as links"
3. Save the configuration

The default Commerce breadcrumb will be restored immediately. You can then uninstall the module if desired.

## Support and Contribution

This is a custom module. For issues or feature requests, please contact your development team or module maintainer.

## License

This module follows the same license as Drupal core (GPL v2 or later).

## Credits

Developed for Drupal Commerce 3.x and Drupal 10/11.

## Author

Pavel Kasianov.

Linkedin: https://www.linkedin.com/in/pkasianov/</br>
Drupal org: https://www.drupal.org/u/pkasianov
