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

    protected $name = "apictl:api";

    public function configure()
    {
        parent::configure();
        $this->addOption('api', 'A', InputOption::VALUE_REQUIRED, "api文件名");
    }

    public function handle()
    {
        $apiName = $this->input->getOption('api');
        $apiParse = new ApiParse($apiName);
        $apiParse->parse();
    }
}