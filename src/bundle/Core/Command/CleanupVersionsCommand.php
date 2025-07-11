<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Command;

use Doctrine\DBAL\Connection;
use Exception;
use Ibexa\Contracts\Core\Container\ApiLoader\RepositoryConfigurationProviderInterface;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Persistence\Legacy\Content\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway as ContentTypeGateway;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'ibexa:content:cleanup-versions',
    description: 'Removes unwanted content versions. Keeps the published version untouched. By default, also keeps the last archived/draft version.'
)]
class CleanupVersionsCommand extends Command
{
    public const DEFAULT_REPOSITORY_USER = 'admin';
    public const DEFAULT_EXCLUDED_CONTENT_TYPES = 'user';
    public const BEFORE_RUNNING_HINTS = <<<EOT
<error>Before you continue:</error>
- Make sure to back up your database.
- Take the installation offline. The database should not be modified while the script is being executed.
- Run this command without memory limit.
- Run this command in production environment using <info>--env=prod</info>
EOT;
    public const VERSION_DRAFT = 'draft';

    public const VERSION_ARCHIVED = 'archived';
    public const VERSION_PUBLISHED = 'published';
    public const VERSION_ALL = 'all';

    public const VERSION_STATUS = [
        self::VERSION_DRAFT => VersionInfo::STATUS_DRAFT,
        self::VERSION_ARCHIVED => VersionInfo::STATUS_ARCHIVED,
        self::VERSION_PUBLISHED => VersionInfo::STATUS_PUBLISHED,
    ];

    private readonly Repository $repository;

    private readonly RepositoryConfigurationProviderInterface $repositoryConfigurationProvider;

    private readonly Connection $connection;

    public function __construct(
        Repository $repository,
        RepositoryConfigurationProviderInterface $repositoryConfigurationProvider,
        Connection $connection
    ) {
        $this->repository = $repository;
        $this->repositoryConfigurationProvider = $repositoryConfigurationProvider;
        $this->connection = $connection;

        parent::__construct();
    }

    protected function configure(): void
    {
        $beforeRunningHints = self::BEFORE_RUNNING_HINTS;
        $this
            ->addOption(
                'status',
                't',
                InputOption::VALUE_OPTIONAL,
                sprintf(
                    "Select which version types should be removed: '%s', '%s', '%s'.",
                    self::VERSION_DRAFT,
                    self::VERSION_ARCHIVED,
                    self::VERSION_ALL
                ),
                self::VERSION_ALL
            )
            ->addOption(
                'keep',
                'k',
                InputOption::VALUE_OPTIONAL,
                "Sets the number of the most recent versions (both drafts and archived) which won't be removed.",
                'config_default'
            )
            ->addOption(
                'user',
                'u',
                InputOption::VALUE_OPTIONAL,
                'Ibexa username (with Role containing at least content policies: remove, read, versionread)',
                self::DEFAULT_REPOSITORY_USER
            )
            ->addOption(
                'excluded-content-types',
                null,
                InputOption::VALUE_OPTIONAL,
                'Comma-separated list of content type identifiers whose versions should not be removed, for instance `article`.',
                self::DEFAULT_EXCLUDED_CONTENT_TYPES
            )->setHelp(
                <<<EOT
The command <info>%command.name%</info> reduces content versions to a minimum. 
It keeps published version untouched, and by default also keeps the last archived/draft version.
Note: This script can potentially run for a very long time, and in Symfony dev environment it will consume memory exponentially with the size of the dataset.

{$beforeRunningHints}
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // We don't load repo services or config resolver before execute() to avoid loading before SiteAccess is set.
        $keep = $input->getOption('keep');
        if ($keep === 'config_default') {
            $config = $this->repositoryConfigurationProvider->getRepositoryConfig();
            $keep = $config['options']['default_version_archive_limit'];
        }

        if (($keep = (int) $keep) < 0) {
            throw new InvalidArgumentException(
                'keep',
                'Keep value cannot be negative.'
            );
        }

        $userService = $this->repository->getUserService();
        $contentService = $this->repository->getContentService();
        $permissionResolver = $this->repository->getPermissionResolver();

        $permissionResolver->setCurrentUserReference(
            $userService->loadUserByLogin($input->getOption('user'))
        );

        $status = $input->getOption('status');

        $excludedContentTypes = (string) $input->getOption('excluded-content-types');
        if ($excludedContentTypes === '') {
            $excludedContentTypes = self::DEFAULT_EXCLUDED_CONTENT_TYPES;
        }
        $excludedContentTypeIdentifiers = explode(',', $excludedContentTypes);
        $contentIds = $this->getObjectsIds($keep, $status, $excludedContentTypeIdentifiers);
        $contentIdsCount = count($contentIds);

        if ($contentIdsCount === 0) {
            $output->writeln('<info>There is no content matching the given Criteria.</info>');

            return self::SUCCESS;
        }

        $output->writeln(sprintf(
            '<info>Found %d Content IDs matching the given Criteria.</info>',
            $contentIdsCount
        ));

        $displayProgressBar = !($output->isVerbose() || $output->isVeryVerbose() || $output->isDebug());

        if ($displayProgressBar) {
            $progressBar = new ProgressBar($output, $contentIdsCount);
            $progressBar->setFormat(
                '%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%' . PHP_EOL
            );
            $progressBar->start();
        }

        $removedVersionsCounter = 0;

        $removeAll = $status === self::VERSION_ALL;

        foreach ($contentIds as $contentId) {
            try {
                $contentInfo = $contentService->loadContentInfo((int) $contentId);
                $versions = $contentService->loadVersions(
                    $contentInfo,
                    $removeAll ? null : $this->mapStatusToVersionInfoStatus($status)
                );
                $versions = iterator_to_array($versions);
                $versionsCount = count($versions);

                $output->writeln(sprintf(
                    '<info>Content %d has %d version(s)</info>',
                    (int) $contentId,
                    $versionsCount
                ), OutputInterface::VERBOSITY_VERBOSE);

                if ($removeAll) {
                    $versions = array_filter($versions, static function (VersionInfo $version): bool {
                        return $version->status !== VersionInfo::STATUS_PUBLISHED;
                    });
                }

                if ($keep > 0) {
                    $versions = array_slice($versions, 0, -$keep);
                }

                $output->writeln(sprintf(
                    'Found %d content (%d) version(s) to remove.',
                    count($versions),
                    (int) $contentId
                ), OutputInterface::VERBOSITY_VERBOSE);

                /** @var \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo $version */
                foreach ($versions as $version) {
                    $contentService->deleteVersion($version);
                    ++$removedVersionsCounter;
                    $output->writeln(sprintf(
                        'Content (%d) version (%d) has been deleted.',
                        $contentInfo->id,
                        $version->id
                    ), OutputInterface::VERBOSITY_VERBOSE);
                }

                if ($displayProgressBar) {
                    $progressBar->advance(1);
                }
            } catch (Exception $e) {
                $output->writeln(sprintf(
                    '<error>%s</error>',
                    $e->getMessage()
                ));
            }
        }

        $output->writeln(sprintf(
            '<info>Removed %d unwanted contents version(s) from %d Content item(s).</info>',
            $removedVersionsCounter,
            $contentIdsCount
        ));

        return self::SUCCESS;
    }

    /**
     * @param int $keep
     * @param string $status
     * @param string[] $excludedContentTypes
     *
     * @return array
     *
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentException
     */
    protected function getObjectsIds($keep, $status, $excludedContentTypes = [])
    {
        $query = $this->connection->createQueryBuilder()
                ->select('c.id')
                ->from(Gateway::CONTENT_ITEM_TABLE, 'c')
                ->join('c', Gateway::CONTENT_VERSION_TABLE, 'v', 'v.contentobject_id = c.id')
                ->join('c', ContentTypeGateway::CONTENT_TYPE_TABLE, 'cl', 'cl.id = c.content_type_id')
                ->groupBy('c.id', 'v.status')
                ->having('count(c.id) > :keep');
        $query->setParameter('keep', $keep);

        if ($status !== self::VERSION_ALL) {
            $query->where('v.status = :status');
            $query->setParameter('status', $this->mapStatusToVersionInfoStatus($status));
        } else {
            $query->andWhere('v.status != :status');
            $query->setParameter('status', $this->mapStatusToVersionInfoStatus(self::VERSION_PUBLISHED));
        }

        if ($excludedContentTypes) {
            $expr = $query->expr();
            $query
                ->andWhere(
                    $expr->notIn(
                        'cl.identifier',
                        ':contentTypes'
                    )
                )->setParameter('contentTypes', $excludedContentTypes, Connection::PARAM_STR_ARRAY);
        }

        $stmt = $query->executeQuery();

        return $stmt->fetchFirstColumn();
    }

    /**
     * @param string $status
     *
     * @return int
     *
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentException
     */
    private function mapStatusToVersionInfoStatus($status)
    {
        if (array_key_exists($status, self::VERSION_STATUS)) {
            return self::VERSION_STATUS[$status];
        }

        throw new InvalidArgumentException(
            'status',
            sprintf(
                'Status %s cannot be mapped to a VersionInfo status.',
                $status
            )
        );
    }
}
