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
 * @package ow_plugins.watchdog.forms
 * @since 1.0
 */
abstract class WATCHDOG_FORM_Watchdog extends Form
{
    public function __construct( $name )
    {
        parent::__construct( $name );

        $field = new IP_TextField( 'ip' );
        $field->setRequired( true );
        $field->addValidator( new IP_Validator() );
        $field->setInvitation( OW::getLanguage()->text('watchdog', 'ip_address') );
        $field->setHasInvitation( true );
        $field->backInvitationOnBlur();
        
        $this->addElement( $field );
    }
    
    abstract function process();
}

class IP_TextField extends TextField
{
    public function backInvitationOnBlur()
    {
        $js = '
$( "#' . $this->getAttribute('id') . '" ).blur( function()
{
    if ( $(this).val() == "" )
    {
        $( this ).addClass( "invitation" );
        $( this ).val( ' . json_encode( $this->getInvitation() ) . ' );
    }
});';
        OW::getDocument()->addOnloadScript( $js );
    }
}

class IP_Validator extends OW_Validator
{
    public function __construct()
    {
        $this->setErrorMessage( OW::getLanguage()->text('watchdog', 'invalid_ip_addr') );
    }

    public function isValid( $value )
    {
        return ( !empty($value) && filter_var($value, FILTER_VALIDATE_IP) );
    }

    public function getJsValidator()
    {
        return "
{
    validate : function( value )
    {
        // doesn't check empty values
        if( !value || $.trim( value ).length == 0 )
        {
            return;
        }

        this.checkValue( value );
    },

    getErrorMessage : function()
    {
        return " . json_encode( $this->getError() ) . "
    },
    
    checkValue : function( value )
    {
        var pattern = /\b(([01]?\d?\d|2[0-4]\d|25[0-5])\.){3}([01]?\d?\d|2[0-4]\d|25[0-5])\b/;
    		
        if( !pattern.test( value ) )
        {
            throw " . json_encode( $this->getError() ) . ";
        }
    }
}";
    }
}
