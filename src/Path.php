<?php

declare(strict_types=1);

namespace Phlib;

/**
 * @package Phlib\Path
 * @license LGPL-3.0
 */
class Path implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * Constants for info keys, used for asking for info with the info method
     *
     * @see Path::info
     */
    public const INFO_DIRNAME = 1;

    public const INFO_BASENAME = 2;

    public const INFO_EXTENSION = 4;

    public const INFO_FILENAME = 8;

    public const INFO_ALL = 15;

    /**
     * The separator to use for delimiting parts of the path
     *
     * @var string
     */
    private $directorySeparator;

    /**
     * The parts of the path
     *
     * @var string[]
     */
    private $parts;

    /**
     * Array containing the info properties of the path
     *
     * @see Path::info
     * @var array info
     */
    private $info;

    /**
     * Mapping of info keys to the key names used in the output
     *
     * @see Path::info
     * @var array
     */
    private $infoKeys = [
        self::INFO_DIRNAME => 'dirname',
        self::INFO_BASENAME => 'basename',
        self::INFO_EXTENSION => 'extension',
        self::INFO_FILENAME => 'filename',
    ];

    /**
     * Escape any directory separators which appear in a name (part of a path)
     *
     * This also escapes the escape character, to ensure that the escape character is still usable
     */
    public static function escapeName(string $name, string $directorySeparator = DIRECTORY_SEPARATOR): string
    {
        return strtr(
            $name,
            [
                '\\' => '\\\\', // escape the escape character
                $directorySeparator => "\\{$directorySeparator}", // escape the separator
            ]
        );
    }

    /**
     * Unescape the directory separator character
     *
     * This generally shouldn't be needed outside of this class, as asking for a particular part of a path
     * from an instance of this class should return the unescaped name
     */
    public static function unescapeName(string $name, string $directorySeparator = DIRECTORY_SEPARATOR): string
    {
        return strtr(
            $name,
            [
                '\\\\' => '\\',
                "\\{$directorySeparator}" => "{$directorySeparator}",
            ]
        );
    }

    /**
     * Create a new path instance from a string path
     */
    public static function fromString(string $path, string $directorySeparator = DIRECTORY_SEPARATOR): self
    {
        $parts = self::splitPath($path, $directorySeparator);
        return new self($parts, $directorySeparator);
    }

    /**
     * Splits a path by the directory separator
     *
     * This method could be as simple as an explode, were it not for needing to account for escaped
     * directory separators.
     */
    private static function splitPath(string $path, string $directorySeparator): array
    {
        $index = 0;
        $length = mb_strlen($path);
        $escaping = false;
        $out = [];
        $lastSep = 0;
        while ($index < $length) {
            $chr = mb_substr($path, $index, 1);
            if ($escaping) {
                $escaping = false;
            } elseif ($chr === '\\') {
                $escaping = true;
            } elseif ($chr === $directorySeparator) {
                $out[] = self::unescapeName(mb_substr($path, $lastSep, $index - $lastSep), $directorySeparator);
                $lastSep = $index + 1;
            }
            $index++;
        }
        $out[] = self::unescapeName(mb_substr($path, $lastSep, $index - $lastSep), $directorySeparator);
        return $out;
    }

    public function __construct(array $parts, string $directorySeparator = DIRECTORY_SEPARATOR)
    {
        $parts = $this->trimEmptyParts($parts);

        $this->directorySeparator = $directorySeparator;
        $this->parts = $parts;
    }

    /**
     * Get a new path instance for everything up to the parent directory
     */
    public function getDirnamePath(): self
    {
        return $this->slice(0, -1);
    }

    /**
     * Get a new path instance for a slice of the path (arguments should match array_slice)
     */
    public function slice(int $offset, int $length = null): self
    {
        $parts = $this->parts;
        $parts = isset($length) ? array_slice($parts, $offset, $length) : array_slice($parts, $offset);
        return new self($parts, $this->directorySeparator);
    }

    /**
     * Get a new path instance with any empty start part removed
     */
    public function trimStart(): self
    {
        $parts = $this->parts;
        if (count($parts) > 1 && empty($parts[0])) {
            array_shift($parts);
        }
        return new self($parts, $this->directorySeparator);
    }

    /**
     * Get a string representation of the path
     *
     * This will return the path with directory separators escaped for any part which contains them
     */
    public function toString(): string
    {
        return implode($this->directorySeparator, $this->escapeParts($this->parts));
    }

    /**
     * Get the path info for this path
     *
     * Ignoring the escaping of directory separators, this method should return the same result as
     * pathinfo for any given path
     *
     * @see pathinfo
     * @return array|string
     */
    public function info(int $options = self::INFO_ALL)
    {
        $this->parseInfo();
        $info = [];
        foreach ($this->infoKeys as $key => $keyName) {
            if (isset($this->info[$key]) && $options & $key) {
                $info[$keyName] = $this->info[$key];
            }
        }
        if (($options & ($options - 1)) === 0) {
            // is a power of two, i.e. a single info option was specified
            return current($info);
        }
        return $info;
    }

    /**
     * Gets the info data for the info method if this has not already been done
     *
     * @see Path::info
     */
    private function parseInfo(): void
    {
        if (isset($this->info)) {
            return;
        }
        $this->info = [];
        $parts = $this->parts;
        if (empty($parts)) {
            $this->info = [
                self::INFO_BASENAME => '',
                self::INFO_FILENAME => '',
            ];
            return;
        }
        $basename = array_pop($parts);

        $this->info[self::INFO_BASENAME] = $basename;
        if (empty($parts)) {
            $this->info[self::INFO_DIRNAME] = '.';
        } elseif (count($parts) === 1 && empty($parts[0])) {
            $this->info[self::INFO_DIRNAME] = '/';
        } else {
            $this->info[self::INFO_DIRNAME] = implode($this->directorySeparator, $this->escapeParts($parts));
        }

        if (($dotPos = strrpos($basename, '.')) !== false) {
            $this->info[self::INFO_FILENAME] = substr($basename, 0, $dotPos);
            $this->info[self::INFO_EXTENSION] = substr($basename, $dotPos + 1);
        } else {
            $this->info[self::INFO_FILENAME] = $basename;
        }
    }

    /**
     * Escapes each part in the given parts array to escape directory separators and returns the resulting array
     */
    private function escapeParts(array $parts): array
    {
        return array_map(function ($name) {
            return self::escapeName($name);
        }, $parts);
    }

    /**
     * Trims the empty elements from the parts array
     *
     * This has special behaviour to deal with empty paths, and paths which contain only a separator
     */
    private function trimEmptyParts(array $parts): array
    {
        $emptyLeading = empty($parts[0]);

        if (count($parts) === 1 && $emptyLeading) {
            // empty path (e.g. '')
            return [];
        }

        return array_filter($parts, function ($part, $index) use ($emptyLeading) {
            if ($index === 0) {
                // never trim first element to keep leading separators (e.g. '/foo')
                return true;
            }
            if ($index === 1 && $emptyLeading) {
                // never trim second element if first was empty (e.g. '/')
                return true;
            }
            return !empty($part);
        }, ARRAY_FILTER_USE_BOTH);
    }

    public function count(): int
    {
        return count($this->parts);
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->parts);
    }

    public function offsetGet($offset)
    {
        return $this->parts[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        throw new \RuntimeException('Cannot modify parts of the path');
    }

    public function offsetUnset($offset): void
    {
        throw new \RuntimeException('Cannot modify parts of the path');
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->parts);
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
