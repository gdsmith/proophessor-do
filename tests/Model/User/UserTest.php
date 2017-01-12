<?php
/**
 * This file is part of prooph/proophessor-do.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\ProophessorDo\Model\User;

use Prooph\ProophessorDo\Model\User\EmailAddress;
use Prooph\ProophessorDo\Model\User\Event\UserWasRegistered;
use Prooph\ProophessorDo\Model\User\Event\UserWasRegisteredAgain;
use Prooph\ProophessorDo\Model\User\Exception\InvalidName;
use Prooph\ProophessorDo\Model\User\User;
use Prooph\ProophessorDo\Model\User\UserId;
use ProophTest\ProophessorDo\TestCase;

class UserTest extends TestCase
{
    /**
     * @test
     */
    public function it_registers_a_new_user(): User
    {
        $userId = UserId::generate();
        $name = 'John Doe';
        $emailAddress = EmailAddress::fromString('john.doe@example.com');

        $user = User::registerWithData($userId, $name, $emailAddress);

        $this->assertInstanceOf(User::class, $user);

        $events = $this->popRecordedEvent($user);

        $this->assertEquals(1, count($events));
        $this->assertInstanceOf(UserWasRegistered::class, $events[0]);

        $expectedPayload = [
            'name' => $name,
            'email' => $emailAddress->toString(),
        ];

        $this->assertEquals($expectedPayload, $events[0]->payload());

        return $user;
    }

    /**
     * @test
     */
    public function it_registers_a_new_user_again(): void
    {
        $userId = UserId::generate();
        $name = 'John Doe';
        $emailAddress = EmailAddress::fromString('john.doe@example.com');

        $events = [
            UserWasRegistered::withData($userId, $name, $emailAddress),
        ];

        /** @var $user User */
        $user = $this->reconstituteAggregateFromHistory(User::class, $events);

        $user->registerAgain($name);

        $events = $this->popRecordedEvent($user);

        $this->assertEquals(1, count($events));
        $this->assertInstanceOf(UserWasRegisteredAgain::class, $events[0]);

        $expectedPayload = [
            'name' => $name,
            'email' => $emailAddress->toString(),
        ];

        $this->assertEquals($expectedPayload, $events[0]->payload());
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_user_registers_with_invalid_name(): void
    {
        $this->expectException(InvalidName::class);

        $name = '';

        User::registerWithData(UserId::generate(), $name, EmailAddress::fromString('john.doe@example.com'));
    }

    /**
     * @test
     * @depends it_registers_a_new_user
     */
    public function it_throws_an_exception_if_user_registers_again_with_invalid_name(User $user): void
    {
        $this->expectException(InvalidName::class);

        $name = '';

        $user->registerAgain($name);
    }
}
