<?php
/*
 * Copyright 2013 Michał Zubkowicz <michal.zubkowicz@gmail.com>
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 */

define('ROUTE_BASIC', 'Basic');
define('ROUTE_GOLD', 'gold');
define('ROUTE_DIRECT', 'direct');

define('MESSAGETYPE_UNICODE', 'unicode');
define('MESSAGETYPE_BINARY', 'binary');
define('MESSAGETYPE_FLASH', 'flash');
define('MESSAGETYPE_VOICE', 'voice');

interface StradeInterface
{
    public function setSendDate(DateTime $d);

    public function setDebug($d);

    public function setMessageId($m);

    public function setRoute($route);

    public function setConcat($c);

    public function setResponse($r);

    public function setCharset($c);

    public function setFrom($from);

    public function setDlr($d);

    public function send($to, $message);

}

abstract class Strade implements StradeInterface
{
    var $from = 'SMSTRADE'; //only for  Gold and Direct SMS
    var $route = ROUTE_BASIC;
    var $key = '';
    var $debug = 0; //Activation of debugging mode
    var $message_id = 1; //Output of message ID
    var $response = 0; //Activation of reply SMS
    var $concat = 1; //Sending as linked (longer) SMS
    var $charset = 'UTF-8'; //Message encoding ATTENTION: This is NOT encoding in SMS.
    var $senddate = null;
    var $messagetype = null;
    var $dlr = null;

    private function parseBool($b)
    {
        return ($b ? 1 : 0);
    }

    public function __construct($apikey)
    {
        $this->key = $apikey;
    }

    public function setSendDate(DateTime $d)
    {
        $this->senddate = $d->getTimestamp();
    }

    public function setDebug($d)
    {
        $d = $this->parseBool($d);
        $this->debug = $d;
    }

    public function setDlr($b)
    {
        $this->dlr = $this->parseBool($b);
    }

    public function setMessageId($b)
    {
        $this->message_id = $this->parseBool($b);
    }

    public function setRoute($route)
    {
        $this->route = $route;
    }

    public function setConcat($b)
    {
        $this->concat = $this->parseBool($b);
    }

    public function setResponse($b)
    {
        $this->response = $this->parseBool($b);
    }

    public function setCharset($c)
    {
        $this->charset = $c;
    }

    public function setFrom($from)
    {
        $this->from = $from;
    }
}

class StradeSoap extends Strade
{
    public function __construct($apikey)
    {
        ini_set('soap.wsdl_cache', 0);
        parent::__construct($apikey);
    }

    public function send($to, $message)
    {
        $options = array_filter(get_object_vars($this));
        $options["message"] = $message;
        $options["to"] = $to;
        try {
            $client = new SoapClient('https://gateway.smstrade.de/soap/index.php?wsdl');
            foreach ($options as $key => $value) {
                $client->setOptionalParam($key, $value);
            }
            $data = $client->sendSMS($this->key, $to, $message, $this->route, $this->from);
        } catch (SoapFault $e) {
            throw new StradeException($e->getCode());
        }
        $response_code = $data[0];
        $messageid = $data[1];
        //$cost = $data[2];
        return (is_numeric($messageid) ? $messageid : true);
    }
}

class StradeHttp extends Strade
{

    public function send($to, $message)
    {
        $options = array_filter(get_object_vars($this));
        $options["message"] = ($this->messagetype=='unicode' && $this->charset=='UTF-8' ? strtoupper(bin2hex(iconv('UTF-8', 'UCS-2BE', $message))) : $message);
        $options["to"] = $to;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://gateway.smstrade.de");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($options));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $response = curl_exec($ch);
        $eresponse = explode("\n", $response);
        curl_close($ch);
        $response_code = intval($response);
        if ($response_code != 100) throw new StradeException($response_code);
        return ($this->message_id && isset($eresponse[1]) ? $eresponse[1] : true);
    }
}


class StradeException extends Exception
{
    var $codes = array(
        0 => 'Cannot connect to gateway',
        10 => 'Receiver number not valid',
        20 => 'Sender number not valid',
        30 => 'Message text not valid',
        31 => 'Message type not valid',
        40 => 'SMS route not valid',
        50 => 'Identification failed',
        60 => 'Not enough balance in account',
        70 => 'Network does not support the route',
        71 => 'Feature is not possible by the route',
        80 => 'Handover to SMSC failed',
        100 => 'OK'
    );

    public function __construct($code)
    {
        $code = (int)$code;
        $this->message = (isset($this->codes[$code]) ? $this->codes[$code] : 'Unknown Error Code');
        $this->code = $code;
    }


}