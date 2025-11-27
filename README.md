# NomadsGuru - Travel Deals Aggregator

NomadsGuru is an AI-powered WordPress plugin that aggregates, evaluates, and publishes travel deals from various sources.

## Features

- **Multi-Source Aggregation:** Fetches deals from Skyscanner (more coming soon).
- **AI Evaluation:** Scores deals based on price, destination, and value using AI.
- **Content Generation:** Automatically writes SEO-friendly articles for high-scoring deals.
- **Image Enrichment:** Finds and attaches royalty-free images to posts.
- **Affiliate Monetization:** Automatically converts links to affiliate URLs.
- **Flexible Publishing:** Choose between automatic publishing or manual review (Drafts).
- **Frontend Display:** Display deals via Gutenberg Block or Shortcode `[nomadsguru_deals]`.

## Installation

1. Upload the `nomadsguru` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Run `composer install` within the plugin directory to install dependencies.

## Configuration

1. Go to **NomadsGuru -> Settings**.
2. Configure the **Publishing Mode** (Automatic or Manual).
3. Go to **NomadsGuru -> Deal Sources** to manage active sources.

## Developer Notes

- **Tests:** Run `vendor/bin/phpunit` to execute unit tests.
- **Architecture:** The plugin follows a modular architecture with separate layers for Core, Services, Processors, and Integrations.

## License

GPL-2.0-or-later
