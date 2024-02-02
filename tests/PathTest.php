<?php

declare(strict_types=1);

namespace Phlib;

use PHPUnit\Framework\TestCase;

/**
 * @package Phlib\Path
 * @license LGPL-3.0
 */
class PathTest extends TestCase
{
    public function testCreateFromParts(): void
    {
        $parts = ['foo', 'bar/baz', 'taz'];
        $phlibPath = new Path($parts);
        $this->assertEquals('foo/bar\\/baz/taz', $phlibPath->toString());
        // Also test magic `__toString()`
        $this->assertEquals('foo/bar\\/baz/taz', (string)$phlibPath);
    }

    /**
     * @dataProvider matchesPathInfoProvider
     */
    public function testMatchesPathInfo(string $path): void
    {
        $phpPathInfo = pathinfo($path);
        $phlibPathInfo = Path::fromString($path)->info();

        $this->assertPathInfoEquals(
            $phpPathInfo,
            $phlibPathInfo,
            "Failed asserting path info matched for path '{$path}'"
        );
    }

    public function matchesPathInfoProvider(): array
    {
        return [
            ['foo'],
            ['foo/bar'],
            ['foo/bar/'],
            ['/foo'],
            ['/foo/bar'],
            ['/foo/bar/'],
            ['foo.baz'],
            ['foo/bar.baz'],
            ['/foo.baz'],
            ['/foo/bar.baz'],
            [''],
            ['/'],
            ['foo//bar'],
            ['/foo//bar'],
            ['/foo//bar/'],
            ['/foo//bar/'],
            ['/foo//bar//'],
            ['//foo//bar'],
            ['//foo//bar/'],
            ['//'],
            ['///'],
        ];
    }

    public function testEscapingPathSeparators(): void
    {
        $parts = [
            'foo/bar',
            'baz/taz\\',
            'boz/.woz',
        ];

        $parts = array_map(function ($part) {
            return Path::escapeName($part);
        }, $parts);

        $phlibPath = Path::fromString(implode('/', $parts));
        $this->assertEquals(3, count($phlibPath));
    }

    /**
     * @dataProvider ignoresEscapedSeparatorsProvider
     */
    public function testIgnoresEscapedSeparators(string $path, int $expectedCount): void
    {
        $phlibPath = Path::fromString($path);
        $this->assertEquals($expectedCount, count($phlibPath), "Failed asserting path count for path '{$path}'");
    }

    public function ignoresEscapedSeparatorsProvider(): array
    {
        return [
            ['foo\\/bar', 1],
            ['foo/bar\\/baz/foo', 3],
            ['foo/bar\\\\/baz/foo', 4],
            ['/foo/bar\\\\/baz/foo', 5],
            ['\\/foo/bar/baz', 3],
            ['foo/bar/\\/baz', 3],
            ['foo/bar/baz\\/', 3],
        ];
    }

    public function testPathInfoOption(): void
    {
        $path = 'foo/bar/baz.taz';
        $phlibPath = Path::fromString($path);
        $this->assertEquals(pathinfo($path, PATHINFO_BASENAME), $phlibPath->info(Path::INFO_BASENAME));
        $this->assertEquals(pathinfo($path, PATHINFO_DIRNAME), $phlibPath->info(Path::INFO_DIRNAME));
        $this->assertEquals(pathinfo($path, PATHINFO_FILENAME), $phlibPath->info(Path::INFO_FILENAME));
        $this->assertEquals(pathinfo($path, PATHINFO_EXTENSION), $phlibPath->info(Path::INFO_EXTENSION));
    }

    public function testPathInfoMultipleOptions(): void
    {
        // a little extra behaviour which pathinfo can't handle
        $path = 'foo/bar/baz.taz';
        $phlibPath = Path::fromString($path);

        $expected = [
            'filename' => pathinfo($path, PATHINFO_FILENAME),
            'extension' => pathinfo($path, PATHINFO_EXTENSION),
        ];
        $actual = $phlibPath->info(Path::INFO_FILENAME | Path::INFO_EXTENSION);

        $this->assertPathInfoEquals($expected, $actual);
    }

    public function testPathInfoMultipleOptionsNotAllPresent(): void
    {
        $path = '';
        $phlibPath = Path::fromString($path);

        $expected = [
            'filename' => pathinfo($path, PATHINFO_FILENAME),
        ];
        $actual = $phlibPath->info(Path::INFO_FILENAME | Path::INFO_EXTENSION);

        $this->assertIsArray($actual);
        $this->assertPathInfoEquals($expected, $actual);
    }

    public function testCountable(): void
    {
        $phlibPath = Path::fromString('foo/bar/baz.taz');
        $this->assertEquals(3, count($phlibPath));
    }

    public function testOffsetAccess(): void
    {
        $phlibPath = Path::fromString('foo/bar/baz.taz');
        $this->assertTrue(isset($phlibPath[1]));
        $this->assertEquals('baz.taz', $phlibPath[2]);
    }

    public function testOffsetsAreImmutable(): void
    {
        $this->expectException(\RuntimeException::class);

        $phlibPath = Path::fromString('foo/bar/baz.taz');
        $phlibPath[1] = 'boo';
    }

    public function testOffsetsCannotUnset(): void
    {
        $this->expectException(\RuntimeException::class);

        $phlibPath = Path::fromString('foo/bar/baz.taz');
        unset($phlibPath[1]);
    }

    public function testIterable(): void
    {
        $phlibPath = Path::fromString('foo/bar/baz.taz');
        $out = [];
        foreach ($phlibPath as $part) {
            $out[] = $part;
        }
        $this->assertEquals('foo::bar::baz.taz', implode('::', $out));
    }

    public function testPartsAreUnescaped(): void
    {
        $name = 'my/file';
        $dir = 'dir';
        $path = $dir . '/' . Path::escapeName($name);
        $phlibPath = Path::fromString($path);

        $this->assertEquals($name, $phlibPath->info(Path::INFO_BASENAME));
        $this->assertEquals($name, $phlibPath[1]);
    }

    public function testToStringReEscapes(): void
    {
        $name = 'my/file';
        $dir = 'dir';
        $path = $dir . '/' . Path::escapeName($name);
        $phlibPath = Path::fromString($path);

        $this->assertEquals($path, $phlibPath->toString());
    }

    public function testDirnamePath(): void
    {
        $root = 'root';
        $dir = 'dir';
        $path = "{$root}/{$dir}/file";

        $phlibPath = Path::fromString($path);
        $dirPath = $phlibPath->getDirnamePath();

        $this->assertInstanceOf('\Phlib\Path', $dirPath);
        $this->assertEquals($dir, $dirPath->info(Path::INFO_BASENAME));
        $this->assertEquals($root, $dirPath->info(Path::INFO_DIRNAME));
    }

    public function testSlice(): void
    {
        $path = 'root/dir/file';
        $phlibPath = Path::fromString($path);

        $this->assertEquals('dir/file', $phlibPath->slice(1)->toString());
        $this->assertEquals('root/dir', $phlibPath->slice(0, -1)->toString());
        $this->assertEquals('file', $phlibPath->slice(-1)->toString());
        $this->assertEquals('root', $phlibPath->slice(0, 1)->toString());
    }

    public function testTrimStart(): void
    {
        $path = '/root/dir/file';
        $phlibPath = Path::fromString($path);

        $trimmed = $phlibPath->trimStart();

        $this->assertInstanceOf('\Phlib\Path', $trimmed);
        $this->assertEquals('root/dir/file', $trimmed->toString());
        $this->assertEquals(3, $trimmed->count());
    }

    /**
     * Helper method for comparing pathinfo arrays
     */
    private function assertPathInfoEquals(array $expected, array $actual, string $message = ''): void
    {
        ksort($expected);
        ksort($actual);
        $this->assertEquals($expected, $actual, $message);
    }
}
