<?php

namespace NuvoleWeb\Robo\Task\Config;

use Robo\Robo;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Robo\Config\YamlConfigLoader;
use Robo\Config\ConfigProcessor;
use Symfony\Component\Yaml\Yaml;

/**
 * Class loadTasks.
 *
 * @package NuvoleWeb\Robo\Task\Config
 */
trait loadTasks {

  /**
   * Add default options.
   *
   * @param \Symfony\Component\Console\Command\Command $command
   *   Command object.
   *
   * @hook option
   */
  public function defaultOptions(Command $command) {
    $command->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file to be used instead of default `robo.yml.dist`.', 'robo.yml');
    $command->addOption('override', 'o', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Configuration value(s) to be overridden. Format: "path.to.key:value"', []);
  }

  /**
   * Command initialization.
   *
   * @param \Symfony\Component\Console\Input\Input $input
   *   Input object.
   *
   * @hook pre-init
   */
  public function initializeConfiguration(Input $input) {

    // Initialize configuration objects.
    $config = Robo::config();
    $loader = new YamlConfigLoader();
    $processor = new ConfigProcessor();

    // Extend and import configuration.
    $processor->extend($loader->load('robo.yml.dist'));
    $processor->extend($loader->load($input->getOption('config')));
    $config->import($processor->export());

    // Replace tokens in final configuration file.
    $export = $processor->export();
    array_walk_recursive($export, function (&$value, $key) use ($config) {
      $value = (string) $value;
      if (!empty($value) && $value[0] == '!') {
        $value = substr($value, 1, strlen($value));
        $value = $config->get($value);
      }
      return $value;
    });
    $config->import($export);

    // Process command line overrides.
    foreach ($input->getOption('override') as $override) {
      $override = (array) Yaml::parse($override);
      $config->set(key($override), array_shift($override));
    }
  }

  /**
   * Fetch a configuration value.
   *
   * @param string $key
   *   Which config item to look up.
   *
   * @return mixed
   *   Configuration value.
   */
  protected function config($key) {
    return Robo::config()->get($key);
  }

}