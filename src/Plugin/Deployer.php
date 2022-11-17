<?php

namespace PHPCensor\Plugin;

use GuzzleHttp\Client;
use PHPCensor\Builder;
use PHPCensor\Model\Build;
use PHPCensor\Plugin;

/**
 * Integration with Deployer: https://github.com/rebelinblue/deployer
 *
 * @package    PHP Censor
 * @subpackage Application
 *
 * @author Dan Cryer <dan@block8.co.uk>
 * @author Dmitry Khomutov <poisoncorpsee@gmail.com>
 */
class Deployer extends Plugin
{
    protected $webhookUrl;
    protected $reason = 'PHP Censor Build #%BUILD_ID% - %COMMIT_MESSAGE%';
    protected $updateOnly;

    /**
     * @return string
     */
    public static function pluginName()
    {
        return 'deployer';
    }

    /**
     * {@inheritDoc}
     */
    public function __construct(Builder $builder, Build $build, array $options = [])
    {
        parent::__construct($builder, $build, $options);

        if (isset($options['webhook_url'])) {
            $this->webhookUrl = $options['webhook_url'];
        }

        if (isset($options['reason'])) {
            $this->reason = $options['reason'];
        }

        $this->updateOnly = isset($options['update_only']) ? (bool)$options['update_only'] : true;
    }

    /**
    * Copies files from the root of the build directory into the target folder
    */
    public function execute()
    {
        if (empty($this->webhookUrl)) {
            $this->builder->logFailure('You must specify a webhook URL.');

            return false;
        }

        $client   = new Client();
        $response = $client->post(
            $this->webhookUrl,
            [
                'form_params' => [
                    'reason'      => $this->builder->interpolate($this->reason, true),
                    'source'      => 'PHP Censor',
                    'url'         => $this->builder->interpolate('%BUILD_LINK%', true),
                    'branch'      => $this->builder->interpolate('%BRANCH%', true),
                    'commit'      => $this->builder->interpolate('%COMMIT_ID%', true),
                    'update_only' => $this->updateOnly,
                ]
            ]
        );

        $status = (int)$response->getStatusCode();

        return (
            ($status >= 200 && $status < 300)
                ? true
                : false
        );
    }
}
