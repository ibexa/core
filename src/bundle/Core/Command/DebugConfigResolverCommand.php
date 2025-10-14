<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Command;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

#[AsCommand(
    name: 'ibexa:debug:config-resolver',
    description: 'Debugs / Retrieves a parameter from the Config Resolver',
    aliases: ['ibexa:debug:config']
)]
class DebugConfigResolverCommand extends Command
{
    /** @var \Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface */
    private $configResolver;

    /** @var \Ibexa\Core\MVC\Symfony\SiteAccess */
    private $siteAccess;

    public function __construct(
        ConfigResolverInterface $configResolver,
        SiteAccess $siteAccess
    ) {
        $this->configResolver = $configResolver;
        $this->siteAccess = $siteAccess;

        parent::__construct();
    }

    /**
     * {@inheritdoc}.
     */
    public function configure(): void
    {
        $this->addArgument(
            'parameter',
            InputArgument::REQUIRED,
            'The configuration resolver parameter to return, for instance "languages" or "http_cache.purge_servers"'
        );
        $this->addOption(
            'json',
            null,
            InputOption::VALUE_NONE,
            'Only return value, for automation / testing use on a single line in json format.'
        );
        $this->addOption(
            'scope',
            null,
            InputOption::VALUE_REQUIRED,
            'Set another scope (SiteAccess) to use. This is an alternative to using the global --siteaccess[=SITEACCESS] option.'
        );
        $this->addOption(
            'namespace',
            null,
            InputOption::VALUE_REQUIRED,
            'Set a different namespace than the default "ibexa.site_access.config" used by SiteAccess settings.'
        );
        $this->addOption(
            'sort',
            null,
            InputOption::VALUE_REQUIRED,
            'Sort list of hashes by this key, ascending. For example: --sort position'
        );
        $this->addOption(
            'reverse-sort',
            null,
            InputOption::VALUE_NONE,
            'Reverse the sorting to descending. For example: --sort priority --reverse-sort'
        );
        $this->setHelp(
            <<<EOM
Outputs a given config resolver parameter, more commonly known as a SiteAccess setting.

By default it will give value depending on the global <comment>--siteaccess[=SITEACCESS]</comment> (default SiteAccess is used if not set).

However, you can also manually set <comment>--scope[=NAME]</comment> yourself if you don't want to affect the SiteAccess
set by the system. You can also override the namespace to get something other than the default "ibexa.site_access.config" namespace by using
the <comment>--namespace[=NS]</comment> option.

NOTE: To see *all* compiled SiteAccess settings, use: <comment>debug:config ibexa [system.default]</comment>

EOM
        );
    }

    /**
     * {@inheritdoc}.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $parameter = $input->getArgument('parameter');
        $namespace = $input->getOption('namespace');
        $scope = $input->getOption('scope');
        $sort = $input->getOption('sort');
        $parameterData = $this->configResolver->getParameter($parameter, $namespace, $scope);

        if (null !== $sort && !empty($parameterData)) {
            if (!is_array($parameterData)) {
                throw new InvalidArgumentException('--sort', "'$parameter' isn't a list. Sort can be used only on a list.");
            }
            if (!array_is_list($parameterData)) {
                throw new InvalidArgumentException('--sort', "'$parameter' is a hash but sort can be used only on a list (an array with numeral keys incremented from zero).");
            }
            for ($i=0, $count = count($parameterData); $i < $count; $i++) {
                if (!array_key_exists($sort, $parameterData[$i])) {
                    throw new InvalidArgumentException('--sort', "'$sort' property doesn't exist on each '$parameter' list item.");
                }
                if (!is_scalar($parameterData[$i][$sort])) {
                    throw new InvalidArgumentException('--sort', "'$sort' properties aren't always scalar and can't be sorted.");
                }
            }
            if ($input->getOption('reverse-sort')) {
                usort($parameterData, static fn (array $a, array $b): int => $b[$sort] <=> $a[$sort]);
            } else {
                usort($parameterData, static fn (array $a, array $b): int => $a[$sort] <=> $b[$sort]);
            }
        }

        // In case of json output return early with no newlines and only the parameter data
        if ($input->getOption('json')) {
            $output->write(json_encode($parameterData));

            return self::SUCCESS;
        }

        $output->writeln('<comment>SiteAccess name:</comment> ' . $this->siteAccess->name);

        $output->writeln('<comment>Parameter:</comment>');
        $cloner = new VarCloner();
        $dumper = new CliDumper();
        $output->write(
            $dumper->dump(
                $cloner->cloneVar($parameterData),
                true
            )
        );

        return self::SUCCESS;
    }
}
