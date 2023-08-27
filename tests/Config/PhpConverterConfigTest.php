<?php

declare(strict_types=1);

namespace App\Tests\Config;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Riverwaysoft\PhpConverter\Ast\ConverterVisitor;
use Riverwaysoft\PhpConverter\CodeProvider\CodeProviderInterface;
use Riverwaysoft\PhpConverter\CodeProvider\FileSystemCodeProvider;
use Riverwaysoft\PhpConverter\CodeProvider\RemoteRepositoryCodeProvider;
use Riverwaysoft\PhpConverter\Config\PhpConverterConfig;
use Symfony\Component\Console\Input\InputInterface;

class PhpConverterConfigTest extends TestCase
{
    public function testVisitors(): void
    {
        $config = new PhpConverterConfig($this->createInputMock([]));

        $visitor1Mock = $this->createMock(ConverterVisitor::class);
        $visitor2Mock = $this->createMock(ConverterVisitor::class);

        $config->addVisitor($visitor1Mock);
        $config->addVisitor($visitor2Mock);

        $this->assertCount(2, $config->getVisitors());
    }

    public function testGetCodeProviderWhenAlreadySet(): void
    {
        $config = new PhpConverterConfig($this->createInputMock([]));

        $mockedProvider = $this->createMock(CodeProviderInterface::class);
        $config->setCodeProvider($mockedProvider);

        $this->assertSame($mockedProvider, $config->getCodeProvider());
    }

    public function testGetCodeProviderFromDirectory(): void
    {
        $input = $this->createInputMock([
            'from' => __DIR__,
        ]);
        $config = new PhpConverterConfig($input);

        $this->assertInstanceOf(FileSystemCodeProvider::class, $config->getCodeProvider());
    }

    public function testGetCodeProviderFromGitLinkWithBranch(): void
    {
        $input = $this->createInputMock([
            'from' => 'https://example.com/repo.git',
            'branch' => 'main',
        ]);
        $config = new PhpConverterConfig($input);

        $this->assertInstanceOf(RemoteRepositoryCodeProvider::class, $config->getCodeProvider());
    }

    public function testGetCodeProviderFromGitShhLinkWithBranch(): void
    {
        $input = $this->createInputMock([
            'from' => 'git@gitlab.com:test_soft/project/repo.git',
            'branch' => 'main',
        ]);
        $config = new PhpConverterConfig($input);

        $this->assertInstanceOf(RemoteRepositoryCodeProvider::class, $config->getCodeProvider());
    }

    public function testGetCodeProviderFromGitLinkWithoutBranch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Option --branch is required when using URL as repository source');

        $input = $this->createInputMock([
            'from' => 'https://example.com/repo.git',
            'branch' => '',
        ]);
        $config = new PhpConverterConfig($input);

        $config->getCodeProvider();
    }

    public function testGetCodeProviderFromInvalidOption(): void
    {
        $this->expectException(Exception::class);

        $input = $this->createInputMock([
            'from' => 'invalid',
        ]);
        $config = new PhpConverterConfig($input);

        $config->getCodeProvider();
    }

    private function createInputMock(mixed $values): InputInterface
    {
        $mock = $this->createMock(InputInterface::class);
        $mock->method('getOption')
            ->willReturnCallback(function ($argument) use ($values) {
                if (isset($values[$argument])) {
                    return $values[$argument];
                }
                throw new Exception('Unknown input mock argument ' . $argument);
            });

        return $mock;
    }
}
