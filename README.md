# Generic sameAs Schema WordPress Plugin

## Overview

This WordPress plugin adds comprehensive Schema.org markup to connect websites with social media profiles and professional links. It generates structured data that helps search engines understand professional relationships, expertise, and online presence. The code was created with AI assistance.

## What it does

The plugin adds rich structured data to websites that tells search engines about:
- Professional details (job title, employer, areas of expertise)
- Educational background (alumni institutions)  
- Alternative name variations
- Social media and professional profiles
- Organizational relationships

This helps search engines understand connections between websites and other online presences, potentially improving visibility in search results and enabling rich snippets or knowledge panels.

## Installation

1. Download the plugin files
2. Upload the `generic-sameas-schema` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Settings → sameAs Schema to configure

## Usage

1. Navigate to **Settings → sameAs Schema** in your WordPress admin
2. Configure the following sections:

### Basic Information
- Choose entity type (Person or Organization)
- Enter primary name and website URL
- Add alternative name variations

### Professional Information  
- Add job title and current employer
- List areas of expertise or specialization
- Include educational institutions (alumni)

### Social Profiles & Professional Links
- Add URLs to social media profiles
- Include professional networks (LinkedIn, ResearchGate, etc.)
- Add academic profiles (Google Scholar, ORCID, etc.)

3. Use the live preview to see generated Schema.org markup
4. Save settings to activate the structured data output

## Example Output

```json
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "Dr. Jane Smith",
  "alternateName": ["Jane Smith", "J. Smith"],
  "url": "https://example.com",
  "jobTitle": "Head of Research",
  "worksFor": {
    "@type": "Organization",
    "name": "University of Example",
    "url": "https://university.example.com"
  },
  "knowsAbout": ["Data Science", "Machine Learning"],
  "alumniOf": ["MIT", "Stanford University"],
  "sameAs": [
    "https://linkedin.com/in/janesmith",
    "https://scholar.google.com/citations?user=...",
    "https://orcid.org/0000-0000-0000-0000"
  ]
}
```

## Profile URL Examples

The plugin works well with various professional and social platforms:

- **Professional Networks**: LinkedIn, Xing
- **Academic Profiles**: Google Scholar, ResearchGate, ORCID, Academia.edu
- **Social Media**: Twitter/X, Bluesky, Mastodon, Instagram
- **Development**: GitHub, GitLab
- **Other**: Personal websites, portfolio sites, company pages

## Compatibility

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **SEO Plugins**: Designed to complement Yoast SEO, RankMath, and other SEO plugins
- **Themes**: Works with any properly coded WordPress theme

## Technical Details

- Outputs Schema.org JSON-LD markup in the `<head>` section
- Uses WordPress best practices for security and performance
- Stores settings in WordPress options table
- Includes proper uninstall cleanup
- No external dependencies

## Contributing

This plugin was created with AI assistance. Contributions, bug reports, and feature requests are welcome. Please open an issue or submit a pull request.

## Changelog

### Version 1.2.0
- Added alternative names support
- Enhanced live schema preview
- Improved professional information fields
- Added multiple alumni institutions support
- Better admin interface organization

### Version 1.1.0
- Added professional details (job title, employer, expertise)
- Added educational background support
- Improved admin interface

### Version 1.0.0
- Initial release
- Basic sameAs functionality
- Social profile linking

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## References

- [Schema.org sameAs](https://schema.org/sameAs)
- [Schema.org Person](https://schema.org/Person)
- [Schema.org Organization](https://schema.org/Organization)
- [WordPress Plugin Development](https://developer.wordpress.org/plugins/)
- [Google Structured Data Guidelines](https://developers.google.com/search/docs/appearance/structured-data)
