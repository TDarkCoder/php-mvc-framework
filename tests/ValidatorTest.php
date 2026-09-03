<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Tests;

use TDarkCoder\Framework\Contracts\Rule;
use TDarkCoder\Framework\Exceptions\ServerErrorException;
use TDarkCoder\Framework\Exceptions\ValidationException;
use TDarkCoder\Framework\Tests\Fixtures\User;
use TDarkCoder\Framework\Validation\Validator;

final class ValidatorTest extends TestCase
{
    public function testRequiredAcceptsZeroAndRejectsBlank(): void
    {
        $validator = Validator::make(['a' => '0', 'b' => '  ', 'c' => [], 'd' => null], [
            'a' => 'required',
            'b' => 'required',
            'c' => 'required',
            'd' => 'required',
            'e' => 'required',
        ]);

        $this->assertSame(['b', 'c', 'd', 'e'], array_keys($validator->errors()));
    }

    public function testOtherRulesAreSkippedForBlankValues(): void
    {
        $this->assertTrue(Validator::make(['email' => ''], ['email' => 'nullable|email'])->passes());
        $this->assertTrue(Validator::make([], ['age' => 'integer|gte:18'])->passes());
        $this->assertFalse(Validator::make(['email' => 'x'], ['email' => 'email'])->passes());
    }

    public function testLengthRulesUseMultibyteLength(): void
    {
        $this->assertTrue(Validator::make(['name' => 'Азиз'], ['name' => 'min:4|max:4'])->passes());
        $this->assertFalse(Validator::make(['name' => 'Азиз'], ['name' => 'max:3'])->passes());
        $this->assertTrue(Validator::make(['tags' => ['a', 'b']], ['tags' => 'min:2'])->passes());
    }

    public function testNumericComparisonsAndTypes(): void
    {
        $validator = Validator::make([
            'age' => '17',
            'price' => '9.5',
            'count' => '3.2',
            'flag' => 'yes',
            'choice' => 'd',
        ], [
            'age' => 'number|gte:18',
            'price' => 'numeric|lte:10',
            'count' => 'integer',
            'flag' => 'boolean',
            'choice' => 'in:a,b,c',
        ]);

        $this->assertSame(['age', 'count', 'flag', 'choice'], array_keys($validator->errors()));
        $this->assertSame('The age must be greater than or equal to 18', $validator->errors()['age'][0]);
    }

    public function testMatchConfirmedAndRegex(): void
    {
        $validator = Validator::make([
            'password' => 'secret',
            'password_confirmation' => 'secret',
            'other' => 'nope',
            'slug' => 'Hello World',
        ], [
            'password' => 'confirmed',
            'other' => 'match:password',
            'slug' => ['regex:/^[a-z0-9-]+$/'],
        ]);

        $this->assertSame(['other', 'slug'], array_keys($validator->errors()));
    }

    public function testCustomMessagesAndAttributeNames(): void
    {
        $validator = Validator::make([], ['first_name' => 'required', 'email' => 'required'], [
            'email.required' => 'We need your email',
        ]);

        $this->assertSame('The first name field is required', $validator->errors()['first_name'][0]);
        $this->assertSame('We need your email', $validator->errors()['email'][0]);
    }

    public function testValidateReturnsOnlyValidatedKeysOrThrows(): void
    {
        $data = Validator::make(['name' => 'A', 'extra' => 'x'], ['name' => 'required'])->validate();

        $this->assertSame(['name' => 'A'], $data);

        try {
            Validator::make([], ['name' => 'required'])->validate();

            $this->fail('Expected a ValidationException');
        } catch (ValidationException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
            $this->assertSame(['name' => ['The name field is required']], $exception->errors());
        }
    }

    public function testUnknownRuleThrows(): void
    {
        $this->expectException(ServerErrorException::class);

        Validator::make(['a' => 1], ['a' => 'bogus'])->passes();
    }

    public function testClosureAndClassExtensions(): void
    {
        Validator::extend('even', fn(string $attribute, mixed $value): bool => (int) $value % 2 === 0, 'The :attribute must be even');
        Validator::extend('uppercase', UppercaseRule::class);

        $validator = Validator::make(['n' => '3', 'code' => 'abc'], ['n' => 'even', 'code' => 'uppercase']);

        $this->assertSame('The n must be even', $validator->errors()['n'][0]);
        $this->assertSame('The code must be uppercase', $validator->errors()['code'][0]);

        $this->assertTrue(Validator::make(['code' => 'ABC'], ['code' => [new UppercaseRule()]])->passes());
    }

    public function testUniqueRuleQueriesTheModelAndIgnoresAnId(): void
    {
        $app = $this->app();
        $this->createUsersTable($app);

        $user = User::create(['name' => 'A', 'email' => 'a@b.co', 'password' => 'x']);

        $this->assertFalse(Validator::make(['email' => 'a@b.co'], ['email' => 'unique:' . User::class])->passes());
        $this->assertTrue(Validator::make(['email' => 'new@b.co'], ['email' => 'unique:' . User::class])->passes());
        $this->assertTrue(Validator::make(['email' => 'a@b.co'], ['email' => 'unique:' . User::class . ',email,' . $user->id])->passes());
    }
}

final class UppercaseRule implements Rule
{
    public function passes(string $attribute, mixed $value, array $parameters, array $data): bool
    {
        return is_string($value) && $value === strtoupper($value);
    }

    public function message(): string
    {
        return 'The :attribute must be uppercase';
    }
}
