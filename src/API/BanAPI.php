<?php

class BanAPI{
	/** @var PocketMinecraftServer */
	private $server;
	/** @var Config */
	private $whitelist;
	/** @var Config */
	private $banned;
	/** @var Config */
	private $ops;
	/** @var Config */
	private $bannedIPs;
	/**
	 * Contains commands that can be used by anyone on the server.
	 * Keys: lowercased command name. Values don't matter.
	 * @var array
	 */
	public $cmdWhitelist = [];

	public function __construct(){
		$this->server = ServerAPI::request();
	}

	public function init(){
		$this->whitelist = new Config(DATA_PATH . "white-list.txt", CONFIG_LIST);//Open whitelist list file
		$this->bannedIPs = new Config(DATA_PATH . "banned-ips.txt", CONFIG_LIST);//Open Banned IPs list file
		$this->banned = new Config(DATA_PATH . "banned.txt", CONFIG_LIST);//Open Banned Usernames list file
		$this->ops = new Config(DATA_PATH . "ops.txt", CONFIG_LIST);//Open list of OPs
		$this->server->api->console->register("banip", "<add|remove|list|reload> [IP|player]", [$this, "commandHandler"]);
		$this->server->api->console->register("ban", "<add|remove|list|reload> [username]", [$this, "commandHandler"]);
		$this->server->api->console->register("kick", "<player> [reason ...]", [$this, "commandHandler"]);
		$this->server->api->console->register("whitelist", "<on|off|list|add|remove|reload> [username]", [$this, "commandHandler"]);
		$this->server->api->console->register("op", "<player>", [$this, "commandHandler"]);
		$this->server->api->console->register("deop", "<player>", [$this, "commandHandler"]);
		$this->server->api->console->register("sudo", "<player>", [$this, "commandHandler"]);
		$this->server->api->console->alias("ban-ip", "banip add");
		$this->server->api->console->alias("banlist", "ban list");
		$this->server->api->console->alias("pardon", "ban remove");
		$this->server->api->console->alias("pardon-ip", "banip remove");
		$this->server->addHandler("console.command", [$this, "permissionsCheck"], 1);//Event handler when commands are issued. Used to check permissions of commands that go through the server.
		$this->server->addHandler("player.block.break", [$this, "permissionsCheck"], 1);//Event handler for blocks
		$this->server->addHandler("player.block.place", [$this, "permissionsCheck"], 1);//Event handler for blocks
		$this->server->addHandler("player.flying", [$this, "permissionsCheck"], 1);//Flying Event
	}

	/**
	 * Whitelists a CMD so everyone can issue it - Even non OPs.
	 * @deprecated use ConsoleAPI::cmdWhitelist
	 * @see ConsoleAPI::cmdWhitelist
	 * @param string $cmd Command to Whitelist
	 */
	public function cmdWhitelist($cmd){
		$this->server->api->console->cmdWhitelist($cmd);
	}

	/**
	 * @param mixed $data
	 * @param string $event
	 * @return boolean
	 */
	public function permissionsCheck($data, $event){
		switch($event){
			case "player.flying"://OPs can fly around the server.
				if($this->isOp($data->iusername)){
					return true;
				}
				break;
			case "player.block.break":
				/**
				 * @var Player $player
				 */
				$player = $data["player"];
				if(!$this->isOp($player->iusername) && $player->level->getName() === $this->server->api->level->getDefault()){
					$t = new Vector2($data["target"]->x, $data["target"]->z);
					$s = new Vector2($this->server->spawn->x, $this->server->spawn->z);
					$sp = $player->entity->level->getProperty("spawn-protection");
					
					if($t->distance($s) <= $sp and $this->server->api->dhandle($event . ".spawn", $data) !== true){
						return false;
					}
				}
				return;
			case "player.block.place"://Spawn protection detection. Allows OPs to place/break blocks in the spawn area.
				$player = $data["player"];
				if(!$this->isOp($player->iusername) && $player->level->getName() === $this->server->api->level->getDefault()){
					$t = new Vector2($data["block"]->x, $data["block"]->z);
					$s = new Vector2($this->server->spawn->x, $this->server->spawn->z);
					$sp = $player->entity->level->getProperty("spawn-protection");
					
					if($t->distance($s) <= $sp and $this->server->api->dhandle($event . ".spawn", $data) !== true){
						return false;
					}
				}
				return;
			case "console.command"://Checks if a command is allowed with the current user permissions.
				if(isset($this->cmdWhitelist[$data["cmd"]])){
					return;
				}

				if($data["issuer"] instanceof Player){
					if($this->server->api->handle("console.check", $data) === true or $this->isOp($data["issuer"]->iusername)){
						return;
					}
				}elseif($data["issuer"] === "console" or $data["issuer"] === "rcon"){
					return;
				}
				return false;
		}
	}

	/**
	 * Checks is player op or not
	 * @param string $username
	 * @return boolean
	 */
	public function isOp($username){
		$username = strtolower($username);
		if($this->server->api->dhandle("op.check", $username) === true){
			return true;
		}elseif($this->ops->exists($username)){
			return true;
		}
		return false;
	}

	/**
	 * Bans a player.
	 * @param string $username
	 */
	public function ban($username){
		$this->commandHandler("ban", ["add", $username], "console", "");
	}

	/**
	 * @param string $cmd
	 * @param array $params
	 * @param string $issuer
	 * @param string $alias
	 *
	 * @return string
	 */
	public function commandHandler($cmd, $params, $issuer, $alias){
		$output = "";
		switch($cmd){
			case "sudo":
				$target = strtolower(array_shift($params));
				$player = $this->server->api->player->get($target);
				if(!isset($params[0])){
					$output .= "Usage: /sudo <player>\n";
					break;
				}
				if(!($player instanceof Player)){
					$output .= "Player not connected.\n";
					break;
				}
				$this->server->api->console->run(implode(" ", $params), $player);
				$output .= "Command ran as " . $player->username . ".\n";
				break;
			case "op":
				if(!isset($params[0])) return "Usage: /op <player>\n";
				$user = strtolower($params[0]);
				$player = $this->server->api->player->get($user);
				if(!($player instanceof Player)){
					$this->ops->set($user);
					$this->ops->save();
					$output .= $user . " is now op\n";
					break;
				}
				$this->ops->set($player->iusername);
				$this->ops->save();
				$output .= $player->iusername . " is now op\n";
				$this->server->api->chat->sendTo(false, "You are now op.", $player->iusername);
				break;
			case "deop":
				if(!isset($params[0])) return "Usage: /deop <player>\n";

				$user = strtolower($params[0]);
				$player = $this->server->api->player->get($user);
				if(!($player instanceof Player)){
					$this->ops->remove($user);
					$this->ops->save();
					$output .= $user . " is no longer op\n";
					break;
				}
				$this->ops->remove($player->iusername);
				$this->ops->save();
				$output .= $player->iusername . " is no longer op\n";
				$this->server->api->chat->sendTo(false, "You are no longer op.", $player->iusername);
				break;
			case "kick":
				if(!isset($params[0])){
					$output .= "Usage: /kick <player> [reason ...]\n";
				}else{
					$name = strtolower(array_shift($params));
					$player = $this->server->api->player->get($name);
					if($player === false){
						$output .= "Player \"" . $name . "\" does not exist\n";
					}else{
						$reason = implode(" ", $params);
						$reason = $reason == "" ? "No reason" : $reason;

						$this->server->schedule(60, [$player, "close"], "You have been kicked: " . $reason); //Forces a kick
						$player->lastCorrect = new Vector3($player->entity->x, $player->entity->y, $player->entity->z);
						$player->blocked = true;
						if($issuer instanceof Player){
							$this->server->api->chat->broadcast($player->username . " has been kicked by " . $issuer->username . ": $reason");
						}else{
							$this->server->api->chat->broadcast($player->username . " has been kicked: $reason");
						}
					}
				}
				break;
			case "whitelist":
				$p = strtolower(array_shift($params));
				switch($p){
					case "remove":
						$user = strtolower($params[0]);
						$this->whitelist->remove($user);
						$this->whitelist->save();
						$output .= "Player \"$user\" removed from white-list\n";
						break;
					case "add":
						$user = strtolower($params[0]);
						$this->whitelist->set($user);
						$this->whitelist->save();
						$output .= "Player \"$user\" added to white-list\n";
						break;
					case "reload":
						$this->whitelist = new Config(DATA_PATH . "white-list.txt", CONFIG_LIST);
						break;
					case "list":
						$output .= "White-list: " . implode(", ", $this->whitelist->getAll(true)) . "\n";
						break;
					case "on":
					case "true":
					case "1":
						$output .= "White-list turned on\n";
						$this->server->api->setProperty("white-list", true);
						break;
					case "off":
					case "false":
					case "0":
						$output .= "White-list turned off\n";
						$this->server->api->setProperty("white-list", false);
						break;
					default:
						$output .= "Usage: /whitelist <on|off|list|add|remove|reload> [username]\n";
						break;
				}
				break;
			case "banip":
				$p = strtolower(array_shift($params));
				switch($p){
					case "pardon":
					case "remove":
						if(!isset($params[0])) return "Ussage: /banip remove <ip>";
						$ip = strtolower($params[0]);
						$this->bannedIPs->remove($ip);
						$this->bannedIPs->save();
						return "IP \"$ip\" removed from ban list\n";
					case "add":
					case "ban":
						if(!isset($params[0])) return "Ussage: /banip add <username>";
						$ip = strtolower($params[0]);
						$player = $this->server->api->player->get($ip);
						if($player instanceof Player){
							$ip = $player->ip;
							$player->close("banned");
						}
						$this->bannedIPs->set($ip);
						$this->bannedIPs->save();
						return "IP \"$ip\" added to ban list\n";
					case "reload":
						$this->bannedIPs = new Config(DATA_PATH . "banned-ips.txt", CONFIG_LIST);
						return "Reloaded banned ips list";
					case "list":
						return "IP ban list: " . implode(", ", $this->bannedIPs->getAll(true)) . "\n";
					default:
						return "Usage: /banip <add|remove|list|reload> [IP|player]\n";
				}
				break;
			case "ban":
				$p = strtolower(array_shift($params));
				switch($p){
					case "pardon":
					case "remove":
						if(!isset($params[0])) return "Ussage: /ban remove <username>";
						$user = strtolower($params[0]);
						$this->banned->remove($user);
						$this->banned->save();
						return "Player \"$user\" removed from ban list\n";
					case "add":
					case "ban":
						if(!isset($params[0])) return "Ussage: /ban add <username>";
						$user = strtolower($params[0]);
						$this->banned->set($user);
						$this->banned->save();
						$player = $this->server->api->player->get($user);
						if($player !== false){
							$player->close("You have been banned");
						}
						if($issuer instanceof Player){
							$this->server->api->chat->broadcast($user . " has been banned by " . $issuer->username . "\n");
						}else{
							$this->server->api->chat->broadcast($user . " has been banned\n");
						}
						$this->kick($user, "Banned");
						return "Player \"$user\" added to ban list\n";
					case "reload":
						$this->banned = new Config(DATA_PATH . "banned.txt", CONFIG_LIST);
						return "Reloaded banned players list";
					case "list":
						return "Ban list: " . implode(", ", $this->banned->getAll(true)) . "\n";
					default:
						return "Usage: /ban <add|remove|list|reload> [username]\n";
				}
				break;
		}
		return $output;
	}

	/**
	 * Kicks a player
	 * @param string $username - player's username
	 * @param string $reason - kick reason, optional
	 */
	public function kick($username, $reason = "No Reason"){
		$this->commandHandler("kick", [$username, $reason], "console", "");
	}

	/**
	 * Unbans a player
	 * @param string $username
	 */
	public function pardon($username){
		$this->commandHandler("ban", ["pardon", $username], "console", "");
	}

	/**
	 * Bans some ip
	 * @param string $ip
	 */
	public function banIP($ip){
		$this->commandHandler("banip", ["add", $ip], "console", "");
	}

	/**
	 * Unbans some ip
	 * @param string $ip
	 */
	public function pardonIP($ip){
		$this->commandHandler("banip", ["pardon", $ip], "console", "");
	}

	/**
	 * Rereads ban list, kick list and banip list
	 */
	public function reload(){
		$this->commandHandler("ban", ["reload"], "console", "");
		$this->commandHandler("banip", ["reload"], "console", "");
		$this->commandHandler("whitelist", ["reload"], "console", "");
	}

	/**
	 * Checks is ip banned or not
	 * @param string $ip
	 * @return boolean
	 */
	public function isIPBanned($ip){
		if($this->server->api->dhandle("api.ban.ip.check", $ip) === false){
			return true;
		}elseif($this->bannedIPs->exists($ip, true)){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * Checks is username banned or not.
	 * @param string $username
	 * @return boolean
	 */
	public function isBanned($username){
		$username = strtolower($username);
		if($this->server->api->dhandle("api.ban.check", $username) === false){
			return true;
		}elseif($this->banned->exists($username, true)){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * Checks is username in the whitelist or not
	 * @param string $username
	 * @return boolean
	 */
	public function inWhitelist($username){
		$username = strtolower($username);
		if($this->isOp($username)){
			return true;
		}elseif($this->server->api->dhandle("api.ban.whitelist.check", $username) === false){
			return true;
		}elseif($this->whitelist->exists($username, true)){
			return true;
		}
		return false;
	}
}
