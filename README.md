# phlib/path

[![Code Checks](https://img.shields.io/github/actions/workflow/status/phlib/path/code-checks.yml?logo=github)](https://github.com/phlib/path/actions/workflows/code-checks.yml)
[![Codecov](https://img.shields.io/codecov/c/github/phlib/path.svg?logo=codecov)](https://codecov.io/gh/phlib/path)
[![Latest Stable Version](https://img.shields.io/packagist/v/phlib/path.svg?logo=packagist)](https://packagist.org/packages/phlib/path)
[![Total Downloads](https://img.shields.io/packagist/dt/phlib/path.svg?logo=packagist)](https://packagist.org/packages/phlib/path)
![Licence](https://img.shields.io/github/license/phlib/path.svg)

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

## License

This package is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
