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
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}

$return = blea2mqtt::getBrokerFromJeedom();

?>
<form class="form-horizontal">
  <fieldset>
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
                               $mqttPlugin['eq_id'] = $mqttPlugin['eq_id'] ? $mqttPlugin['eq_id'] : '';
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
  $('.configKey[data-l1key=mode]').off('change').on('change', function() {
    $('.blea2mqttMode').hide()
    $('.blea2mqttMode.' + $(this).value()).show()
  })

  $('#bt_blea2mqttRestartMosquitto').off('click').on('click', function() {
    $.ajax({
      type: "POST",
      url: "plugins/blea2mqtt/core/ajax/blea2mqtt.ajax.php",
      data: {
        action: "restartMosquitto"
      },
      dataType: 'json',
      error: function(error) {
        $.fn.showAlert({
          message: error.message,
          level: 'danger'
        })
      },
      success: function(data) {
        if (data.state != 'ok') {
          $.fn.showAlert({
            message: data.result,
            level: 'danger'
          })
          return
        } else {
          $('.pluginDisplayCard[data-plugin_id=' + $('#span_plugin_id').text() + ']').click()
          $.fn.showAlert({
            message: '{{Redemarrage réussie}}',
            level: 'success',
            emptyBefore: true
          })
        }
      }
    })
  })

  $('#bt_blea2mqttInstallMosquitto').off('click').on('click', function() {
    $.ajax({
      type: "POST",
      url: "plugins/blea2mqtt/core/ajax/blea2mqtt.ajax.php",
      data: {
        action: "installMosquitto"
      },
      dataType: 'json',
      error: function(error) {
        $.fn.showAlert({
          message: error.message,
          level: 'danger'
        })
      },
      success: function(data) {
        if (data.state != 'ok') {
          $.fn.showAlert({
            message: data.result,
            level: 'danger'
          })
          return
        } else {
          $('.pluginDisplayCard[data-plugin_id=' + $('#span_plugin_id').text() + ']').click()
          $.fn.showAlert({
            message: '{{Installation réussie}}',
            level: 'success',
            emptyBefore: true
          })

        }
      }
    })
  })

  $('#bt_blea2mqttUninstallMosquitto').off('click').on('click', function() {
    bootbox.confirm('{{Confirmez-vous la désinstallation du broker Mosquitto local?}}', function(result) {
      if (result) {
        $.ajax({
          type: "POST",
          url: "plugins/blea2mqtt/core/ajax/blea2mqtt.ajax.php",
          data: {
            action: "uninstallMosquitto"
          },
          dataType: 'json',
          error: function(error) {
            $.fn.showAlert({
              message: error.message,
              level: 'danger'
            })
          },
          success: function(data) {
            if (data.state != 'ok') {
              $.fn.showAlert({
                message: data.result,
                level: 'danger'
              })
              return
            } else {
              $.fn.showAlert({
                message: '{{Désinstallation réussie}}',
                level: 'success',
                emptyBefore: true
              })

            }
          }
        })
      }
    })
  })

  $('.configKey[data-l1key=mqttProto]').change(function(){
      switch ($(this).val()) {
          case 'mqtts':
              $('.configKey[data-l1key=mqttPort]').addClass('roundedRight').attr('placeholder', '8883');
              $('.jmqttWsUrl').hide();
              $('#jmqttTls').show();
              break;
          case 'ws':
              $('.configKey[data-l1key=mqttPort]').removeClass('roundedRight').attr('placeholder', '1884');
              $('.jmqttWsUrl').show();
              $('#jmqttTls').hide();
              break;
          case 'wss':
              $('.configKey[data-l1key=mqttPort]').removeClass('roundedRight').attr('placeholder', '8884');
              $('.jmqttWsUrl').show();
              $('#jmqttTls').show();
              break;
          default: // mqtt
              $('.configKey[data-l1key=mqttPort]').addClass('roundedRight').attr('placeholder', '1883');
              $('.jmqttWsUrl').hide();
              $('#jmqttTls').hide();
              break;
      }
  });

  $('#sel_mqttBroker').change(function(e) {
    if ($(this).value() == '') {
        $('#manualBroker').show();
    } else {
        $('#manualBroker').hide();
    }
    console.log($(this).value())
  });
</script>