# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Multi-client support with named connections
- Index management commands (create, reset, populate, alias switch, template reset, debug config)
- Doctrine ORM integration (automatic sync via entity listener, ORM provider, ORM hydrator)
- Object transformation via reflection or Symfony Serializer
- Repository pattern for search queries
- Blue/green reindexing with atomic alias switching
- Index template management
- Event system with 7 lifecycle events (PreRequest, PostRequest, PreTransform, PostTransform, PrePopulate, BatchProcessed, PostPopulate)
- Finder services with configurable hydration (array, ORM)
