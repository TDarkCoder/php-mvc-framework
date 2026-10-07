<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Tests;

use TDarkCoder\Framework\Exceptions\ViewNotFoundException;

final class ViewTest extends TestCase
{
    public function testRendersParamsWithEscaping(): void
    {
        $this->writeView('hello', 'Hi <?= e($name) ?>');

        $this->assertSame('Hi &lt;b&gt;', $this->app()->view->render('hello', ['name' => '<b>']));
    }

    public function testLayoutReceivesTheContent(): void
    {
        $this->writeView('hello', 'Hi <?= $name ?>');
        $this->writeView('layouts.main', '<main><?= $content ?></main>');

        $view = $this->app()->view->layout('main');

        $this->assertSame('<main>Hi A</main>', $view->render('hello', ['name' => 'A']));
        $this->assertSame('Hi B', $view->layout(null)->render('hello', ['name' => 'B']));
    }

    public function testPartialsCanRenderMoreThanOnce(): void
    {
        $this->writeView('item', '[<?= $i ?>]');
        $this->writeView('list', '<?= view("item", ["i" => 1]) . view("item", ["i" => 2]) ?>');

        $this->assertSame('[1][2]', $this->app()->view->render('list'));
    }

    public function testDotNotationAndExists(): void
    {
        $this->writeView('users.show', 'user');

        $view = $this->app()->view;

        $this->assertTrue($view->exists('users.show'));
        $this->assertFalse($view->exists('users.edit'));
        $this->assertSame('user', $view->render('users.show'));
    }

    public function testMissingViewThrows(): void
    {
        $this->expectException(ViewNotFoundException::class);

        $this->app()->view->render('nope');
    }

    public function testTemplatesCannotReachTheViewInstance(): void
    {
        $this->writeView('scope', '<?= isset($this) ? "yes" : "no" ?>');

        $this->assertSame('no', $this->app()->view->render('scope'));
    }
}
