# Plugin blea2mqtt

Le plugin blea2mqtt permet d'installer le paquet ["blea2mqtt"](https://github.com/Flobul/blea2mqtt) sur des machines distantes accessibles en SSH et d'intégrer les périphériques Bluetooth Low Energy (BLE) dans le système Jeedom en utilisant le protocole MQTT pour communiquer avec les périphériques BLE.
Le but principal étant d'uniformiser et d'automatiser l'installation pour créer des antennes BLEA qui soient compatibles avec les publications Tasmota.

Le plugin ne contient que les équipements "antenne" et les commandes permettant de relancer le service blea2mqtt.

Pour récupérer les informations des sondes et autres périphérique BLE, il est nécessaire d'utiliser un plugin gérant les brokers MQTT.

## Configuration requise

- Jeedom version 4.2.0 ou supérieure.
- Un broker MQTT fonctionnel (par exemple, Mosquitto, ou l'un des 3 plugins Jeedom : jMQTT, MQTT ou mqtt2).
- Une machine "antenne" sous Linux ou MacOS.

## Fonctionnalités

- Découverte automatique des périphériques BLE. (dans la limite des périphériques reconnus par ["blea2mqtt"](https://github.com/Flobul/blea2mqtt))
- Envoyer les informations de vos équipements BLEA à un broker MQTT.
- Possibilité d'activer/désactiver le service MQTT directement depuis le plugin
- Prise en charge des systèmes Linux et macOS

## Installation

1. Installer le plugin blea2mqtt via le market Jeedom ou depuis le Github.
2. Configurer le template de publication, par défaut "tasmota_ble" afin de correspondre au topic de Tasmota et créer un réseau maillé.
3. Configurer son broker MQTT : possibilité de le sélectionner directement s'il est sur ce Jeedom, ou d'entrer les informations manuellement.
4. Ajouter un équipement, et configurer les informations de son antenn (renseigner l'adresse IP, port SSH, utilisateur, mot de passe, répertoire d'installation...) et sauvegarder.
5. Lancer l'installation des dépendances et suivre l'avancée sur la fenêtre de log (remonter toute anomalie au développeur).

## Configuration

### Configuration du plugin

- **Sélectionner le broker :** issu de jMQTT, ou MQTT ou MQTT manager.

ou entrer manuellement les informations :

- **Adresse du broker MQTT :** adresse IP ou nom de domaine de votre broker MQTT.
- **Port du broker MQTT :** port de votre broker MQTT (par défaut 1883).
- **Identifiant :** identifiant si nécessaire pour se connecter à votre broker MQTT.
- **Mot de passe :** mot de passe si nécessaire pour se connecter à votre broker MQTT.
- **Topic de publication :** topic sur lequel les informations des équipements BLEA sont publiées.

### Configuration des équipements

Les périphériques BLE doivent être configurés dans le plugin jMQTT, MQTT ou MQTT Manager.

### Configuration Tasmota

Pour créer un réseau maillé et diffuser sur le même topic, les appareils sous Tasmota ont besoin d'avoir l'option BLE : mi32option6 à 1.

Il suffit de créer une commande action sur l'appareil, avec en topic "cmnd/mi32option6" avec la valeur à 1 et de lancer l'action.

Ou d'entrer dans les logs de la page Web de Tasmota la commande "mi32option6 1".

Cela aura pour rôle de demander au Tasmota de publier pour chaque périphérique tasmota sur le topic "tasmota_ble".

## Utilisation

Une fois le plugin blea2mqtt configuré et le service activé, les informations de vos équipements BLEA seront envoyées sur le broker MQTT sur le topic configuré ("tasmota_ble", par défaut).

## Contribuer

Si vous souhaitez contribuer au développement de ce plugin, vous pouvez :

- Créer des "issues" pour signaler des bugs ou proposer des améliorations.
- Proposer des "pull requests" pour ajouter des fonctionnalités ou corriger des bugs.


## Licence

Ce plugin est distribué sous licence GNU GENERAL PUBLIC LICENSE.
