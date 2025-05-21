<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  [PLUGIN_NAME]
 * @author      jumbo125
 * @copyright   Copyright (C) 2025 jumbo125. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 */

\defined('_JEXEC') or die;



use Joomla\CMS\Factory;

use Joomla\CMS\Extension\PluginInterface;

use Joomla\CMS\Plugin\PluginHelper;

use Joomla\DI\Container;

use Joomla\DI\ServiceProviderInterface;

use Joomla\Event\DispatcherInterface;

use Joomla\Plugin\System\Baohoneypotar\Plugin;



return new class implements ServiceProviderInterface

{

    public function register(Container $container)

    {

        $container->set(

            PluginInterface::class,

            function (Container $container) {

                // Konfiguration (params etc.) aus der DB holen

                $config = (array) PluginHelper::getPlugin('system', 'baohoneypotar');



                // Event-Dispatcher holen

                $dispatcher = $container->get(DispatcherInterface::class);



                // Plugin-Instanz erzeugen mit Dispatcher + Config

                $plugin = new Plugin($dispatcher, $config);



                // Application zuweisen (f�r $this->app in CMSPlugin)

                $plugin->setApplication(Factory::getApplication());



                return $plugin;

            }

        );

    }

};

