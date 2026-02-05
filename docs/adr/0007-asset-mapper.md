# ADR 7: AssetMapper

**Date:** 2026-02-05
**Status:** Accepted

## Decision

Use Symfony AssetMapper for managing frontend assets.

## Context

We needed a simple asset management solution that:
- Works without complex build tools (Webpack, Vite)
- Provides hot reload in development
- Works seamlessly with FrankenPHP

## Consequences

### Positive

- **Zero Build Configuration**: Works out of the box with PHP
- **Hot Reload**: Development mode auto-refreshes assets
- **Versioning**: Automatic asset versioning via content hash
- **Import Maps**: Native browser ES module support

### Negative

- **Limited Transpilation**: Not suitable for complex JS frameworks
- **Browser Support**: Requires modern browsers with Import Maps

## Usage

### Basic Asset

```php
// templates/base.html.twig
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="{{ asset('styles/app.css') }}">
</head>
<body>
    {{ include('@App/scripts/app.js') }}
</body>
</html>
```

### Import Maps

```javascript
// assets/app.js
import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';

console.log('App loaded');
```

## References

- [Symfony AssetMapper Documentation](https://symfony.com/doc/current/frontend/asset_mapper.html)
