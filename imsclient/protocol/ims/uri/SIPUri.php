<?php

namespace imsclient\protocol\ims\uri;

class SIPUri extends GenericUri
{
    static protected $_proto = "sip";
    protected $_params = [];

    public $username = null;
    public $host = null;
    public $port = null;

    public function __construct($username, $host, $port = null, $params = [])
    {
        $this->username = $username;
        $this->host = $host;
        $this->port = $port;
        $this->_params = $params;
    }

    protected function generate()
    {
        $this->_value = "";

        if ($this->username != null) {
            $this->_value .= $this->username . "@";
        }

        if (strstr($this->host, ":")) {
            $this->_value .= "[" . $this->host . "]";
        } else {
            $this->_value .= $this->host;
        }

        if ($this->port !== null) {
            $this->_value .= ":" . $this->port;
        }

        foreach ($this->_params as $key => $value) {
            $this->_value .= ";" . $key;
            if ($value !== true) {
                $this->_value .= "={$value}";
            }
        }
    }

    protected function parse()
    {
        $exp_uri_param = explode(";", $this->_value);
        $uri = $exp_uri_param[0];

        $params = array_slice($exp_uri_param, 1);
        foreach ($params as $param) {
            $exp_param = explode("=", $param, 2);
            if (count($exp_param) == 2) {
                $this->_params[$exp_param[0]] = $exp_param[1];
            } else {
                $this->_params[$exp_param[0]] = true;
            }
        }

        if (strstr($uri, "@")) {
            $exp_uri = explode("@", $uri, 2);
            $this->username = $exp_uri[0];
            $this->host = $exp_uri[1];
        } else {
            $this->username = null;
            $this->host = $uri;
        }

        if (substr($this->host, 0, 1) == "[") {
            $exp_host = explode("]", $this->host, 2);
            $this->host = substr($exp_host[0], 1);
            if (count($exp_host) == 1) {
                $this->port = null;
            } else {
                $port_formatfix = substr($exp_host[1], 1);
                if (strlen($port_formatfix) == 0) {
                    $port_formatfix = null;
                }
                $this->port = $port_formatfix;
            }
        } else {
            $exp_host = explode(":", $this->host, 2);
            $this->host = $exp_host[0];
            if (count($exp_host) == 1) {
                $this->port = null;
            } else {
                $this->port = $exp_host[1];
            }
        }

        $this->host = strtolower($this->host);
    }
}
