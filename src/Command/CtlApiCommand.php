<?php

namespace Timebug\ApiCtl\Command;

use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Symfony\Component\Console\Input\InputOption;
use Timebug\ApiCtl\ApiParse\ApiParse;

/**
 * @Command()
 */
class CtlApiCommand extends HyperfCommand
{

    protected ?string $name = "apictl:api";

    public function configure()
    {
        parent::configure();
        $this->addOption('api', 'A', InputOption::VALUE_REQUIRED, "api文件名");
        $this->addOption('pool', 'P', InputOption::VALUE_OPTIONAL, "模块", 'default');
    }

    public function handle()
    {
        $apiName = $this->input->getOption('api');
        $pool = $this->input->getOption('pool');
        $apiParse = new ApiParse($apiName, $pool);
        $apiParse->parse();
    }
}