# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Added
- Add PHP version constraints to Composer config. No BC breaks as the constraint
  covers all current and past versions back to the implicit support for PHP v5.4.
  No future versions of PHP will be supported by *v1.x*.

## [1.0.3] - 2016-08-15
### Fixed
- PHP v5.4 compatibility for `trimEmptyParts()` by not using `array_filter()`.

## [1.0.2] - 2016-08-03
### Fixed
- Fix the logic for when to return a single value from `info()`.

## [1.0.1] - 2016-07-22
### Fixed
- Handle empty paths

## [1.0.0] - 2016-07-15
Initial Release
