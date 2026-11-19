<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\RepositoryInstaller\Installer;

use Doctrine\DBAL\Connection;
use Ibexa\Contracts\DoctrineSchema\Database\DatabasePlatformResolver;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class DbBasedInstaller
{
    /** @var Connection */
    protected $db;

    /** @var OutputInterface */
    protected $output;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * Copy and override configuration file.
     *
     * @param string $source
     * @param string $target
     */
    protected function copyConfigurationFile(
        $source,
        $target
    ) {
        $fs = new Filesystem();
        $fs->copy($source, $target, true);

        if (!$this->output->isQuiet()) {
            $this->output->writeln("Copied $source to $target");
        }
    }

    protected function runQueriesFromFile($file)
    {
        $queries = array_filter(preg_split('(;\\s*$)m', file_get_contents($file)));

        if (!$this->output->isQuiet()) {
            $this->output->writeln(
                sprintf(
                    '<info>Executing %d queries from <comment>%s</comment> on database <comment>%s</comment></info>',
                    count($queries),
                    $file,
                    $this->db->getDatabase()
                )
            );
        }

        foreach ($queries as $query) {
            $this->db->executeStatement($query);
        }
    }

    protected function getDBMSDataDirectoryName(): string
    {
        $platform = $this->db->getDatabasePlatform();
        $name = DatabasePlatformResolver::resolveName($platform);
        if ($name === null) {
            throw new InvalidArgumentException(
                'platform',
                sprintf('Unsupported database platform: %s', $platform::class)
            );
        }

        return $name->value;
    }
}
