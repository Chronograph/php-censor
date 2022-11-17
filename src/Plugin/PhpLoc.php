<?php

namespace PHPCensor\Plugin;

use PHPCensor;
use PHPCensor\Builder;
use PHPCensor\Model\Build;
use PHPCensor\Model\User;
use PHPCensor\Plugin;
use PHPCensor\ZeroConfigPluginInterface;

/**
 * PHP Loc - Allows PHP Copy / Lines of Code testing.
 *
 * @package    PHP Censor
 * @subpackage Application
 *
 * @author Johan van der Heide <info@japaveh.nl>
 * @author Dmitry Khomutov <poisoncorpsee@gmail.com>
 */
class PhpLoc extends Plugin implements ZeroConfigPluginInterface
{
    /**
     * @return string
     */
    public static function pluginName()
    {
        return 'php_loc';
    }

    /**
     * {@inheritDoc}
     */
    public static function canExecuteOnStage($stage, Build $build)
    {
        if (Build::STAGE_TEST === $stage) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function __construct(Builder $builder, Build $build, array $options = [])
    {
        parent::__construct($builder, $build, $options);

        $this->executable = $this->findBinary(['phploc', 'phploc.phar']);
    }

    /**
     * Runs PHP LOC in a specified directory.
     */
    public function execute()
    {
        $ignore = '';
        if (\is_array($this->ignore)) {
            $map = fn ($item) => \sprintf(' --exclude="%s"', $item);

            $ignore = \array_map($map, $this->ignore);
            $ignore = \implode('', $ignore);
        }

        $phploc = $this->executable;

        $success = $this->builder->executeCommand('cd "%s" && php -d xdebug.mode=off -d error_reporting=0 ' . $phploc . ' %s %s', $this->builder->buildPath, $ignore, $this->directory);
        $output  = $this->builder->getLastOutput();

        if (\preg_match_all('/\((LOC|CLOC|NCLOC|LLOC)\)\s+([0-9]+)/', $output, $matches)) {
            $data = [];
            foreach ($matches[1] as $k => $v) {
                $data[$v] = (int)$matches[2][$k];
            }

            $this->build->storeMeta((self::pluginName() . '-data'), $data);
        }

        return $success;
    }
}
