<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Tests;

use TDarkCoder\Framework\Application;
use TDarkCoder\Framework\Exceptions\NotFoundException;
use TDarkCoder\Framework\Exceptions\ServerErrorException;
use TDarkCoder\Framework\Tests\Fixtures\User;

final class ModelTest extends TestCase
{
    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = $this->app();
        $this->createUsersTable($this->application);
    }

    public function testCreateFindAndFindOrFail(): void
    {
        $user = User::create(['name' => 'A', 'email' => 'a@b.co', 'password' => 'secret']);

        $this->assertSame(1, $user->id);
        $this->assertTrue($user->exists);

        $found = User::findOne(['email' => 'a@b.co']);

        $this->assertInstanceOf(User::class, $found);
        $this->assertSame('A', $found->name);
        $this->assertSame('A', $found['name']);
        $this->assertTrue($found->exists);
        $this->assertNull(User::findOne(['email' => 'missing']));

        $this->expectException(NotFoundException::class);

        User::findOrFail(['id' => 99]);
    }

    public function testMultipleConditionsAndNullComparison(): void
    {
        User::create(['name' => 'A', 'email' => 'a@b.co', 'password' => 'x', 'role' => 'admin']);
        User::create(['name' => 'A', 'email' => 'a2@b.co', 'password' => 'x']);
        User::create(['name' => 'B', 'email' => 'b@b.co', 'password' => 'x']);

        $this->assertCount(1, User::findAll(['name' => 'A', 'role' => 'admin']));
        $this->assertCount(1, User::findAll(['name' => 'A', 'role' => null]));
        $this->assertCount(3, User::all());
        $this->assertContainsOnlyInstancesOf(User::class, User::all());
    }

    public function testSaveUpdatesExistingRows(): void
    {
        $user = User::create(['name' => 'A', 'email' => 'a@b.co', 'password' => 'x']);
        $user->name = 'B';
        $user->save();

        $this->assertSame('B', User::findOrFail(['id' => 1])->name);
        $this->assertCount(1, User::all());

        $this->assertTrue($user->update(['role' => 'editor']));
        $this->assertSame('editor', $user->role);
        $this->assertSame('editor', User::findOrFail(['id' => 1])->role);
    }

    public function testMassAssignmentIsGuarded(): void
    {
        $this->expectException(ServerErrorException::class);

        User::create(['id' => 5, 'name' => 'A']);
    }

    public function testDeleteRemovesTheRow(): void
    {
        $user = User::create(['name' => 'A', 'email' => 'a@b.co', 'password' => 'x']);

        $this->assertTrue($user->delete());
        $this->assertFalse($user->exists);
        $this->assertCount(0, User::all());
        $this->assertFalse((new User())->delete());
    }

    public function testHiddenAttributesAreLeftOutOfArrayAndJson(): void
    {
        $user = User::create(['name' => 'A', 'email' => 'a@b.co', 'password' => 'secret']);

        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertStringNotContainsString('secret', json_encode($user, JSON_THROW_ON_ERROR));
        $this->assertSame('secret', $user->password);
    }

    public function testInvalidIdentifiersAreRejected(): void
    {
        $this->expectException(ServerErrorException::class);

        User::findOne(['email = 1; --' => 'x']);
    }

    public function testRouteModelBinding(): void
    {
        User::create(['name' => 'A', 'email' => 'a@b.co', 'password' => 'x']);

        $app = $this->app('GET', '/users/1');
        $app->router->get('/users/{user}', fn(User $user): string => $user->name);

        $this->assertSame('A', $app->handle()->content());

        $app = $this->app('GET', '/users/2');
        $app->router->get('/users/{user}', fn(User $user): string => $user->name);

        $this->assertSame(404, $app->handle()->status());
    }

    public function testModelsReturnedFromHandlersBecomeJson(): void
    {
        User::create(['name' => 'A', 'email' => 'a@b.co', 'password' => 'secret']);

        $app = $this->app('GET', '/users/1');
        $app->router->get('/users/{user}', fn(User $user): User => $user);

        $response = $app->handle();

        $this->assertSame(['id' => 1, 'name' => 'A', 'email' => 'a@b.co', 'role' => null], json_decode($response->content(), true));
    }
}
