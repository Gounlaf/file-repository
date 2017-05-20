#!/usr/bin/env php
<?php

/**
 * Interactive console
 * -------------------
 */

// @codeCoverageIgnoreStart
require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * @var \Silex\Application $app
 */
$app = require __DIR__ . '/../src/app.php';

$console = new ConsoleApplication('Wolnościowiec Image Repository', 'n/a');
$optionEnv = new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev');

$console->getDefinition()->addOption($optionEnv);

try {
    // ArgvInput is created here just for retrieving "env" parameter
    // When calling console commands, it will produce an exception, since they are not yet configured
    // (and so they are not recognized)
    $input  = new ArgvInput(null, new InputDefinition([$optionEnv]));
    $output = new ConsoleOutput();
    $env    = $input->getOption('env');
} catch (Exception $e) {
    $env = 'dev';
}

$configPath = __DIR__ . '/../config/' . $env . '.php';
if (!is_file($configPath) || !is_readable($configPath) && $env !== 'dev') {
    $output->getErrorOutput()->writeln(sprintf('Unknown env %s', $env));;
    exit();
}

define('ENV', $env);

require $configPath;
require __DIR__ . '/../src/services.php';
require __DIR__ . '/../src/controllers.php';

foreach (scandir(__DIR__ . '/Commands') as $fileName) {
    if (substr($fileName, -11) === 'Command.php') {
        $commandName = substr($fileName, 0, -4);
        $className = '\\Commands\\' . $commandName;

        if ($commandName === 'BaseCommand') {
            continue;
        }

        /* @var $command \Commands\BaseCommand */
        $command = new $className();
        $command->setApp($app);

        $console->add($command);
    }
}

$app->register(
    new \Kurl\Silex\Provider\DoctrineMigrationsProvider($console),
    array(
        'migrations.directory' => __DIR__ . '/../migrations',
        'migrations.name' => 'File Repository Migrations',
        'migrations.namespace' => 'Db\Migrations',
        'migrations.table_name' => 'core_migrations',
    )
);

$app->boot();

$console->run();
// @codeCoverageIgnore

