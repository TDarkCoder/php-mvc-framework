<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Tests;

use TDarkCoder\Framework\Form\Form;

final class FormTest extends TestCase
{
    public function testStartEmitsCsrfAndMethodFields(): void
    {
        $this->app();
        $form = new Form();
        $token = csrf_token();

        $put = $form->start('/users/1', 'PUT', ['class' => 'my-form', 'novalidate' => true]);

        $this->assertStringStartsWith('<form action="/users/1" method="POST" class="my-form" novalidate>', $put);
        $this->assertStringContainsString('name="_token" value="' . $token . '"', $put);
        $this->assertStringContainsString('name="_method" value="PUT"', $put);

        $get = $form->start('/search', 'GET');

        $this->assertSame('<form action="/search" method="GET">', $get);
        $this->assertSame('</form>', $form->end());
    }

    public function testFieldsRenderOldInputAndErrors(): void
    {
        $_SESSION['_flash']['new'] = [
            '_errors' => ['email' => ['Bad email'], 'bio' => ['Too long']],
            '_old_input' => ['email' => '<x>', 'bio' => 'text', 'role' => 'b', 'agree' => '1'],
        ];

        $this->app();
        $form = new Form();

        $email = (string) $form->input('email', 'Your email')->email()->placeholder('you@example.com')->required();

        $this->assertStringContainsString('<label for="email" class="form-label">Your email</label>', $email);
        $this->assertStringContainsString('type="email"', $email);
        $this->assertStringContainsString('class="form-control is-invalid"', $email);
        $this->assertStringContainsString('value="&lt;x&gt;"', $email);
        $this->assertStringContainsString('placeholder="you@example.com" required', $email);
        $this->assertStringContainsString('<div class="invalid-feedback">Bad email</div>', $email);

        $bio = (string) $form->textarea('bio');

        $this->assertStringContainsString('is-invalid', $bio);
        $this->assertStringContainsString('>text</textarea>', $bio);
        $this->assertStringContainsString('Too long', $bio);

        $role = (string) $form->select('role', ['a' => 'Admin', 'b' => 'Basic']);

        $this->assertStringContainsString('<option value="b" selected>Basic</option>', $role);
        $this->assertStringNotContainsString('is-invalid', $role);

        $agree = (string) $form->checkbox('agree', 'I agree');

        $this->assertStringContainsString('type="checkbox"', $agree);
        $this->assertStringContainsString(' checked', $agree);

        $name = (string) $form->input('first_name')->default('Az');

        $this->assertStringContainsString('>First name</label>', $name);
        $this->assertStringContainsString('value="Az"', $name);
    }

    public function testHiddenTokenFieldRendersWithoutLabel(): void
    {
        $this->app();

        $field = (string) (new Form())->input('ignored')->token();

        $this->assertSame('<input type="hidden" class="form-control" id="_token" name="_token" value="' . csrf_token() . '">', $field);
    }
}
