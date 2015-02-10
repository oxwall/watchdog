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

/**
 * 
 * 
 * @author Kairat Bakitow <kainisoft@gmail.com>
 * @package ow_plugins.watchdog.bol
 * @since 1.0
 */
class WATCHDOG_BOL_WatchdogService
{
    /* IP Black List */
    const ZIP_IP_LIST = 'listed_ip_90.zip';
    const TXT_IP_LIST = 'listed_ip_90.txt';
    
    /* E-mail Black List */
    const ZIP_EMAIL_LIST = 'listed_email_30.zip';
    const TXT_EMAIL_LIST = 'listed_email_30.txt';
    
    /* Install */
    const INSTALL_EMAIL_ZIP = 'listed_email_7.zip';
    const INSTALL_EMAIL_TXT = 'listed_email_7.txt';
    
    /* Config section */
    const COUNT_BLACK_LIST        = 'count_black_list';
    const COUNT_EMAIL_LIST        = 'count_email_list';
    const COUNT_SPAM_ATTEMPT      = 'count_spam_attempt';
    const TIME_UPDATE_DATABASE    = 'time_update_database';
    const TIME_NEXT_UPDATE        = 'time_next_update';
    const TIME_RESET_SPAM_ATTEMPT = 'time_reset_spam_attempt';

    const DATABASE_DUMP_STOPFORUM = 'http://www.stopforumspam.com/downloads/';
    const DATABASE_DUMP_AMAZON    = 'http://ow.antispam.s3.amazonaws.com/';

    private static $classInstance;
    
    private $plugin;
    private $watchdogBlackListDao;
    private $watchdogWhiteListDao;
    private $watchdogEmailDao;

    public static function getInstance()
    {
        if ( !isset(self::$classInstance) )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private function __construct()
    {
        $this->plugin = OW::getPluginManager()->getPlugin( 'watchdog' );
        $this->watchdogWhiteListDao = WATCHDOG_BOL_WatchdogWhiteListDao::getInstance();
        $this->watchdogBlackListDao = WATCHDOG_BOL_WatchdogBlackListDao::getInstance();
        $this->watchdogEmailDao = WATCHDOG_BOL_WatchdogEmailDao::getInstance();
    }
    
    public function exctractFile( WATCHDOG_BOL_EnumBlackList $enumBlackList )
    {
        switch ( $enumBlackList->getCurrentVal() )
        {
            case WATCHDOG_BOL_EnumBlackList::IP_BLACKLIST :
                $fileName = WATCHDOG_BOL_WatchdogService::ZIP_IP_LIST;
                $fileTxt = WATCHDOG_BOL_WatchdogService::TXT_IP_LIST;
                break;
                
            case WATCHDOG_BOL_EnumBlackList::EMAIL_BLACKLIST :
                $fileName = WATCHDOG_BOL_WatchdogService::ZIP_EMAIL_LIST;
                $fileTxt = WATCHDOG_BOL_WatchdogService::TXT_EMAIL_LIST;
                break;
            
            case WATCHDOG_BOL_EnumBlackList::INSTALL_EMAIL :
                $fileName = WATCHDOG_BOL_WatchdogService::INSTALL_EMAIL_ZIP;
                $fileTxt = WATCHDOG_BOL_WatchdogService::INSTALL_EMAIL_TXT;
                break;
        }
        
        
        
        $zip = new ZipArchive();
        $zip->open( $this->plugin->getPluginFilesDir() . $fileName );
        $zip->extractTo( $this->plugin->getPluginFilesDir() );
        $zip->close();
    }
    
    public function insertFromStopForum( WATCHDOG_BOL_EnumBlackList $enumBlackList )
    {
        switch ( $enumBlackList->getCurrentVal() )
        {
            case WATCHDOG_BOL_EnumBlackList::IP_BLACKLIST :
                $fileName = WATCHDOG_BOL_WatchdogService::TXT_IP_LIST;
                $dao = $this->watchdogBlackListDao;
                $filter = FILTER_VALIDATE_IP;
                break;
            
            case WATCHDOG_BOL_EnumBlackList::EMAIL_BLACKLIST :
                $fileName = WATCHDOG_BOL_WatchdogService::TXT_EMAIL_LIST;
                $dao = $this->watchdogEmailDao;
                $filter = FILTER_VALIDATE_EMAIL;
                break;
            
            case WATCHDOG_BOL_EnumBlackList::INSTALL_EMAIL :
                $fileName = WATCHDOG_BOL_WatchdogService::INSTALL_EMAIL_TXT;
                $dao = $this->watchdogEmailDao;
                $filter = FILTER_VALIDATE_EMAIL;
                break;
        }
        
        $fileHandler = fopen( $this->plugin->getPluginFilesDir() . $fileName, 'r' );

        $i = 1;
        $data = array();

        while ( $buffer = fgets($fileHandler) )
        {
            $buffer = trim( $buffer, "\n\r" );
            
            if ( !filter_var($buffer, $filter) )
            {
                continue;
            }
            
            array_push( $data, $buffer );

            if ($i % 500 == 0)
            {
                $dao->insert( $data );
                unset( $data );
                $data = array();
            }

            $i++;
        }

        $dao->insert( $data );
    }

    public function setCountBlackList( WATCHDOG_BOL_EnumBlackList $enumBlackList )
    {
        switch ( $enumBlackList->getCurrentVal() )
        {
            case WATCHDOG_BOL_EnumBlackList::IP_BLACKLIST :
                OW::getConfig()->saveConfig( $this->plugin->getKey(), self::COUNT_BLACK_LIST, $this->watchdogBlackListDao->countAll() );
                break;
            
            case WATCHDOG_BOL_EnumBlackList::EMAIL_BLACKLIST :
            case WATCHDOG_BOL_EnumBlackList::INSTALL_EMAIL :
                OW::getConfig()->saveConfig( $this->plugin->getKey(), self::COUNT_EMAIL_LIST, $this->watchdogEmailDao->countAll() );
                break;
        }
    }
    
    public function checkRemoteIP()
    {
        $whiteIP = $this->watchdogWhiteListDao->findIP( OW::getRequest()->getRemoteAddress() );
        
        if ( isset($whiteIP) && $whiteIP instanceof WATCHDOG_BOL_Ip )
        {
            return;
        }
        
        $blackIP = $this->watchdogBlackListDao->findIP( OW::getRequest()->getRemoteAddress() );
        
        if ( isset($blackIP) && $blackIP instanceof WATCHDOG_BOL_Ip )
        {
            $this->catchRequest();
            return;
        }
        
        if ( OW::getUser()->isAuthenticated() )
        {
            $blackEmail = $this->watchdogEmailDao->findByEmail( OW::getUser()->getEmail() );
        }
        else 
        {
            if ( OW::getRequest()->isPost() && !empty($_POST['email']) )
            {
                $blackEmail = $this->watchdogEmailDao->findByEmail( $_POST['email'] );
            }
        }
        
        if ( isset($blackEmail) && $blackEmail instanceof WATCHDOG_BOL_Email )
        {
            $this->catchRequest();
        }
    }
    
    private function catchRequest()
    {
        OW::getRequestHandler()->setCatchAllRequestsAttributes( 'watchdog.spam-attempt', array(
            OW_RequestHandler::CATCH_ALL_REQUEST_KEY_CTRL => 'WATCHDOG_CTRL_Watchdog', 
            OW_RequestHandler::CATCH_ALL_REQUEST_KEY_ACTION => 'spamAttempt'
        ) );        
        OW::getRequestHandler()->addCatchAllRequestsExclude( 'watchdog.spam-attempt', 'BASE_CTRL_User', 'signOut' );
        
        $this->incSpamAttempt();
    }

    private function incSpamAttempt()
    {
        $count = OW::getConfig()->getValue( $this->plugin->getKey(), self::COUNT_SPAM_ATTEMPT );
        OW::getConfig()->saveConfig( $this->plugin->getKey(), self::COUNT_SPAM_ATTEMPT, ++$count );
    }
    
    public function findWhiteIP( $ip )
    {
        return $this->watchdogWhiteListDao->findIP( $ip );
    }
    
    public function addNewWhiteIPAddr( $ip )
    {
        return $this->watchdogWhiteListDao->addNewIPAddr( $ip );
    }

    public function deleteIPById( $id )
    {
        return $this->watchdogWhiteListDao->deleteById( $id );
    }

    public function truncateWhiteListTable()
    {
        return $this->watchdogWhiteListDao->truncateTable();
    }
    
    public function resetSpamAttempt()
    {
        OW::getConfig()->saveConfig( $this->plugin->getKey(), self::COUNT_SPAM_ATTEMPT, 0 );
        OW::getConfig()->saveConfig( $this->plugin->getKey(), self::TIME_RESET_SPAM_ATTEMPT, strtotime('+1 day 00:00:00') );
    }
    
    public function truncateList( WATCHDOG_BOL_EnumBlackList $enumBlackList )
    {
        switch ( $enumBlackList->getCurrentVal() )
        {
            case WATCHDOG_BOL_EnumBlackList::IP_BLACKLIST :
                return $this->watchdogBlackListDao->truncateTable();
            
            case WATCHDOG_BOL_EnumBlackList::EMAIL_BLACKLIST :
                return $this->watchdogEmailDao->truncateTable();
        }
        
    }

    public function updateDatabaseFromStopForum()
    {
        OW::getConfig()->saveConfig( $this->plugin->getKey(), WATCHDOG_BOL_WatchdogService::TIME_NEXT_UPDATE, strtotime('now 00:00:00') + 86400 + rand(1, 86400) );
        
        $this->prepareDirToUpdate();
        
        $enumIPBlackList = new WATCHDOG_BOL_EnumBlackList( 'IP_BLACKLIST' );

        if ( $this->downloadDatabaseStopForum($enumIPBlackList) )
        {
            $this->truncateList( $enumIPBlackList );
            $this->exctractFile( $enumIPBlackList );
            $this->insertFromStopForum( $enumIPBlackList );
            $this->setCountBlackList( $enumIPBlackList );
            $this->setTimeUpdateDatabase();
            
        }

        $enumEmailBlackList = new WATCHDOG_BOL_EnumBlackList( 'EMAIL_BLACKLIST' );
        
        if ( $this->downloadDatabaseStopForum($enumEmailBlackList) )
        {
            $this->truncateList( $enumEmailBlackList );
            $this->exctractFile( $enumEmailBlackList );
            $this->insertFromStopForum( $enumEmailBlackList );
            $this->setCountBlackList( $enumEmailBlackList );
            $this->setTimeUpdateDatabase();
        }
    }

    private function downloadDatabaseStopForum(WATCHDOG_BOL_EnumBlackList $enumBlackList )
    {
        switch ( $enumBlackList->getCurrentVal() )
        {
            case WATCHDOG_BOL_EnumBlackList::IP_BLACKLIST :
                $fileName = self::ZIP_IP_LIST;
                break;
            
            case WATCHDOG_BOL_EnumBlackList::EMAIL_BLACKLIST :
                $fileName = self::ZIP_EMAIL_LIST;
                break;
        }
        
        copy( self::DATABASE_DUMP_STOPFORUM . $fileName, $this->plugin->getPluginFilesDir() . $fileName );
        
        if ( file_exists($this->plugin->getPluginFilesDir() . $fileName) && 
                filesize($this->plugin->getPluginFilesDir() . $fileName) > 512000 )
        {
            return true;
        }
        else
        {
            $this->prepareDirToUpdate();
            copy( self::DATABASE_DUMP_AMAZON . $fileName, $this->plugin->getPluginFilesDir() . $fileName );
            
            if ( file_exists($this->plugin->getPluginFilesDir() . $fileName) && 
                    filesize($this->plugin->getPluginFilesDir() . $fileName) > 512000 )
            {
                return true;
            }
            else
            {
                trigger_error( 'Can`t download ' . $fileName . ' file!' , E_ERROR );
                return false;
            }
        }
    }
    
    private function setTimeUpdateDatabase()
    {
        OW::getConfig()->saveConfig( $this->plugin->getKey(), self::TIME_UPDATE_DATABASE, time() );
    }
    
    private function prepareDirToUpdate()
    {
        $dir = opendir( $this->plugin->getPluginFilesDir() );
        
        while ( ($file = readdir($dir)) !== false )
        {
            if ( in_array($file, array('.', '..')) )
            {
                continue;
            }
            
            unlink( $this->plugin->getPluginFilesDir() . $file );
        }
        
        closedir( $dir );
    }
}
