# Contributing

Thank you for considering contributing to the OpenSearch Bundle.

## Development Setup

1. Clone the repository:

```bash
git clone https://github.com/bneumann/opensearch-bundle.git
cd opensearch-bundle
```

2. Install dependencies:

```bash
composer install
```

3. Run the test suite:

```bash
vendor/bin/phpunit
```

## Pull Requests

- Fork the repository and create your branch from `main`.
- Add tests for any new functionality.
- Ensure the full test suite passes before submitting.
- Keep pull requests focused on a single change.
- Update documentation if your change affects the public API.

## Coding Standards

- Use `declare(strict_types=1)` in every PHP file.
- Mark classes as `final` unless they are explicitly designed for extension.
- Follow existing code conventions (PSR-12, Symfony patterns).
- Write interfaces for any new public-facing service.

## Reporting Bugs

Open an issue with:

- A clear description of the problem.
- Steps to reproduce.
- Expected vs actual behavior.
- Your PHP, Symfony, and OpenSearch versions.

## Feature Requests

Open an issue describing the use case and proposed solution. Discussion before implementation helps keep the scope manageable.
