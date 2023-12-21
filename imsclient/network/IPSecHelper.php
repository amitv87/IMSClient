<?php

namespace imsclient\network;

use imsclient\util\Utils;

abstract class IPSecHelper
{
    static public function cleanup($mark)
    {
        self::deleteVTI($mark);
        self::deleteXFRM($mark);
        self::deleteEndpointMap($mark);
    }

    static private function deleteVTI($mark)
    {
        $ifname = "ims" . bin2hex(pack('N', $mark));
        Utils::passthru("ip link del {$ifname}");
    }

    static private function deleteEndpointMap($mark)
    {
        $rule_list = shell_exec('iptables -t nat -L OUTPUT -n -v');
        $rule_list = explode(PHP_EOL, $rule_list);
        $rules = [];
        foreach ($rule_list as $r) {
            if (preg_match("/(\S+?)\s+?mark match (.*?) to:(.*)/", $r, $matches)) {
                if (count($matches) == 4) {
                    $rules[] = [
                        'dst' => $matches[1],
                        'mark' => $matches[2],
                        'to' => $matches[3],
                    ];
                }
            }
        }
        foreach ($rules as $r) {
            if ($r['mark'] == "0x" . dechex($mark)) {
                Utils::passthru("iptables -t nat -D OUTPUT -d {$r['dst']} -m mark --mark {$r['mark']} -j DNAT --to-destination {$r['to']}");
            }
        }
    }

    static private function deleteXFRM($reqid)
    {
        $policy_list = shell_exec('ip xfrm policy list');
        $policy_list = explode(PHP_EOL, $policy_list);
        $policy = [];
        $current_policy = [];
        foreach ($policy_list as $line) {
            if (preg_match('/^src (.*?) dst (.*?) $/', $line, $matches)) {
                if ($current_policy !== []) {
                    $policy[] = $current_policy;
                    $current_policy = [];
                }
                $current_policy['src'] = $matches[1];
                $current_policy['dst'] = $matches[2];
                continue;
            }
            if (preg_match('/dir (.*?) /', $line, $matches)) {
                $current_policy['dir'] = $matches[1];
                continue;
            }
            if (preg_match('/mark (.*?)\//', $line, $matches)) {
                $current_policy['mark'] = $matches[1];
                continue;
            }
            if (preg_match('/reqid (.*?) /', $line, $matches)) {
                $current_policy['reqid'] = $matches[1];
                continue;
            }
        }
        $policy[] = $current_policy;
        foreach ($policy as $p) {
            if (isset($p['reqid']) && $p['reqid'] == $reqid) {
                Utils::passthru("ip xfrm policy delete src {$p['src']} dst {$p['dst']} dir {$p['dir']} mark {$p['mark']}");
            }
        }
        Utils::passthru("ip xfrm state deleteall reqid {$reqid}");
    }

    static public function addEndpointMap($mark, $peer): string
    {
        $newep = long2ip($mark);
        Utils::passthru("iptables -t nat -I OUTPUT -m mark --mark {$mark} -d {$newep} -j DNAT --to-destination {$peer}");
        return $newep;
    }

    static public function addTunnelInterface($mark, $indicator, $responder, $spi_indicator, $spi_responder, $encr_indicator, $encr_responder, $auth_indicator, $auth_responder, $port_indicator, $port_responder, $cidr, array $route = []): string
    {
        $ifname = "ims" . bin2hex(pack('N', $mark));
        $cidr_ip = explode("/", $cidr)[0];
        $policy_cidr = "0.0.0.0/0";
        if (strstr($cidr, ":")) {
            $policy_cidr = "::/0";
        }

        Utils::passthru("ip xfrm state add src {$indicator} dst {$responder} proto esp spi {$spi_responder} mode tunnel {$encr_indicator} {$auth_indicator} encap espinudp {$port_indicator} {$port_responder} 0.0.0.0 sel src {$policy_cidr} dst {$policy_cidr} output-mark {$mark} reqid {$mark}");

        Utils::passthru("ip xfrm state add src {$responder} dst {$indicator} proto esp spi {$spi_indicator} mode tunnel {$encr_responder} {$auth_responder} encap espinudp {$port_responder} {$port_indicator} 0.0.0.0 sel src {$policy_cidr} dst {$policy_cidr} reqid {$mark}");

        Utils::passthru("ip xfrm policy add src {$policy_cidr} dst {$policy_cidr} dir out tmpl src {$indicator} dst {$responder} proto esp spi {$spi_responder} mode tunnel reqid {$mark} mark {$mark}");
        Utils::passthru("ip xfrm policy add src {$policy_cidr} dst {$policy_cidr} dir in tmpl src {$responder} dst {$indicator} proto esp spi {$spi_indicator} mode tunnel reqid {$mark} mark {$mark}");

        Utils::passthru("ip link add {$ifname} type vti local {$indicator} remote {$responder} key {$mark}");
        Utils::passthru("ip addr add {$cidr} dev {$ifname}");
        Utils::passthru("ip link set {$ifname} mtu 1280 up");

        foreach ($route as $r) {
            Utils::passthru("ip route add {$r} src {$cidr_ip} dev {$ifname}");
        }

        return $ifname;
    }

    static public function addTransportPair($reqid, $mark, $indicator, $responder, $spi_indicator, $spi_responder, $encr_indicator, $encr_responder, $auth_indicator, $auth_responder)
    {
        Utils::passthru("ip xfrm state add src {$indicator} dst {$responder} proto esp spi {$spi_responder} mode transport {$encr_indicator} {$auth_indicator} reqid {$reqid}");
        Utils::passthru("ip xfrm state add src {$responder} dst {$indicator} proto esp spi {$spi_indicator} mode transport {$encr_responder} {$auth_responder} reqid {$reqid}");
        Utils::passthru("ip xfrm policy add src {$indicator} dst {$responder} dir out tmpl src {$indicator} dst {$responder} proto esp spi {$spi_responder} mode transport reqid {$reqid} mark {$mark}");
    }
}
