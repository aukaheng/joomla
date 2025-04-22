<?php

namespace AKH\Plugin\Authentication\OpenIDDemo\Extension;

use Joomla\Authentication\Exception\UnsupportedPasswordHandlerException;
use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\DI\Exception\KeyNotFoundException;
use Random\RandomException;

\defined('_JEXEC') or die;

final class OpenIDDemo extends CMSPlugin
{
    use DatabaseAwareTrait;

    public function onUserAuthenticate(array $credentials, array $options, &$response): void
    {
    }
}
