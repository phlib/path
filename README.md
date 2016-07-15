# phlib/path

[![Build Status](https://img.shields.io/travis/phlib/path/master.svg?style=flat-square)](https://travis-ci.org/phlib/path)
[![Latest Stable Version](https://img.shields.io/packagist/v/phlib/path.svg?style=flat-square)](https://packagist.org/packages/phlib/path)
[![Total Downloads](https://img.shields.io/packagist/dt/phlib/path.svg?style=flat-square)](https://packagist.org/packages/phlib/path)

PHP path handling component for dealing with escaped directory separators

## Install

Via Composer

``` bash
$ composer require phlib/path
```

## Usage

Creation of path instance

``` php
$path = \Phlib\Path::fromString('foo/bar/baz');
$info = $path->info(); // should return the same as `pathinfo`
```

Using path to parse paths with escaped directory separators 

``` php
$path = \Phlib\Path::fromString('foo/bar\\/baz');
echo $path->info(\Phlib\Path::INFO_BASENAME); // bar\/baz
```

Build paths with escaped separators

``` php
$parts = ['foo', 'bar/baz', 'taz'];
$path  = new \Phlib\Path($parts);
echo $path->toString(); // foo/bar\/baz/taz
```
