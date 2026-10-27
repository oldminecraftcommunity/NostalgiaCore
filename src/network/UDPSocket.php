<?php

class UDPSocket{

	public $connected; 
	public $sock;
	public $server;
	public $port;
	function __construct($server, $port, $listen = false, $serverip = "0.0.0.0"){
		$this->server = $server;
		$this->port = $port;
		$this->sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_set_option($this->sock, SOL_SOCKET, SO_BROADCAST, 1); //Allow sending broadcast messages
		if($listen !== true){
			$this->connected = true;
			$this->unblock();
		}else{
			if(socket_bind($this->sock, $serverip, $port) === true){
				socket_set_option($this->sock, SOL_SOCKET, SO_REUSEADDR, 0);
				socket_set_option($this->sock, SOL_SOCKET, SO_SNDBUF, 1024 * 1024 * 8); //8MB
				socket_set_option($this->sock, SOL_SOCKET, SO_RCVBUF, 1024 * 1024); //1MB
				$this->unblock();
				$this->connected = true;
			}else{
				$this->connected = false;
			}
		}
	}

	public function unblock(){
		socket_set_nonblock($this->sock);
	}

	public function close($error = 125){
		$this->connected = false;
		return @socket_close($this->sock);
	}

	public function block(){
		socket_set_block($this->sock);
	}

	public function read(&$buf, &$source, &$port){
		if($this->connected === false){
			return false;
		}
		if(PocketMinecraftServer::$TICKING_MODE == PocketMinecraftServer::TICK_NETWAIT){
			$read = [$this->sock];
			$next = (ServerAPI::request()->lastTick + 0.05);
			$waitTime = $next - microtime(true);
			if($waitTime < 0) $waitTime = 0;
			$c = socket_select($read, $_, $_, 0, (int)($waitTime*1000000));
			if($c === false || $c <= 0) return false;
		}
		return @socket_recvfrom($this->sock, $buf, 65535, 0, $source, $port);
	}

	public function write($data, $dest, $port){
		if($this->connected === false){
			return false;
		}

		return @socket_sendto($this->sock, $data, strlen($data), 0, $dest, $port);
	}
}
