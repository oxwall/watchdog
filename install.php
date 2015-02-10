<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

$config = OW::getConfig();
$plugin = OW::getPluginManager()->getPlugin( 'watchdog' );
$pluginKey = strtolower( $plugin->getKey() );

OW::getAutoloader()->addPackagePointer( 'WATCHDOG_BOL', $plugin->getBolDir() );

OW::getPluginManager()->addPluginSettingsRouteName( 'watchdog', 'watchdog.admin_status' );

if ( !$config->configExists($pluginKey, WATCHDOG_BOL_WatchdogService::COUNT_SPAM_ATTEMPT) )
{
    $config->addConfig( $pluginKey, WATCHDOG_BOL_WatchdogService::COUNT_SPAM_ATTEMPT, 0 );
}

if ( !$config->configExists($pluginKey, WATCHDOG_BOL_WatchdogService::COUNT_BLACK_LIST) )
{
    $config->addConfig( $pluginKey, WATCHDOG_BOL_WatchdogService::COUNT_BLACK_LIST, 0 );
}

if ( !$config->configExists($pluginKey, WATCHDOG_BOL_WatchdogService::COUNT_EMAIL_LIST) )
{
    $config->addConfig($pluginKey, WATCHDOG_BOL_WatchdogService::COUNT_EMAIL_LIST, 0 );
}

if ( !$config->configExists($pluginKey, WATCHDOG_BOL_WatchdogService::TIME_UPDATE_DATABASE))
{
    $config->addConfig( $pluginKey, WATCHDOG_BOL_WatchdogService::TIME_UPDATE_DATABASE, 0 );
}

if ( !$config->configExists($pluginKey, WATCHDOG_BOL_WatchdogService::TIME_NEXT_UPDATE) )
{
    $config->addConfig($pluginKey, WATCHDOG_BOL_WatchdogService::TIME_NEXT_UPDATE, strtotime('+1 day 00:00:00') + rand(1, 86400) );
}

if ( !$config->configExists($pluginKey, WATCHDOG_BOL_WatchdogService::TIME_RESET_SPAM_ATTEMPT) )
{
    $config->addConfig($pluginKey, WATCHDOG_BOL_WatchdogService::TIME_RESET_SPAM_ATTEMPT, strtotime('+1 day 00:00:00') );
}

unset( $config );

OW::getLanguage()->importPluginLangs( $plugin->getRootDir() . 'langs.zip', $pluginKey, true );

$prefix = OW_DB_PREFIX;

/* ****************************** IP Black ist ****************************** */

$query = "
CREATE TABLE IF NOT EXISTS `{$prefix}watchdog_black_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(15) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ";
OW::getDbo()->query( $query );
        
$query = "
CREATE TABLE IF NOT EXISTS `{$prefix}watchdog_white_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(15) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ";
OW::getDbo()->query( $query );

$watchdogService = WATCHDOG_BOL_WatchdogService::getInstance();

require_once OW_DIR_PLUGIN . 'watchdog' . DS . 'alloc.php';

//copy( $plugin->getStaticUrl() . WATCHDOG_BOL_WatchdogService::ZIP_IP_LIST,
//        $plugin->getPluginFilesDir() . WATCHDOG_BOL_WatchdogService::ZIP_IP_LIST );
$enumIPBlackList = new WATCHDOG_BOL_EnumBlackList( 'IP_BLACKLIST' );
$watchdogService->exctractFile( $enumIPBlackList );
$watchdogService->insertFromStopForum( $enumIPBlackList );
$watchdogService->setCountBlackList( $enumIPBlackList );

unset( $enumIPBlackList );

/* **************************** E-mail Black List *************************** */

$query = "
CREATE TABLE IF NOT EXISTS `{$prefix}watchdog_email_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
OW::getDbo()->query( $query );

//copy( $plugin->getStaticUrl() . WATCHDOG_BOL_WatchdogService::INSTALL_EMAIL_ZIP,
//        $plugin->getPluginFilesDir() . WATCHDOG_BOL_WatchdogService::INSTALL_EMAIL_ZIP );
$enumEmailBlackList = new WATCHDOG_BOL_EnumBlackList( 'INSTALL_EMAIL' );
$watchdogService->exctractFile( $enumEmailBlackList );
$watchdogService->insertFromStopForum( $enumEmailBlackList );
$watchdogService->setCountBlackList( $enumEmailBlackList );

@unlink( $plugin->getPluginFilesDir() . WATCHDOG_BOL_WatchdogService::ZIP_IP_LIST );
@unlink( $plugin->getPluginFilesDir() . WATCHDOG_BOL_WatchdogService::TXT_IP_LIST );
@unlink( $plugin->getPluginFilesDir() . WATCHDOG_BOL_WatchdogService::INSTALL_EMAIL_TXT );
@unlink( $plugin->getPluginFilesDir() . WATCHDOG_BOL_WatchdogService::INSTALL_EMAIL_ZIP );

@chmod( $plugin->getPluginFilesDir(), 0777 );

unset( $enumEmailBlackList, $watchdogService, $plugin );
