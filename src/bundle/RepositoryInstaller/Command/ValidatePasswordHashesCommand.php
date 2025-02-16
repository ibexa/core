<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\RepositoryInstaller\Command;

use Ibexa\Contracts\Core\Repository\PasswordHashService;
use Ibexa\Core\FieldType\User\UserStorage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'ibexa:user:validate-password-hashes')]
final class ValidatePasswordHashesCommand extends Command
{
    /** @var \Ibexa\Core\FieldType\User\UserStorage */
    private $userStorage;

    /** @var \Ibexa\Contracts\Core\Repository\PasswordHashService */
    private $passwordHashService;

    public function __construct(
        UserStorage $userStorage,
        PasswordHashService $passwordHashService
    ) {
        $this->userStorage = $userStorage;
        $this->passwordHashService = $passwordHashService;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $unsupportedHashesCounter = $this->userStorage->countUsersWithUnsupportedHashType(
            $this->passwordHashService->getSupportedHashTypes()
        );

        if ($unsupportedHashesCounter > 0) {
            $output->writeln(sprintf('<error>Found %s users with unsupported password hash types</error>', $unsupportedHashesCounter));
            $output->writeln('<info>For more details check documentation:</info> <href=https://doc.ezplatform.com/en/latest/releases/ez_platform_v3.0_deprecations/#password-hashes>https://doc.ezplatform.com/en/latest/releases/ez_platform_v3.0_deprecations/#password-hashes</>');
        } else {
            $output->writeln('OK - <info>All users have supported password hash types</info>');
        }

        return self::SUCCESS;
    }
}
