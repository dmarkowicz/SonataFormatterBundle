<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\FormatterBundle\Tests\Formatter;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sonata\FormatterBundle\Formatter\Pool;
use Sonata\FormatterBundle\Formatter\RawFormatter;

class PoolTest extends TestCase
{
    public function testPool()
    {
        $formatter = new RawFormatter();
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        // NEXT_MAJOR: remove this if block
        if (class_exists('\Twig_Loader_Array')) {
            $template = $this->getMockBuilder('\Twig_Template')
                ->disableOriginalConstructor()
                ->getMock();
            $template->expects($this->once())->method('render')->will($this->returnValue('Salut'));

            $env->expects($this->once())->method('createTemplate')->will($this->returnValue($template));
        } else {
            $env->expects($this->once())->method('render')->will($this->returnValue('Salut'));
        }

        $pool = $this->getPool();

        $this->assertFalse($pool->has('foo'));

        $pool->add('foo', $formatter, $env);

        $this->assertTrue($pool->has('foo'));

        $this->assertSame('Salut', $pool->transform('foo', 'Salut'));
    }

    public function testNonExistantFormatter()
    {
        $this->expectException('RuntimeException');

        $pool = $this->getPool();
        $pool->get('foo');
    }

    public function testSyntaxError()
    {
        $formatter = new RawFormatter();
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        // NEXT_MAJOR: remove this if block
        if (class_exists('\Twig_Loader_Array')) {
            $template = $this->getMockBuilder('\Twig_Template')
                ->disableOriginalConstructor()
                ->getMock();
            $template->expects($this->once())->method('render')->will($this->throwException(new \Twig_Error_Syntax('Error')));

            $env->expects($this->once())->method('createTemplate')->will($this->returnValue($template));
        } else {
            $env->expects($this->once())->method('render')->will($this->throwException(new \Twig_Error_Syntax('Error')));
        }

        $pool = $this->getPool();
        $pool->add('foo', $formatter, $env);

        $this->assertSame('Salut', $pool->transform('foo', 'Salut'));
    }

    public function testTwig_Sandbox_SecurityError()
    {
        $formatter = new RawFormatter();
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        // NEXT_MAJOR: remove this if block
        if (class_exists('\Twig_Loader_Array')) {
            $template = $this->getMockBuilder('\Twig_Template')
                ->disableOriginalConstructor()
                ->getMock();
            $template->expects($this->once())->method('render')->will($this->throwException(new \Twig_Sandbox_SecurityError('Error')));

            $env->expects($this->once())->method('createTemplate')->will($this->returnValue($template));
        } else {
            $env->expects($this->once())->method('render')->will($this->throwException(new \Twig_Sandbox_SecurityError('Error')));
        }

        $pool = $this->getPool();
        $pool->add('foo', $formatter, $env);

        $this->assertSame('Salut', $pool->transform('foo', 'Salut'));
    }

    public function testUnexpectedException()
    {
        $this->expectException('RuntimeException');

        $formatter = new RawFormatter();
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        // NEXT_MAJOR: remove this if block
        if (class_exists('\Twig_Loader_Array')) {
            $template = $this->getMockBuilder('\Twig_Template')
                ->disableOriginalConstructor()
                ->getMock();
            $template->expects($this->once())->method('render')->will($this->throwException(new \RuntimeException('Error')));

            $env->expects($this->once())->method('createTemplate')->will($this->returnValue($template));
        } else {
            $env->expects($this->once())->method('render')->will($this->throwException(new \RuntimeException('Error')));
        }

        $pool = $this->getPool();
        $pool->add('foo', $formatter, $env);

        $pool->transform('foo', 'Salut');
    }

    public function testDefaultFormatter()
    {
        $pool = new Pool('default');
        $pool->setLogger($this->createMock(LoggerInterface::class));

        $this->assertSame('default', $pool->getDefaultFormatter());
    }

    /**
     * NEXT_MAJOR: This should be removed.
     *
     * @group legacy
     */
    public function testBcDefaultFormatter()
    {
        $formatter = new RawFormatter();
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        $pool = new Pool();

        $pool->add('foo', $formatter, $env);

        $this->assertSame('foo', $pool->getDefaultFormatter());
    }

    /**
     * NEXT_MAJOR: This should be removed.
     *
     * @group legacy
     */
    public function testLoggerProvidedThroughConstuctor()
    {
        $formatter = new RawFormatter();
        $pool = new Pool($logger = $this->createMock('Psr\Log\LoggerInterface'));
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        // NEXT_MAJOR: remove this if block
        if (class_exists('\Twig_Loader_Array')) {
            $template = $this->getMockBuilder('\Twig_Template')
                ->disableOriginalConstructor()
                ->getMock();
            $template->expects($this->once())->method('render')->will($this->throwException(new \Twig_Sandbox_SecurityError('Error')));

            $env->expects($this->once())->method('createTemplate')->will($this->returnValue($template));
        } else {
            $env->expects($this->once())->method('render')->will($this->throwException(new \Twig_Sandbox_SecurityError('Error')));
        }

        $pool->add('foo', $formatter, $env);
        $logger->expects($this->once())->method('critical');

        $pool->transform('foo', 'whatever');
    }

    private function getPool()
    {
        $pool = new Pool('whatever');
        $pool->setLogger($this->createMock(LoggerInterface::class));

        return $pool;
    }
}
