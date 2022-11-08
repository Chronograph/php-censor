<?php

namespace PHPCensor\Plugin;

use Exception;
use PHPCensor\Builder;
use PHPCensor\Common\Exception\RuntimeException;
use PHPCensor\Model\Build;
use PHPCensor\Plugin;

/**
 * Integrates PHPCensor with Mage: https://github.com/andres-montanez/Magallanes
 *
 * @package    PHP Censor
 * @subpackage Application
 *
 * @author Dmitry Khomutov <poisoncorpsee@gmail.com>
 */
class Mage extends Plugin
{
    protected $mageEnv;

    /**
     * {@inheritDoc}
     */
    public static function pluginName()
    {
        return 'mage';
    }

    /**
     * {@inheritDoc}
     */
    public function __construct(Builder $builder, Build $build, array $options = [])
    {
        parent::__construct($builder, $build, $options);

        $this->executable = $this->findBinary(['mage', 'mage.phar']);

        if (isset($options['env'])) {
            $this->mageEnv = $builder->interpolate($options['env'], true);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        if (empty($this->mageEnv)) {
            $this->builder->logFailure('You must specify environment.');

            return false;
        }

        $result = $this->builder->executeCommand($this->executable . ' deploy to:' . $this->mageEnv);

        try {
            $this->builder->log('########## MAGE LOG BEGIN ##########');
            $this->builder->log($this->getMageLog());
            $this->builder->log('########## MAGE LOG END ##########');
        } catch (\Throwable $e) {
            $this->builder->logFailure($e->getMessage());
        }

        return $result;
    }

    /**
     * Get mage log lines
     * @return array
     * @throws Exception
     */
    protected function getMageLog()
    {
        $logsDir = $this->build->getBuildPath() . '/.mage/logs';
        if (!\is_dir($logsDir)) {
            throw new RuntimeException('Log directory not found');
        }

        $list = \scandir($logsDir);
        if ($list === false) {
            throw new RuntimeException('Log dir read fail');
        }

        $list = \array_filter($list, fn($name) => \preg_match('/^log-\d+-\d+\.log$/', $name));
        if (empty($list)) {
            throw new RuntimeException('Log dir filter fail');
        }

        $res = \sort($list);
        if ($res === false) {
            throw new RuntimeException('Logs sort fail');
        }

        $lastLogFile = \end($list);
        if ($lastLogFile === false) {
            throw new RuntimeException('Get last Log name fail');
        }

        $logContent = \file_get_contents($logsDir . '/' . $lastLogFile);
        if ($logContent === false) {
            throw new RuntimeException('Get last Log content fail');
        }

        $lines = \explode("\n", $logContent);
        $lines = \array_map('trim', $lines);

        return \array_filter($lines);
    }
}
