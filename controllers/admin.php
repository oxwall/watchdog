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
 * @package ow_plugins.watchdog.controllers
 * @since 1.0
 */
class WATCHDOG_CTRL_Admin extends ADMIN_CTRL_Abstract
{
    private function getMenu()
    {
        $menuItems = array();

        $item = new BASE_MenuItem();
        $item->setLabel( OW::getLanguage()->text('watchdog', 'admin_status') );
        $item->setUrl( OW::getRouter()->urlForRoute('watchdog.admin_status') );
        $item->setKey( 'status' );
        $item->setIconClass( 'ow_ic_calendar' );
        $item->setOrder( 0 );
        array_push( $menuItems, $item );

        $item = new BASE_MenuItem();
        $item->setLabel( OW::getLanguage()->text('watchdog', 'admin_list') );
        $item->setUrl( OW::getRouter()->urlForRoute('watchdog.admin_white_list') );
        $item->setKey( 'whiteList' );
        $item->setIconClass( 'ow_ic_files' );
        $item->setOrder( 1 );
        array_push( $menuItems, $item );

        return new BASE_CMP_ContentMenu( $menuItems );
    }

    public function __construct()
    {
        parent::__construct();
        
        $this->setPageHeading( OW::getLanguage()->text('watchdog', 'admin_settings_heading') );
        $this->setPageHeadingIconClass( 'ow_ic_gear_wheel' );
    }

    public function status()
    {
        $this->addComponent( 'menu', $this->getMenu() );
        $this->getComponent( 'menu' )->getElement( 'status' )->setActive( true );

        $config = OW::getConfig();
        $statusCongigs = $config->getValues( 'watchdog' );
        
        if ( $statusCongigs[WATCHDOG_BOL_WatchdogService::COUNT_SPAM_ATTEMPT] <= 0 )
        {
            $statusCongigs[WATCHDOG_BOL_WatchdogService::COUNT_SPAM_ATTEMPT] = OW::getLanguage()->text( 'watchdog', 'count_spam_attempt_none' );
        }

        if ( $statusCongigs[WATCHDOG_BOL_WatchdogService::TIME_UPDATE_DATABASE] > 0 )
        {
            $statusCongigs[WATCHDOG_BOL_WatchdogService::TIME_UPDATE_DATABASE] = 
                    UTIL_DateTime::formatDate( $statusCongigs[WATCHDOG_BOL_WatchdogService::TIME_UPDATE_DATABASE] );
        }
         else 
        {
             $statusCongigs[WATCHDOG_BOL_WatchdogService::TIME_UPDATE_DATABASE] = OW::getLanguage()->text( 'watchdog', 'database_not_updated' );
        }

        $this->assign( 'configs', $statusCongigs );
        $this->assign( 'logo', OW::getPluginManager()->getPlugin('watchdog')->getStaticUrl() . 'images/Watchdog_logo.png' );
        OW::getDocument()->addStyleSheet( OW::getPluginManager()->getPlugin('watchdog')->getStaticCssUrl() . 'watchdog.css' );
    }

    public function whiteList()
    {
        $this->addComponent( 'menu', $this->getMenu() );
        $this->getComponent( 'menu' )->getElement( 'whiteList' )->setActive( true );

        $languages = OW::getLanguage();
        
        $addIPForm = new WATCHDOG_FORM_AddNewIp();
        $this->addForm( $addIPForm );
        
        $findIPForm = new WATCHDOG_FORM_FindWhiteIp();
        $this->addForm( $findIPForm );
        
        $this->assign( 'emptyWhiteList', OW::getRouter()->urlFor(__CLASS__, 'truncateTable') );
        $languages->addKeyForJs( 'watchdog', 'confirm_empty_white_list' );
        
        if ( OW::getRequest()->isPost() )
        {
            switch ( $_POST['form_name'] )
            {
                case $addIPForm->getName() :
                    
                    if ( $addIPForm->isValid($_POST) )
                    {
                        $result = $addIPForm->process();

                        switch ( $result['result'] )
                        {
                            case false:
                                OW::getFeedback()->error( $languages->text('watchdog', 'white_ip_exists') );
                                break;

                            case true:
                                OW::getFeedback()->info( $languages->text('watchdog', 'white_ip_added') );
                                break;
                        }
                    }
                    
                    $this->redirect( OW::getRouter()->urlForRoute('watchdog.admin_white_list') );
                    break;
                
                case $findIPForm->getName() :
                    
                    if ( $findIPForm->isValid($_POST) )
                    {
                        $result = $findIPForm->process();

                        switch ( $result['result'] )
                        {
                            case false:
                                OW::getFeedback()->warning( $languages->text('watchdog', 'white_ip_not_exists') );
                                break;

                            case true :
                                $js = '
window.searchIP.floatBox = new OW_FloatBox(
{
    $title: "' . OW::getLanguage()->text( 'watchdog', 'search_result' ) . '",
    $contents: $( "#search-ip-div" ),
    width: 480
});';
                                $this->assign( 'ip', $result['ip'] );
                                OW::getDocument()->addOnloadScript( $js );
                                break;
                        }
                    }
                    break;
            }
        }
    }
    
    public function delete( $params )
    {
        if ( isset($params['id']) )
        {
            WATCHDOG_BOL_WatchdogService::getInstance()->deleteIPById( (int)$params['id'] );
        }
        
        OW::getFeedback()->info( OW::getLanguage()->text('watchdog', 'white_ip_deleted') );
        $this->redirect( OW::getRouter()->urlForRoute('watchdog.admin_white_list') );
    }
    
    public function truncateTable()
    {
        WATCHDOG_BOL_WatchdogService::getInstance()->truncateWhiteListTable();
        OW::getFeedback()->info( OW::getLanguage()->text('watchdog', 'white_list_clear') );
        $this->redirect( OW::getRouter()->urlForRoute('watchdog.admin_white_list') );
    }
}
