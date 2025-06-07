<?php

namespace AKH\Plugin\System\CustomDenied\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Language\Text;

final class CustomDenied extends CMSPlugin
{
    public function onAfterRoute()
    {
        $app = Factory::getApplication();

        if (!$app instanceof SiteApplication || $app->isClient('administrator')) {
            return;
        }

        $menu = $app->getMenu();
        $active = $menu->getActive();

        if (!$active) {
            return;
        }

        $user = Factory::getUser();

        if (!in_array($active->access, $user->getAuthorisedViewLevels())) {
            $redirectUrl = Route::_('index.php?option=com_somewhere', false);
            $message = Text::_('PLG_SYSTEM_CUSTOMDENIED_ACCESS_DENIED');

            $app->redirect($redirectUrl, $message, 'error');
            $app->close();
        }
    }
}
