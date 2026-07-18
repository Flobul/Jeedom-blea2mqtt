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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

include_file('core', 'authentification', 'php');
if (!isConnect('admin')) {
  include_file('desktop', '404', 'php');
  die();
}

$return = blea2mqtt::getBrokerFromJeedom();
$plugin = plugin::byId('blea2mqtt');
$update = $plugin->getUpdate();

?>
<form class="form-horizontal" id="configuration_plugin_blea2mqtt">
  <fieldset>
    <div class="form-group">
      <legend><i class="fas fa-info-circle"></i> {{Général}}</legend>
      <div class="col-lg-4">
        <?php if (is_object($update)) { ?>
          <div><label>{{Branche}} :</label> <span class="label label-info"><?php echo htmlspecialchars($update->getConfiguration('version', 'stable')); ?></span></div>
          <div><label>{{Source}} :</label> <?php echo htmlspecialchars($update->getSource()); ?></div>
          <div><label>{{Version}} :</label> <?php echo htmlspecialchars($update->getLocalVersion()); ?></div>
        <?php } ?>
      </div>
      <div class="col-lg-6">
        <a class="btn btn-success btn-sm" target="_blank" rel="noopener noreferrer" href="<?php echo htmlspecialchars($plugin->getDocumentation()); ?>"><i class="fas fa-book"></i> {{Documentation}}</a>
        <a class="btn btn-default btn-sm" target="_blank" rel="noopener noreferrer" href="<?php echo htmlspecialchars($plugin->getChangelog()); ?>"><i class="fas fa-list"></i> {{Changelog}}</a>
      </div>
    </div>
    <legend><i class="fas fa-rss"></i>{{Paramètre du broker MQTT}}</legend>
    <div class="col-lg-6">
      <div class="form-group">
        <label class="col-md-4 control-label">{{Template de publication}}
          <sup><i class="fas fa-question-circle tooltips" title="{{Template de publication des évènements blea2mqtt}}"></i></sup>
        </label>
        <div class="col-md-7">
          <input class="configKey form-control" data-l1key="publish_template">
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-4 control-label">{{Broker MQTT}}
          <sup><i class="fas fa-question-circle tooltips" title="{{Broker MQTT sur lequel sera transmis les données de blea2mqtt}}"></i></sup>
        </label>
        <div class="col-md-7">
            <select id="sel_mqttBroker" class="configKey form-control" data-l1key="broker_id">
                <option value="">{{Aucun}}</option>
					<?php
                       if (is_array($return)) {
                           foreach ($return as $mqttPlugin) {
                               $mqttPlugin['eq_id'] = isset($mqttPlugin['eq_id']) ? $mqttPlugin['eq_id'] : '';
                               echo '<option value="' . $mqttPlugin['plugin'] . '::' . $mqttPlugin['eq_id'] . '">' . $mqttPlugin['plugin'] . ' > [' . $mqttPlugin['ip'] . ':'. $mqttPlugin['port'] . ']</option>';
                           }
                       }
					?>
            </select>
        </div>
        <div id="manualBroker" class="form-group">
          <label class="col-lg-4 control-label">{{Adresse du broker}}
            <sup><i class="fa fa-question-circle tooltips" title="{{Paramètres d'accès au Broker.}}"></i>
            </sup>
          </label>
          <div class="col-lg-7 input-group">
            <span class="input-group-btn">
              <select class="configKey form-control roundedLeft tooltips" data-l1key="mqttProto" style="width:80px;"
                title="{{Choisir quel protocole attend le Broker pour la communication}}.<br />{{Pour plus d'information, se référer à la documentation.}}">
                  <option>mqtt</option>
                  <option>mqtts</option>
                  <option>ws</option>
                  <option>wss</option>
              </select>
            </span>
            <span class="input-group-addon">:
              <input class="configKey form-control tooltips" data-l1key="mqttAddress" placeholder="localhost" title="{{Adresse IP ou nom de domaine du Broker}}.<br/>{{Valeur si vide, 'localhost' (donc la machine hébergeant Jeedom).}}">
              <span class="input-group-addon">:</span>
              <input class="configKey form-control tooltips jmqttPort roundedRight" data-l1key="mqttPort" type="number" min="1" max="65535" placeholder="port" title="{{Port réseau sur lequel écoute le Broker}}.<br/>{{Valeur si vide, 1883 en mqtt, 8883 en mqtts, 1884 en ws et 8884 en wss.}}">
              <span class="input-group-addon jmqttWsUrl" style="display:none">/</span>
              <input class="configKey form-control tooltips roundedRight jmqttWsUrl" data-l1key="mqttWsUrl" style="display:none" placeholder="{{mqtt}}" title="{{URL de la connexion Web Sockets du serveur distant, sans '/' initial}}.<br />{{Valeur si vide, 'mqtt'. Ne pas modifier si vous ne savez pas ce que vous faites.}}">
            </span>
          </div>

          <div class="form-group">
            <label class="col-lg-4 control-label">{{Authentification}}
              <sup><i class="fa fa-question-circle tooltips" title="{{Nom d'utilisateur et Mot de passe permettant de se connecter au Broker.<br/>Remplir ces champs n'est obligatoire que si le Broker est configuré pour.}}"></i>
              </sup>
            </label>
            <div class="col-lg-7 input-group">
              <input class="configKey form-control roundedLeft" data-l1key="mqttUser" autocomplete="nope" autofill="off" placeholder="{{Nom d'utilisateur}}" />
              <span class="input-group-addon">:</span>
              <input class="configKey form-control roundedRight" data-l1key="mqttPass" type="password" autocomplete="nope" autofill="off" placeholder="{{Mot de passe}}" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </fieldset>
</form>

<script>
  const mqttProtocol = document.querySelector('.configKey[data-l1key="mqttProto"]');
  const mqttPort = document.querySelector('.configKey[data-l1key="mqttPort"]');
  const wsFields = document.querySelectorAll('.jmqttWsUrl');
  const tlsField = document.getElementById('jmqttTls');

  function updateMqttProtocol() {
      if (!mqttProtocol || !mqttPort) return;
      switch (mqttProtocol.value) {
          case 'mqtts':
              mqttPort.classList.add('roundedRight');
              mqttPort.placeholder = '8883';
              wsFields.forEach(element => element.style.display = 'none');
              if (tlsField) tlsField.style.display = '';
              break;
          case 'ws':
              mqttPort.classList.remove('roundedRight');
              mqttPort.placeholder = '1884';
              wsFields.forEach(element => element.style.display = '');
              if (tlsField) tlsField.style.display = 'none';
              break;
          case 'wss':
              mqttPort.classList.remove('roundedRight');
              mqttPort.placeholder = '8884';
              wsFields.forEach(element => element.style.display = '');
              if (tlsField) tlsField.style.display = '';
              break;
          default:
              mqttPort.classList.add('roundedRight');
              mqttPort.placeholder = '1883';
              wsFields.forEach(element => element.style.display = 'none');
              if (tlsField) tlsField.style.display = 'none';
              break;
      }
  }

  function updateBrokerMode() {
    const broker = document.getElementById('sel_mqttBroker');
    const manualBroker = document.getElementById('manualBroker');
    if (broker && manualBroker) manualBroker.style.display = broker.value === '' ? '' : 'none';
  }

  mqttProtocol?.addEventListener('change', updateMqttProtocol);
  document.getElementById('sel_mqttBroker')?.addEventListener('change', updateBrokerMode);
  updateMqttProtocol();
  updateBrokerMode();
</script>
