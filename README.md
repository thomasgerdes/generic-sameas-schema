# Generic sameAs Schema WordPress Plugin

A WordPress plugin that adds Schema.org `sameAs` markup to connect your website with your social media profiles and professional links.

## What it does

This plugin adds structured data to your website that tells search engines about your various online profiles. This helps search engines understand the connection between your website and your other online presences.

## Installation

1. Download the plugin files
2. Upload the `generic-sameas-schema` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Settings → sameAs Schema to configure

## Configuration

Navigate to Settings → sameAs Schema in your WordPress admin to:

- Choose entity type (Person or Organization)
- Enter your name and website URL
- Add URLs to your social media and professional profiles
- Save settings

## Example Output

The plugin generates JSON-LD structured data:

```json
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "Your Name",
  "url": "https://yourwebsite.com",
  "sameAs": [
    "https://linkedin.com/in/yourprofile",
    "https://github.com/yourusername",
    "https://twitter.com/yourusername"
  ]
}
```

## Compatibility

- WordPress 5.0+
- PHP 7.4+
- Works alongside existing SEO plugins

## License

MIT
