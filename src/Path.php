<?php

namespace Phlib;

class Path implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * Constants for info keys, used for asking for info with the info method
     *
     * @see Path::info
     */
    const INFO_DIRNAME   = 1;
    const INFO_BASENAME  = 2;
    const INFO_EXTENSION = 4;
    const INFO_FILENAME  = 8;
    const INFO_ALL       = 15;

    /**
     * The separator to use for delimiting parts of the path
     *
     * @var string $directorySeparator
     */
    private $directorySeparator;

    /**
     * The parts of the path
     *
     * @var string[] $parts
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
     * @var array $infoKeys
     */
    private $infoKeys = [
        self::INFO_DIRNAME   => 'dirname',
        self::INFO_BASENAME  => 'basename',
        self::INFO_EXTENSION => 'extension',
        self::INFO_FILENAME  => 'filename',
    ];

    /**
     * Escape any directory separators which appear in a name (part of a path)
     *
     * This also escapes the escape character, to ensure that the escape character is still usable
     *
     * @param string $name
     * @param string $directorySeparator
     * @return string
     */
    public static function escapeName($name, $directorySeparator = DIRECTORY_SEPARATOR)
    {
        return strtr(
            $name,
            [
                '\\'                => '\\\\',                  // escape the escape character
                $directorySeparator  => "\\$directorySeparator",  // escape the separator
            ]
        );
    }

    /**
     * Unescape the directory separator character
     *
     * This generally shouldn't be needed outside of this class, as asking for a particular part of a path
     * from an instance of this class should return the unescaped name
     *
     * @param string $name
     * @param string $directorySeparator
     * @return string
     */
    public static function unescapeName($name, $directorySeparator = DIRECTORY_SEPARATOR)
    {
        return strtr(
            $name,
            [
                '\\\\'                  => '\\',
                "\\$directorySeparator" => "$directorySeparator",
            ]
        );
    }

    /**
     * Create a new path instance from a string path
     *
     * @param string $path
     * @param string $directorySeparator
     * @return Path
     */
    public static function fromString($path, $directorySeparator = DIRECTORY_SEPARATOR)
    {
        $parts = self::splitPath($path, $directorySeparator);
        return new self($parts, $directorySeparator);
    }

    /**
     * Splits a path by the directory separator
     *
     * This method could be as simple as an explode, were it not for needing to account for escaped
     * directory separators.
     *
     * @param string $path
     * @param string $directorySeparator
     * @return array
     */
    private static function splitPath($path, $directorySeparator)
    {
        $index    = 0;
        $length   = mb_strlen($path);
        $escaping = false;
        $out      = [];
        $lastSep  = 0;
        while ($index < $length) {
            $chr = mb_substr($path, $index, 1);
            if ($escaping) {
                $escaping = false;
            } else {
                if ($chr === '\\') {
                    $escaping = true;
                } elseif ($chr === $directorySeparator) {
                    $out[] = self::unescapeName(mb_substr($path, $lastSep, $index - $lastSep), $directorySeparator);
                    $lastSep = $index + 1;
                }
            }
            $index++;
        }
        $out[] = self::unescapeName(mb_substr($path, $lastSep, $index - $lastSep), $directorySeparator);
        return $out;
    }

    /**
     * Path constructor
     * @param array $parts
     * @param string $directorySeparator
     */
    public function __construct($parts, $directorySeparator = DIRECTORY_SEPARATOR)
    {
        $parts = $this->trimEmptyParts($parts);

        $this->directorySeparator = $directorySeparator;
        $this->parts              = $parts;
    }

    /**
     * Get a new path instance for everything up to the parent directory
     *
     * @return Path
     */
    public function getDirnamePath()
    {
        return $this->slice(0, -1);
    }

    /**
     * Get a new path instance for a slice of the path (arguments should match array_slice)
     *
     * @param int $offset
     * @param int= $length
     * @return Path
     */
    public function slice($offset, $length = null)
    {
        $parts = $this->parts;
        $parts = isset($length) ? array_slice($parts, $offset, $length) : array_slice($parts, $offset);
        return new self($parts, $this->directorySeparator);
    }

    /**
     * Get a new path instance with any empty start part removed
     *
     * @return Path
     */
    public function trimStart()
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
     *
     * @return string
     */
    public function toString()
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
     * @param int $options
     * @return array|string
     */
    public function info($options = self::INFO_ALL)
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
    private function parseInfo()
    {
        if (isset($this->info)) {
            return;
        }
        $this->info  = [];
        $parts       = $this->parts;
        if (empty($parts)) {
            $this->info = [
                self::INFO_BASENAME => '',
                self::INFO_FILENAME => '',
            ];
            return;
        }
        $basename    = array_pop($parts);

        $this->info[self::INFO_BASENAME] = $basename;
        if (empty($parts)) {
            $this->info[self::INFO_DIRNAME] = '.';
        } elseif (count($parts) === 1 && empty($parts[0])) {
            $this->info[self::INFO_DIRNAME] = '/';
        } else {
            $this->info[self::INFO_DIRNAME] = implode($this->directorySeparator, $this->escapeParts($parts));
        }

        if (($dotPos = strrpos($basename, '.')) !== false) {
            $this->info[self::INFO_FILENAME]  = substr($basename, 0, $dotPos);
            $this->info[self::INFO_EXTENSION] = substr($basename, $dotPos + 1);
        } else {
            $this->info[self::INFO_FILENAME]  = $basename;
        }
    }

    /**
     * Escapes each part in the given parts array to escape directory separators and returns the resulting array
     *
     * @param array $parts
     * @return array
     */
    private function escapeParts($parts)
    {
        return array_map(function($name) {
            return self::escapeName($name);
        }, $parts);
    }

    /**
     * Trims the empty elements from the parts array
     *
     * This has special behaviour to deal with empty paths, and paths which contain only a separator
     *
     * @param array $parts
     * @return array
     */
    private function trimEmptyParts($parts)
    {
        $emptyLeading = empty($parts[0]);

        if (count($parts) === 1 && $emptyLeading) {
            // empty path (e.g. '')
            return [];
        }

        return array_filter($parts, function($part, $index) use ($emptyLeading) {
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

    /**
     * @inheritdoc
     */
    public function count()
    {
        return count($this->parts);
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->parts);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->parts[$offset];
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException('Cannot modify parts of the path');
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        throw new \RuntimeException('Cannot modify parts of the path');
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->parts);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}
