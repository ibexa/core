<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Command;

use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Console command for deep copying subtree from one location to another.
 */
#[AsCommand(
    name: 'ibexa:copy-subtree',
    description: 'Copies a subtree from one Location to another'
)]
class CopySubtreeCommand extends Command
{
    private LocationService $locationService;

    private PermissionResolver $permissionResolver;

    private UserService $userService;

    private ContentTypeService $contentTypeService;

    private SearchService $searchService;

    /**
     * @param \Ibexa\Contracts\Core\Repository\LocationService $locationService
     * @param \Ibexa\Contracts\Core\Repository\PermissionResolver $permissionResolver
     * @param \Ibexa\Contracts\Core\Repository\UserService $userService
     * @param \Ibexa\Contracts\Core\Repository\ContentTypeService $contentTypeService
     * @param \Ibexa\Contracts\Core\Repository\SearchService $searchService
     */
    public function __construct(
        LocationService $locationService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        ContentTypeService $contentTypeService,
        SearchService $searchService
    ) {
        parent::__construct();
        $this->locationService = $locationService;
        $this->permissionResolver = $permissionResolver;
        $this->userService = $userService;
        $this->contentTypeService = $contentTypeService;
        $this->searchService = $searchService;
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'source-location-id',
                InputArgument::REQUIRED,
                'ID of source Location'
            )
            ->addArgument(
                'target-location-id',
                InputArgument::REQUIRED,
                'ID of target Location'
            )
            ->addOption(
                'user',
                'u',
                InputOption::VALUE_OPTIONAL,
                'Ibexa username (with Role containing at least content policies: create, read)',
                'admin'
            );
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->permissionResolver->setCurrentUserReference(
            $this->userService->loadUserByLogin($input->getOption('user'))
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sourceLocationId = $input->getArgument('source-location-id');
        $targetLocationId = $input->getArgument('target-location-id');

        $sourceLocation = $this->locationService->loadLocation($sourceLocationId);
        $targetLocation = $this->locationService->loadLocation($targetLocationId);

        if (stripos($targetLocation->pathString, $sourceLocation->pathString) !== false) {
            throw new InvalidArgumentException(
                'target-location-id',
                'Cannot copy subtree to its own descendant Location'
            );
        }

        $targetContentType = $this->contentTypeService->loadContentType(
            $targetLocation->getContentInfo()->contentTypeId
        );

        if (!$targetContentType->isContainer()) {
            throw new InvalidArgumentException(
                'target-location-id',
                'The selected Location cannot contain children'
            );
        }
        $questionHelper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            sprintf(
                'Are you sure you want to copy `%s` subtree (no. of children: %s) into `%s`? This may take a while for a big number of nested children [Y/n]? ',
                $sourceLocation->contentInfo->name,
                $this->getAllChildrenCountExpr($sourceLocation),
                $targetLocation->contentInfo->name
            )
        );

        if (!$input->getOption('no-interaction') && !$questionHelper->ask($input, $output, $question)) {
            return self::SUCCESS;
        }

        $this->locationService->copySubtree(
            $sourceLocation,
            $targetLocation
        );

        $output->writeln(
            '<info>Finished</info>'
        );

        return self::SUCCESS;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    protected function getAllChildrenCountExpr(Location $location): string
    {
        $query = new LocationQuery([
            'filter' => new Criterion\Subtree($location->pathString),
        ]);

        $totalCount = $this->searchService->findLocations($query)->totalCount;

        return $totalCount !== null ? (string) $totalCount : '~';
    }
}
