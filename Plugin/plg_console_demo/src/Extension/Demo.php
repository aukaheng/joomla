<?php

namespace AKH\Plugin\Console\Demo\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Application\ApplicationEvents;
use Joomla\Database\DatabaseAwareTrait;
use AKH\Plugin\Console\Demo\CliCommand\DoSomething;

final class Demo extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            ApplicationEvents::BEFORE_EXECUTE => 'registerCommands'
        ];
    }

    public function registerCommands(): void
    {
        $this->getApplication()->addCommand(new DoSomething($this->getDatabase()));
    }
}
