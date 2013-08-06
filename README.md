Strade
======

PHP Class for gateway smstrade.de

Usage:
$this->strade = new StradeHttp($apikey);
$this->strade->send($phone,$message);

You can also use soap interface
$this->strade = new StradeSoap($apikey);
