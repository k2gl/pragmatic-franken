---
id: ADR-0007
title: AssetMapper
status: Accepted
date: 2026-02-05
supersedes: []
superseded_by: []
audience: both
summary: "Symfony AssetMapper is the default for HTML-first apps (no Webpack/Vite). The boilerplate ships an Index slice as a non-normative reference; SPA projects may swap AssetMapper for Vite without violating any ADR."
---

# ADR-0007: AssetMapper

**TL;DR:** Default frontend tooling is AssetMapper. The shipped `src/Context/Home/Features/Index/` slice is a reference example, **not** an architectural commitment. Projects building an SPA can replace it with a `frontend/` workspace driven by Vite without violating any other ADR.

## Decision

Use Symfony AssetMapper as the default for managing frontend assets in HTML-first slices.

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
- **SPA escape hatch:** Projects shipping an SPA should replace the AssetMapper integration with a `frontend/` workspace driven by Vite/Bun/etc. The example `Home/Index` slice can be deleted without architectural impact.

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
    <script type="module" src="{{ asset('app.js') }}"></script>
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
