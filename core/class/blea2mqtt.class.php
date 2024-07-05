<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class blea2mqtt extends eqLogic {
    /*     * *************************Attributs****************************** */

    /*
     * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
     * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
     */
    public static $_widgetPossibility = array();

    /*
     * Permet de crypter/décrypter automatiquement des champs de configuration du plugin
     * Exemple : "param1" & "param2" seront cryptés mais pas "param3"
     */
    public static $_encryptConfigKey = array('pwd');

    /**
     * Version du plugin.
     * @var string
     */
    public static $_pluginVersion = '0.32';

    /**
     * URL du dépôt GitHub pour le projet Flobul/Blea2Mqtt.
     */
    const GITHUB_FLOBUL_BLEA2MQTT  = 'https://github.com/Flobul/blea2mqtt.git';

    /**
     * URL du dépôt GitHub pour le projet BitKill/Blea2Mqtt.
     */
    const GITHUB_BITKILL_BLEA2MQTT = 'https://github.com/bitkill/blea2mqtt.git';
    /*     * ***********************Methode static*************************** */

    /*
    * Fonction exécutée automatiquement toutes les minutes par Jeedom
    */
    public static function cron() {
		foreach (eqLogic::byType(__CLASS__) as $eqLogic) {
			if ($eqLogic->getIsEnable()) {
                $eqLogic->getServiceStatus($eqLogic->getConfiguration('system'));
            }
        }
    }

    /**
     * Installe les dépendances pour le plugin Blea2mqtt.
     *
     * @param int $_id Identifiant de l'équipement BLEA2MQTT.
     *
     * @return array|bool
     */
    public static function installDependancy($_id) {
        $eqLogic = eqLogic::byId($_id);
        if (!is_object($eqLogic)) {
            return false;
        }
        log::add(__CLASS__, 'debug', __FUNCTION__ .' début ' . $_id);

        $user = $eqLogic->getConfiguration('user');
        $pass = $eqLogic->getConfiguration('pwd');

        $system = $eqLogic->sendRequest('CMD', array('uname -s'));
        $hostname = $eqLogic->sendRequest('CMD', array('hostname'));

        $eqLogic->setConfiguration('hostname', $hostname['result'][0]);
        $eqLogic->setConfiguration('system', $system['result'][0]);
        $eqLogic->setConfiguration('lastDependancyInstall', date('Y-m-d H:i:s'));
        $eqLogic->save();

        $file1 = '/install_apt.sh';
        $file2 = '/install_brew.sh';
        $pwd = $eqLogic->getHomeDir();
		$path = dirname(__FILE__) . '/../../resources';
	    exec('sudo /bin/echo "Début des dépendances ' . $equipement . '" > ' . log::getPathToLog(__CLASS__ . '_dep') . ' 2>&1 &');

        $result = $eqLogic->sendRequest('PUT', array($path . $file1, $path . $file2), array($pwd . $file1, $pwd . $file2));
        if ($result['result'] && $result['result'][0]) {
          $cmd = "bash -c '" . $pwd . $file1 . " " . $pass . " " . $eqLogic->getConfiguration('library', self::GITHUB_FLOBUL_BLEA2MQTT) . "'";
		  $cmd = $eqLogic->getCmdSudo($cmd, true);
            $exec = $eqLogic->sendRequest('CMD', array($cmd));
            if ($exec['result'][0]) {
                $result['result']['cmd'] = true;
                $exec = $eqLogic->sendRequest('CMD', self::editEnvConfigFile($pwd));
                if ($system['result'][0] == 'Linux') {
                    $exec = $eqLogic->sendRequest('CMD', array(
                        'systemctl daemon-reload',
                        self::getSystemctlCommand('enable'),
                        self::getSystemctlCommand('restart')
                    ));
                    $eqLogic->getServiceStatus('Linux');
                } elseif ($system['result'][0] == 'Darwin') {
                    $brew = $eqLogic->sendRequest('CMD', array('bash -c \'' . $pwd . $file2 . ' "' . $pass . '" "' . $user . '"\''), false, true);
                    $exec = $eqLogic->sendRequest('CMD', array(
                        self::getLaunchctlCommand('bootstrap')
                    ), false, true);
                    $eqLogic->getServiceStatus('Darwin');
                }
            }
        }
        return $result;
    }

    /*     * *********************Méthodes d'instance************************* */

    /**
     * Fonction exécutée automatiquement avant la création de l'équipement
     *
     * @return void
     */
    public function preInsert() {
    }

    /**
     * Fonction exécutée automatiquement après la création de l'équipement
     *
     * @return void
     */
    public function postInsert() {
    }

    /**
     * Fonction exécutée automatiquement avant la mise à jour de l'équipement
     * Vérifie si les champs "ip", "port", "user" et "pwd" sont vides, et lève une exception si c'est le cas.
     * Si les champs "pubkey" et "privkey" sont également vides, une exception est levée.
     *
     * @throws Exception si un champ obligatoire est vide
     * @return void
     */
    public function preUpdate() {
		if ($this->getConfiguration('ip') == '') {
			throw new Exception(__('L\'adresse IP ne peut être vide', __FILE__));
		}
		if ($this->getConfiguration('port') == '') {
			throw new Exception(__('Le port ne peut être vide', __FILE__));
		}
		if ($this->getConfiguration('user') == '') {
			throw new Exception(__('L\'utilisateur ne peut être vide', __FILE__));
		}
		if ($this->getConfiguration('pwd') == '') {
            if ($this->getConfiguration('pubkey') == '' && $this->getConfiguration('privkey') == '') {
			    throw new Exception(__('Le mot de passe ou clés privée/publique ne peuvent être vides', __FILE__));
            }
		}
    }

    /**
     * Fonction exécutée automatiquement après la mise à jour de l'équipement
     * Vérifie si l'équipement est activé ou désactivé, et appelle la méthode appropriée pour vérifier que les événements MQTT arrivent bien en provenance des antennes.
     *
     * @return void
     */
    public function postUpdate() {

    }

    /**
     * Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement.
     *
     * @return void
     */
    public function preSave() {
        $lib = $this->getConfiguration('library', false);
        $homedir = $this->getConfiguration('homeDir', false);
        $user = $this->getConfiguration('user', false);
        $system = $this->getConfiguration('system', false);

        if (!$lib) {
            $this->setConfiguration('library', self::GITHUB_FLOBUL_BLEA2MQTT);
        }
        if (!$homedir) {
            $homeDir = ($system && $system == 'Darwin') ? '/Users/' . $user : (($user == 'root') ? '/root' : '/var/' . $user);
            $this->setConfiguration('homeDir', $homeDir);
        }
    }

    /**
     * Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement.
     *
     * @return void
     */
    public function postSave() {
        $this->loadConfigFile();
    }

    /**
     * Fonction exécutée automatiquement avant la suppression de l'équipement.
     *
     * @return void
     */
    public function preRemove() {
        self::removeListener(array('id' => intval($this->getId())));
    }

    /**
     * Fonction exécutée automatiquement après la suppression de l'équipement.
     *
     * @return void
     */
    public function postRemove() {
    }

    /**
     * Déchiffre le mot de passe stocké dans la configuration de l'objet en utilisant l'outil de chiffrement 'utils::decrypt'.
     *
     * Met à jour la configuration avec le mot de passe déchiffré.
     *
     * @return void
     */
    public function decrypt() {
        $this->setConfiguration('pwd', utils::decrypt($this->getConfiguration('pwd')));
    }

    /**
     * Chiffre le mot de passe stocké dans la configuration de l'objet en utilisant l'outil de chiffrement 'utils::encrypt'.
     *
     * Met à jour la configuration avec le mot de passe chiffré.
     *
     * @return void
     */
    public function encrypt() {
        $this->setConfiguration('pwd', utils::encrypt($this->getConfiguration('pwd')));
    }

    /**
     * Récupère le chemin du répertoire utilisateur.
     *
     * @return string
     */
    public function getHomeDir()
    {
        $user = $this->getConfiguration('user');
        return $this->getConfiguration('homeDir', ($user == 'root') ? '/root' : '/var/' . $user);
    }

    /**
     * Charge le fichier de configuration et crée les commandes si elles n'existent pas.
     *
     * @throws Exception si le fichier de configuration est introuvable, corrompu ou invalide
     *
     * @return array les données du fichier de configuration
     */
    private function loadConfigFile()
    {
        log::add(__CLASS__, 'debug', __FUNCTION__ .' début');
        $filename = __DIR__ . '/../../core/config/cmds.json';
        if ( file_exists($filename) === false ) {
            throw new Exception('Impossible de trouver le fichier de configuration pour l\'équipement ');
        }
        $content = file_get_contents($filename);
        if (!is_json($content)) {
            throw new Exception('Le fichier de configuration \'' . $filename . '\' est corrompu');
        }

        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data['commands'])) {
            throw new Exception('Le fichier de configuration \'' . $filename . '\' est invalide');
        }

		foreach ($data['commands'] as $command) {
			$cmd = null;
			if (is_object($this)){
				foreach ($this->getCmd() as $liste_cmd) {
					if ((isset($command['logicalId']) && $liste_cmd->getLogicalId() == $command['logicalId']) || (isset($command['name']) && $liste_cmd->getName() == $command['name'])) {
						$cmd = $liste_cmd;
						break;
					}
				}
			}
			if ($cmd == null || !is_object($cmd)) {
				$cmd = new blea2mqttCmd();
				$cmd->setEqLogic_id($this->getId());
				utils::a2o($cmd, $command);
				$cmd->save();
				log::add(__CLASS__,'debug', __('Création commande via conf', __FILE__) . ' : ' . $cmd->getName());
			}
		}
        log::add(__CLASS__, 'debug', __FUNCTION__ .' fin');
        return $data;
    }

    /**
     * Retourne la commande à exécuter avec ou sans sudo.
     *
     * @param string $cmd la commande à exécuter
     * @param bool $_sudo true si la commande doit être exécutée avec sudo, false sinon
     *
     * @return string la commande à exécuter avec ou sans sudo
     */
    public function getCmdSudo($cmd, $_sudo) {
        if ($this->getConfiguration('user', 'root') != 'root' && $_sudo) { // si non root ou sudo=true
            return "echo '".$this->getConfiguration('pwd')."' | sudo -S $cmd";
        } else {
            return $cmd;
        }
    }

    /**
     * Envoie une requête SSH à l'équipement.
     *
     * @param string $_action      L'action à effectuer (CMD, GET ou PUT).
     * @param array  $_cmd         Le tableau de commandes à exécuter.
     * @param mixed  $_localFile   Le nom du fichier à récupérer ou à envoyer.
     * @param bool   $_sudo        Indique si la commande doit être exécutée en mode sudo.
     *
     * @return array               Les résultats de l'exécution de la commande SSH.
     *                             Le tableau contient :
     *                             - 'connected' : booléen indiquant si la connexion SSH est établie.
     *                             - 'result' : un tableau contenant le résultat de chaque commande exécutée.
     *                             - 'exit' : la sortie de la commande 'exit' de la connexion SSH.
     *                             - 'time' : la durée du traitement en secondes.
     */
	public function sendRequest($_action, $_cmd, $_localFile = false, $_sudo = false) {

		$output = array();
        $ip      = $this->getConfiguration('ip');
        $port    = $this->getConfiguration('port');
		$user    = $this->getConfiguration('user', 'root');
		$pwd     = $this->getConfiguration('pwd', '');
		$pubkey  = $this->getConfiguration('pubkey');
		$privkey = $this->getConfiguration('privkey');
        $keys    = ($pwd == '') ? array('hostkey' => 'ssh-rsa') : null;
        $equipement = $this->getName() . ' ' . $ip . '::' . $port;
		$timeStart = microtime(true);

        $output['connected'] = false;
		if (!$cx = ssh2_connect($ip, intval($port), $keys)) {
			log::add(__CLASS__, 'debug', __FUNCTION__ . __(' Connexion SSH KO : ',__FILE__) . $equipement . json_encode(error_get_last()));
			return false;
		} else {
            if (!ssh2_auth_password($cx, $user, $pwd) && !ssh2_auth_pubkey_file($cx, $user, $pubkey, $privkey, $pwd)){
                log::add(__CLASS__, 'error', __FUNCTION__ . __(' Authentification SSH par clé privée ou mot de passe KO : ', __FILE__) . $equipement);
				return false;
			} else {
                $output['connected'] = true;
				foreach ($_cmd as $i => $cmd){
                    $output['result'][$i] = false;
                    if ($_action == 'CMD') {
					    $cmd = $this->getCmdSudo($cmd, $_sudo);
					    log::add(__CLASS__, 'info', __FUNCTION__ . __(' Commande par SSH : ',__FILE__) . $cmd);

                        $stream = ssh2_exec($cx, $cmd);
                        $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
                        stream_set_blocking($errorStream, true);
                        stream_set_blocking($stream, true);
                        //stream_set_chunk_size($stream, 1024); // Définir la taille maximale de chaque chunk de données
		                exec('sudo /bin/echo -e "--- $(/bin/date +\'%F %T\'):\n" >> ' . log::getPathToLog(__CLASS__ . '_dep') . ' 2>&1 &');
		                exec('sudo /bin/echo "' . $user . '@' . $ip . ':~' . (($user != 'root' || $_sudo)?'#':'$') . ' ' . $cmd . '" >> ' . log::getPathToLog(__CLASS__ . '_dep') . ' 2>&1 &');
                        $stre = '';
                        while($line = fgets($stream)) {
					    //log::add(__CLASS__, 'info', __FUNCTION__ . __(' Commande par SSH1 : ',__FILE__) . $line);
                            flush();
		                    exec('sudo /bin/echo "' . $line . '" >> ' . log::getPathToLog(__CLASS__ . '_dep') . ' 2>&1 &');
                            $stre .= $line;
                        }
                        $output['result'][$i] = trim($stre);
					    log::add(__CLASS__, 'info', __FUNCTION__ . __(' Résultat cmd SSH : ',__FILE__) . $stre);
                    } elseif ($_action == 'GET') {
				        if (ssh2_scp_recv($cx, $cmd, $_localFile[$i])) {
                            log::add(__CLASS__, 'info', __FUNCTION__ . __(' Fichier récupéré avec succès : ', __FILE__) . $_localFile[$i]);
                            $output['result'][$i] = true;
                        }
                    } elseif ($_action == 'PUT') {
					log::add(__CLASS__, 'info', __FUNCTION__ . __(' Commande par SSHDESDSD : ',__FILE__) . $cmd .  ' ' . $_localFile[$i]);
				        if (ssh2_scp_send($cx, $cmd, $_localFile[$i], 0755)) {
                            log::add(__CLASS__, 'info', __FUNCTION__ . __(' Fichier envoyé avec succès : ', __FILE__) . $cmd);
                            $output['result'][$i] = true;
                        }
                    }
					fclose($stream);
					fclose($errorStream);
				}
				$stream = ssh2_exec($cx, 'exit');
				$errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
				stream_set_blocking($errorStream, true);
				stream_set_blocking($stream, true);
				$output['result']['exit'] = stream_get_contents($stream) . ' ' . stream_get_contents($errorStream);
				fclose($stream);
				fclose($errorStream);
			}
		}
		$timeEnd = microtime(true);
		$output['time'] = round($timeEnd-$timeStart,3);
		log::add(__CLASS__, 'info', __FUNCTION__ . __(' Durée du traitement d\'envoi des commandes : ', __FILE__) . $output['time'] . 's');
		return $output;
	}

    /**
     * Récupère la configuration des brokers MQTT.
     *
     * @param string|null $_class Le nom de la classe du broker à récupérer.
     * @param string|null $_jmqttId L'ID du broker à récupérer.
     *
     * @return array Les informations des brokers MQTT.
     */
    public static function getBrokerFromJeedom($_class = null, $_jmqttId = null) {
        $return = array();
        if (class_exists('jMQTT')){
            if (method_exists('jMQTT', 'getBrokers')) {
                foreach(jMQTT::getBrokers() as $broker) {
                    if($broker->getIsEnable()) {
                        $array = array(
                            'plugin'   => 'jMQTT',
                            'eq_id'    => $broker->getId(),
                            'ip'       => $broker->getConfiguration(jMQTTConst::CONF_KEY_MQTT_ADDRESS),
                            'port'     => $broker->getConfiguration(jMQTTConst::CONF_KEY_MQTT_PORT),
                            'user'     => $broker->getConfiguration(jMQTTConst::CONF_KEY_MQTT_USER),
                            'password' => $broker->getConfiguration(jMQTTConst::CONF_KEY_MQTT_PASS)
                        );
                        $return[] = $array;
                        if ($_class && $_class == 'jMQTT' && $_jmqttId && $_jmqttId == $array['eq_id']) {
                            return $array;
                        }
                    }
                }
            }
        }
        if (class_exists('mqtt2')){
            $plugin = plugin::byId('mqtt2');
            if (method_exists('mqtt2', 'getFormatedInfos')) {
                $array = array_merge(array('plugin' => 'mqtt2'), mqtt2::getFormatedInfos());
                $return[] = $array;
                if ($_class && $_class == 'mqtt2') {
                    return $array;
                }
            }
        }
        if (class_exists('MQTT')){
            $array = array(
                'plugin'   => 'MQTT',
                'ip'       => config::byKey('mqttAdress', 'MQTT', '127.0.0.1'),
                'port'     => config::byKey('mqttPort', 'MQTT', '1883'),
                'user'     => config::byKey('mqttUser', 'MQTT'),
                'password' => config::byKey('mqttPass', 'MQTT')
            );
            $return[] = $array;
            if ($_class && $_class == 'MQTT') {
                return $array;
            }
        }
        return $return;
    }

    /**
     * Récupère les informations du broker MQTT configuré dans le plugin ou dans les variables de configuration
     * @return array Tableau contenant les informations du broker MQTT
     */
    public static function getBrokerInfos() {
        $broker = config::byKey('broker_id', __CLASS__, '');
        if ($broker != '') {
            $brok = explode('::', $broker);
            $return = blea2mqtt::getBrokerFromJeedom($brok[0], $brok[1]);
        } else {
            $return = array(
                'plugin'   => 'external',
                'ip'       => config::byKey('mqttAdress', __CLASS__, '127.0.0.1'),
                'port'     => config::byKey('mqttPort', __CLASS__, '1883'),
                'user'     => config::byKey('mqttUser', __CLASS__),
                'password' => config::byKey('mqttPass', __CLASS__)
            );
        }
        $return['topic'] = config::byKey('publish_template', __CLASS__, 'tasmota_ble');
        return $return;
    }

    /**
     * Récupère la commande à exécuter pour manipuler le service blea2mqtt sous systemd ou launchd
     * @param string $action Action à effectuer sur le service (start, stop, status, etc.)
     * @return string Commande à exécuter pour manipuler le service
     * @throws Exception Si l'action passée en paramètre n'est pas connue
     */
    public function getSystemctlCommand($action) {
        return "sudo systemctl $action blea2mqtt.service";
    }

    /**
     * Retourne la commande "launchctl" à exécuter en fonction de l'action passée en paramètre.
     *
     * @param string $action L'action à effectuer, parmi : 'load', 'unload', 'start', 'stop', 'status', 'enable', 'disable', 'print', 'bootout', 'bootstrap', 'kickstart'.
     * @return string La commande "launchctl" à exécuter.
     * @throws Exception Si l'action passée en paramètre n'est pas reconnue.
     */
    public function getLaunchctlCommand($action) {
        $cmd = false;
        switch ($action) {
            case 'load':
            case 'unload':
                $cmd = "$action /Library/LaunchDaemons/git.blea2mqtt.plist";
                break;
            case 'start':
            case 'stop':
                $cmd = "$action blea2mqtt";
                break;
            case 'status':
                $cmd = "list | grep blea2mqtt";
                break;
            case 'enable':
            case 'disable':
            case 'print':
                $cmd = "$action gui/$(id -u $( ls -l /dev/console | awk '{print $3}' ))/git.blea2mqtt";
                break;
            case 'bootout':
            case 'bootstrap':
                $cmd = "$action gui/$(id -u $( ls -l /dev/console | awk '{print $3}' )) /Library/LaunchDaemons/git.blea2mqtt.plist";
                break;
            case 'kickstart':
              $cmd = "$action -k gui/$(id -u $( ls -l /dev/console | awk '{print $3}' ))/git.blea2mqtt";
                break;
            default:
                throw new Exception("Action inconnue : $action");
        }
        return "launchctl $cmd";
    }

    /**
     * Retourne l'état du service "blea2mqtt" en fonction du système d'exploitation.
     *
     * @param string $_distrib Le nom du système d'exploitation, parmi : 'Linux', 'Darwin'.
     * @return void
     */
    public function getServiceStatus($_distrib) {

        if ($_distrib == 'Linux') {
            $cmd = self::getSystemctlCommand('status');
            $result = $this->sendRequest('CMD', array($cmd));
            preg_match('/Active:\s*(\S+\s+\S+)/', $result['result'][0], $matches_active);
            preg_match('/since (\w{3} \d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} \w{3});/', $result['result'][0], $matches_start_time);
            preg_match('/Loaded:.*\/blea2mqtt\.service; ([^;]+);/', $result['result'][0], $matches_loaded);

            $this->checkAndUpdateCmd('serviceStatus', (strpos($matches_active[1], 'running') !== false) ? 1 : 0);
            $connected = ($result) ? 1 : 0;
            $this->checkAndUpdateCmd('sshStatus', $connected);
        } elseif ($_distrib == 'Darwin') {
            $cmd = self::getLaunchctlCommand('print');
            $result = $this->sendRequest('CMD', array($cmd));
        log::add(__CLASS__, 'debug', __FUNCTION__ . __(' resultat ', __FILE__)  . json_encode($result));
            $result['connected'] = ($result['connected']) ? 1 : 0;
            $this->checkAndUpdateCmd('sshStatus', $result['connected']);
            if (preg_match('/state\s*=\s*([\w-]+)/', $result['result'][0], $matches)) {
                 $this->checkAndUpdateCmd('serviceStatus', ($matches[1] == 'running') ? 1 : 0);
            } // cas 0 à faire
        }
    }

    /**
     * Envoie un événement en fonction des options spécifiées.
     *
     * @param array $_options Un tableau contenant les options de l'événement.
     * @return void
     */
  	public static function sendEvent($_options) {
        //log::add(__CLASS__, 'debug', __FUNCTION__ . __(' début ', __FILE__)  . json_encode($_options));
		$cmd = cmd::byId(intval($_options['event_id']));
		if (!is_object($cmd)) {
			return;
		}
        for ($i = 0; $i < $_options['count_eqLogic']; $i++) {

            if (isset($_options['eqLogic_hostname::'.$i]) && $_options['value'] == $_options['eqLogic_hostname::'.$i]) {
                $eqLogic = eqLogic::byId(intval($_options['eqLogic_id::'.$i]));
                if (!is_object($eqLogic) || $eqLogic->getIsEnable() == 0) {
                    return;
                }
                $lastReceived = $eqLogic->getStatus('lastReceivedFrom', 0);
                if (strtotime($_options['datetime']) > strtotime($lastReceived)) {
                    log::add(__CLASS__, 'debug', __FUNCTION__ . __(' pour l\'antenne value=', __FILE__) . $_options['value']);
                    $eqLogic->setStatus('lastReceivedFrom', $_options['datetime']);
                }
            } else if (preg_match('/receivedFrom:([^,]+)/', $_options['value'], $matches)) {

                if (isset($_options['eqLogic_hostname::'.$i]) && $matches[1] == $_options['eqLogic_hostname::'.$i]) {
                    $eqLogic = eqLogic::byId(intval($_options['eqLogic_id::'.$i]));
                    if (!is_object($eqLogic) || $eqLogic->getIsEnable() == 0) {
                        return;
                    }
                    $lastReceived = $eqLogic->getStatus('lastReceivedFrom', 0);
                    if (strtotime($_options['datetime']) > strtotime($lastReceived)) {
                        log::add(__CLASS__, 'debug', __FUNCTION__ . __(' pour l\'antenne array=', __FILE__) . $matches[1]);
                        $eqLogic->setStatus('lastReceivedFrom', $_options['datetime']);
                    }
                }
            }
        }
	}

    /**
     * Récupère l'objet listener pour cette instance de classe.
     *
     * @return listener|null L'objet listener pour cette instance de classe ou null s'il n'existe pas.
     */
    private static function getPluginListeners($_option = '') {
        return listener::byClassAndFunction(__CLASS__, 'sendEvent', $_option);
    }

    /**
     * Supprime l'objet listener pour cette instance de classe s'il existe.
     *
     * @return void
     */
    private static function removeListener($_option) {
        log::add(__CLASS__, 'debug', __FUNCTION__ . __(' Suppression des listeners : ', __FILE__) . $this->getHumanName());
        if (is_object($listener = self::getPluginListeners($_option))) {
            $listener->remove();
        }
    }

    /**
     * Vérifie la publication MQTT
     *
     * @return void
     */
    public static function checkMQTTPublish() {
        log::add(__CLASS__, 'debug', __FUNCTION__ . __(' Création du listener : ', __FILE__));
        $broker = self::getBrokerInfos();
        $list = array();
        $eqLogics = eqLogic::byType(__CLASS__);
        $list['count_eqLogic'] = count($eqLogics);
        for ($i = 0; $i < count($eqLogics); $i++) {
            if ($eqLogics[$i]->getIsEnable()) {
                $list['eqLogic_id::'.$i] = $eqLogics[$i]->getId();
                $list['eqLogic_hostname::'.$i] = $eqLogics[$i]->getConfiguration('hostname');
            }
        }
        if (!is_object($listener = self::getPluginListeners())) {
            $listener = new listener();
            $listener->setClass(__CLASS__);
            $listener->setFunction('sendEvent');
        }
        $listener->setOption($list);
        $listener->emptyEvent();
        if ($broker['plugin'] == 'jMQTT') {
            $eqBrokers = jMQTT::getBrokers();
            $eqNonBrokers = jMQTT::getNonBrokers();
            foreach ($eqBrokers as $eqB) { // For each Broker
                foreach ($eqNonBrokers[$eqB->getId()] as $eqL) { //for each eqL of that broker
                    if ($eqL->getIsEnable()) {
                        if (strpos($eqL->getTopic(), $broker['topic']) !== false) { //check if match the topic
                            log::add(__CLASS__, 'debug', 'EqLogic jMQTT found with topic : ' . $broker['topic']. ' => '  . json_encode(utils::o2a($eqL)));
                            foreach ($eqL->getCmd('info') as $allCmd) {
                                log::add(__CLASS__, 'debug', ' listener cmds search => '  . json_encode(utils::o2a($allCmd)));
                              //trouver la commande avec le topic receivedFrom
                                if ($allCmd->getConfiguration('jsonPath') == '[receivedFrom]') {
                                    log::add(__CLASS__, 'debug', 'listener cmds found => '  . json_encode(utils::o2a($allCmd)));
                                    $listener->addEvent('#'.$allCmd->getId().'#');
                                } else {
                                    $value = $allCmd->execCmd();
                                    if (!empty($value)) {
                                        $jsonArray = $allCmd->decodeJsonMsg($value);
                                        if (isset($jsonArray)) {
                                            if (isset($jsonArray['receivedFrom'])) {
                                                $listener->addEvent('#'.$allCmd->getId().'#');
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } elseif ($broker['plugin'] == 'mqtt2') {
            foreach (eqLogic::byType('mqtt2') as $eqL) {
                if ($eqL->getIsEnable()) {
                    if (strpos($eqL->getLogicalId(), $broker['topic']) !== false) { //check if match the topic
                        log::add(__CLASS__, 'debug', 'EqLogic jMQTT found with topic5 : ' . $broker['topic']. ' => '  . json_encode(utils::o2a($eqL)));
                        foreach ($eqL->getCmd('info') as $allCmd) {
                            log::add(__CLASS__, 'debug', 'cmds search => '  . json_encode(utils::o2a($allCmd)));
                            if (strpos($allCmd->getLogicalId(), 'receivedFrom') !== false) {
                                log::add(__CLASS__, 'debug', 'cmds found => '  . json_encode(utils::o2a($allCmd)));
					            $listener->addEvent('#'.$allCmd->getId().'#');
                            }
                        }
                    }
                }
            }
        } elseif ($broker['plugin'] == 'MQTT') {
            foreach (eqLogic::byType('MQTT') as $eqL) {
                if ($eqL->getIsEnable()) {
                    if (strpos($eqL->getConfiguration('topic'), $broker['topic']) !== false) { //check if match the topic
                        log::add(__CLASS__, 'debug', 'EqLogic jMQTT found with topic5 : ' . $broker['topic']. ' => '  . json_encode(utils::o2a($eqL)));
                        foreach ($eqL->getCmd('info') as $allCmd) {
                            log::add(__CLASS__, 'debug', 'cmds search => '  . json_encode(utils::o2a($allCmd)));
                            if (strpos($allCmd->getConfiguration('topic'), '{receivedFrom}') !== false) {
                                log::add(__CLASS__, 'debug', 'cmds found => '  . json_encode(utils::o2a($allCmd)));
					            $listener->addEvent('#'.$allCmd->getId().'#');
                            }
                        }
                    }
                }
            }
        } elseif ($broker['plugin'] == 'external') {

        }
        $listener->save();
    }

    /**
     * Édite le fichier de configuration .env
     *
     * @param string $_dir Le répertoire dans lequel le fichier .env doit être créé
     * @param bool $_debug Indique si le mode de débogage doit être activé
     *
     * @return array Les commandes à exécuter pour éditer le fichier de configuration .env
     */
    public static function editEnvConfigFile($_dir, $_debug = false) {
        $cred = self::getBrokerInfos();

        $cmd = ["sh -c 'cat > $_dir/blea2mqtt/.env' << EOL
MQTT_HOSTNAME={$cred['ip']}
MQTT_PORT={$cred['port']}
MQTT_USERNAME={$cred['user']}
MQTT_PASSWORD={$cred['password']}
MQTT_TOPIC={$cred['topic']}
MACHINE_NAME=\$(hostname)
BLE_DEBUG=".($_debug ? 'true' : 'false')."
EOL
",
        "cat {$_dir}/blea2mqtt/.env"
        ];
        return $cmd;
    }

    /*
    * Permet de modifier l'affichage du widget (également utilisable par les commandes)
    public function toHtml($_version = 'dashboard') {}
    */

    /*
    * Permet de déclencher une action avant modification d'une variable de configuration du plugin
    * Exemple avec la variable "param3"
    public static function preConfig_param3( $value ) {
      // do some checks or modify on $value
      return $value;
    }
    */

    /*
    * Permet de déclencher une action après modification d'une variable de configuration du plugin
    * Exemple avec la variable "param3"
    public static function postConfig_param3($value) {
      // no return value
    }
    */

    /*     * **********************Getteur Setteur*************************** */

}

class blea2mqttCmd extends cmd {
    /*     * *************************Attributs****************************** */

    /**
     * Liste des types de widgets disponibles pour les équipements de cette classe.
     *
     * @var array
     */
    public static $_widgetPossibility = array();

    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
    * Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
    public function dontRemoveCmd() {
      return true;
    }
    */

    /**
     *
     * @param array $_options Les options à passer à l'action.
     *
     * @throws Exception Si l'action demandée n'est pas reconnue.
     */
    public function execute($_options = array()) {

        $eqLogic = $this->getEqLogic();
        $system = $eqLogic->getConfiguration('system');
        log::add('blea2mqtt', 'debug', __FUNCTION__ . __(' action sur : ',__FILE__) . $this->getLogicalId() . __(', system : ', __FILE__) . $system .  __(' et options : ', __FILE__) . json_encode($_options));
        $execCmd = array();

        switch ($this->getLogicalId()) {
            case 'startService':
                if ($system == 'Linux') {
                    $execCmd[] = blea2mqtt::getSystemctlCommand('start');
                } elseif ($system == 'Darwin') {
                    $execCmd[] = blea2mqtt::getLaunchctlCommand('bootstrap');
                }
                break;
            case 'stopService':
                if ($system == 'Linux') {
                    $execCmd[] = blea2mqtt::getSystemctlCommand('stop');
                } elseif ($system == 'Darwin') {
                    $execCmd[] = blea2mqtt::getLaunchctlCommand('bootout');
                }
                break;
            case 'restartService':
                if ($system == 'Linux') {
                    $execCmd[] = blea2mqtt::getSystemctlCommand('restart');
                } elseif ($system == 'Darwin') {
                    $execCmd[] = blea2mqtt::getLaunchctlCommand('kickstart');
                }
                break;
            case 'activateService':
                if ($system == 'Linux') {
                    $execCmd[] = blea2mqtt::getSystemctlCommand('enable');
                } elseif ($system == 'Darwin') {
                    $execCmd[] = blea2mqtt::getLaunchctlCommand('bootstrap');
                }
                break;
            case 'deactivateService':
                if ($system == 'Linux') {
                    $execCmd[] = blea2mqtt::getSystemctlCommand('disable');
                } elseif ($system == 'Darwin') {
                    $execCmd[] = blea2mqtt::getLaunchctlCommand('bootout');
                }
                break;
            case 'createService':
                if ($system == 'Linux') {
                    $execCmd[] = 'systemctl daemon-reload';
                    $execCmd[] = blea2mqtt::getSystemctlCommand('enable');
                    $execCmd[] = blea2mqtt::getSystemctlCommand('restart');
                } elseif ($system == 'Darwin') {
                    $execCmd[] = 'bash -c \'' . dirname(__FILE__) . '/../../resources/install_brew.sh "' . $eqLogic->getConfiguration('pwd') . '" "' . $eqLogic->getConfiguration('user') . '"\'';
                    $execCmd[] = blea2mqtt::getLaunchctlCommand('bootstrap');
                }
                break;
            default:
                throw new Exception("Action inconnue : " . $this->getLogicalId());
        }

        log::add('blea2mqtt', 'debug', __FUNCTION__ . __(' getLaunchctlCommandgetLaunchctlCommand : ',__FILE__) . json_encode($execCmd));
        if (count($execCmd) > 0) {
            $exec = $eqLogic->sendRequest('CMD', $execCmd, false, true);
            log::add('blea2mqtt', 'debug', __FUNCTION__ . __(' result : ',__FILE__) . json_encode($exec));
            $eqLogic->getServiceStatus($system);
        }
    }

    public function decodeJsonMsg($payload) {
		$jsonArray = json_decode($payload, true);
		if (is_array($jsonArray) && json_last_error() == JSON_ERROR_NONE) {
			return $jsonArray;
        } else {
			if (json_last_error() == JSON_ERROR_NONE) {
                log::add('blea2mqtt', 'info', __FUNCTION__ . __(' Problème de format JSON sur la commande #%s#: Le message reçu n\'est pas au format JSON: ',__FILE__) . $this->getHumanName());
            } else {
                log::add('blea2mqtt', 'warning', __FUNCTION__ . sprintf(__("Problème de format JSON sur la commande #%1\$s#: %2\$s (%3\$d)", __FILE__), $this->getHumanName(), json_last_error_msg(), json_last_error()));
            }
            return null;
		}
	}
    /*     * **********************Getteur Setteur*************************** */

}
