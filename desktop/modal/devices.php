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

if (!isConnect('admin')) {
    throw new Exception('401 Unauthorized');
}
$eqLogics = blea2mqtt::byType('blea2mqtt');
$broker = blea2mqtt::getBrokerInfos();
?>
<style>
    .scanHender{
        cursor: pointer !important;
        width: 100%;
    }

    #table_devicesBlea2mqtt .eqLogicAttr > ul > li {
        display: inline-block;
    }
</style>

<table class="table table-condensed tablesorter" id="table_devicesBlea2mqtt">
	<thead>
		<tr>
			<th>{{Nom}}</th>
			<th>{{MAC}}</th>
			<th>{{Batterie}}</th>
			<th style="width:150px;">{{Antenne}}</th>
			<th>{{RSSI}}</th>
			<th>{{Dernière communication}}</th>
		</tr>
	</thead>
	<tbody>
      <?php

        $allMqtt = array();
        if ($broker['plugin'] == 'jMQTT') {
            $eqBrokers = jMQTT::getBrokers();
            $eqNonBrokers = jMQTT::getNonBrokers();
            foreach ($eqBrokers as $eqB) { // For each Broker
                foreach ($eqNonBrokers[$eqB->getId()] as $eqL) { //for each eqL of that broker
                    if ($eqL->getIsEnable()) {
                        if (strpos($eqL->getTopic(), $broker['topic']) !== false) { //check if match the topic
                            foreach ($eqL->getCmd('info') as $allCmd) {
                              //trouver la commande avec le topic receivedFrom
                                if ($allCmd->getConfiguration('jsonPath') == '[receivedFrom]') {
                                    $allMqtt[$eqL->getId()]['receivedFrom'] = array(
                                        'value' => $allCmd->execCmd(),
                                        'id' => $allCmd->getId()
                                    );
                                } else if ($allCmd->getConfiguration('jsonPath') == '[mac]') {
                                    $allMqtt[$eqL->getId()]['mac'] = array(
                                        'value' => $allCmd->execCmd(),
                                        'id' => $allCmd->getId()
                                    );
                                } else if ($allCmd->getConfiguration('jsonPath') == '[Battery]') {
                                    $allMqtt[$eqL->getId()]['Battery'] = array(
                                        'value' => $allCmd->execCmd(),
                                        'id' => $allCmd->getId()
                                    );
                                } else if ($allCmd->getConfiguration('jsonPath') == '[Time]') {
                                    $allMqtt[$eqL->getId()]['Time'] = array(
                                        'value' => $allCmd->execCmd(),
                                        'id' => $allCmd->getId()
                                    );
                                } else if ($allCmd->getConfiguration('jsonPath') == '[RSSI]') {
                                    $allMqtt[$eqL->getId()]['RSSI'] = array(
                                        'value' => $allCmd->execCmd(),
                                        'id' => $allCmd->getId()
                                    );
                                } else {
                                    $value = $allCmd->execCmd();
                                    if (!empty($value)) {
                                        $jsonArray = $allCmd->decodeJsonMsg($value);
                                        if (isset($jsonArray)) {
                                            if (isset($jsonArray['receivedFrom'])) {
                                                $allMqtt[$eqL->getId()]['receivedFrom'] = array(
                                                    'value' => $jsonArray['receivedFrom'],
                                                    'id' => $allCmd->getId()
                                                );
                                            } else if (isset($jsonArray['mac'])) {
                                                $allMqtt[$eqL->getId()]['mac'] = array(
                                                    'value' => $jsonArray['mac'],
                                                    'id' => $allCmd->getId()
                                                );
                                            } else if (isset($jsonArray['Battery'])) {
                                                $allMqtt[$eqL->getId()]['Battery'] = array(
                                                    'value' => $jsonArray['Battery'],
                                                    'id' => $allCmd->getId()
                                                );
                                            } else if (isset($jsonArray['Time'])) {
                                                $allMqtt[$eqL->getId()]['Time'] = array(
                                                    'value' => $jsonArray['Time'],
                                                    'id' => $allCmd->getId()
                                                );
                                            }  else if (isset($jsonArray['RSSI'])) {
                                                $allMqtt[$eqL->getId()]['RSSI'] = array(
                                                    'value' => $jsonArray['RSSI'],
                                                    'id' => $allCmd->getId()
                                                );
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
                        foreach ($eqL->getCmd('info') as $allCmd) {
                            if (strpos($allCmd->getLogicalId(), 'receivedFrom') !== false) {
                                $allMqtt[$eqL->getId()]['receivedFrom'] = array(
                                    'value' => $allCmd->execCmd(),
                                    'id' => $allCmd->getId()
                                );
                            } else if (strpos($allCmd->getLogicalId(), 'mac') !== false) {
                                $allMqtt[$eqL->getId()]['mac'] = array(
                                    'value' => $allCmd->execCmd(),
                                    'id' => $allCmd->getId()
                                );
                            } else if (strpos($allCmd->getLogicalId(), 'Battery') !== false) {
                                $allMqtt[$eqL->getId()]['Battery'] = array(
                                    'value' => $allCmd->execCmd(),
                                    'id' => $allCmd->getId()
                                );
                            } else if (strpos($allCmd->getLogicalId(), 'Time') !== false) {
                                $allMqtt[$eqL->getId()]['Time'] = array(
                                    'value' => $allCmd->execCmd(),
                                    'id' => $allCmd->getId()
                                );
                            } else if (strpos($allCmd->getLogicalId(), 'RSSI') !== false) {
                                $allMqtt[$eqL->getId()]['RSSI'] = array(
                                    'value' => $allCmd->execCmd(),
                                    'id' => $allCmd->getId()
                                );
                            }
                        }
                    }
                }
            }
        } elseif ($broker['plugin'] == 'MQTT') {
            foreach (eqLogic::byType('MQTT') as $eqL) {
                if ($eqL->getIsEnable()) {
                    if (strpos($eqL->getConfiguration('topic'), $broker['topic']) !== false) { //check if match the topic
                        foreach ($eqL->getCmd('info') as $allCmd) {
                            if (strpos($allCmd->getConfiguration('topic'), '{receivedFrom}') !== false) {
                                $allMqtt[$eqL->getId()]['receivedFrom'] = array(
                                    'value' => $allCmd->execCmd(),
                                    'id' => $allCmd->getId()
                                );
                            } else if (strpos($allCmd->getConfiguration('topic'), '{mac}') !== false) {
                                $allMqtt[$eqL->getId()]['mac'] = array(
                                    'value' => $allCmd->execCmd(),
                                    'id' => $allCmd->getId()
                                );
                            } else if (strpos($allCmd->getConfiguration('topic'), '{Battery}') !== false) {
                                $allMqtt[$eqL->getId()]['Battery'] = array(
                                    'value' => $allCmd->execCmd(),
                                    'id' => $allCmd->getId()
                                );
                            } else if (strpos($allCmd->getConfiguration('topic'), '{Time}') !== false) {
                                $allMqtt[$eqL->getId()]['Time'] = array(
                                    'value' => $allCmd->execCmd(),
                                    'id' => $allCmd->getId()
                                );
                            } else if (strpos($allCmd->getConfiguration('topic'), '{RSSI}') !== false) {
                                $allMqtt[$eqL->getId()]['RSSI'] = array(
                                    'value' => $allCmd->execCmd(),
                                    'id' => $allCmd->getId()
                                );
                            }
                        }
                    }
                }
            }
        }
        foreach ($allMqtt as $eqId => $eqArray) {
            $eqLogicMqtt = eqLogic::byId($eqId);
            echo '<tr data-eq_id="'.$eqId.'"><td><a href="' . $eqLogicMqtt->getLinkToConfiguration() . '" style="text-decoration: none;">' . $eqLogicMqtt->getHumanName(true) . '</a></td>';

            echo '<td>' . $eqArray['mac']['value'] . '</td>';

            $label = $eqArray['Battery']['value'] < 30 ? 'danger' : $eqArray['Battery']['value'] < 50 ? 'warning' : 'success';
            echo "<td><span class='label label-{$label} eqLogicAttr simple' style='font-size: 1em; cursor: default;' data-cmd_id='".$eqArray['Battery']['id']."'>" . $eqArray['Battery']['value'] . " %</span></td>";
             $rssiClass = $eqArray['RSSI']['value'] <= -150 ? 'none' : $eqArray['RSSI']['value'] <= -90 ? 'danger': $eqArray['RSSI']['value'] <= -80 ? 'warning' : 'info';

            echo '<td class="label label-'.$rssiClass.'"><span class="eqLogicAttr custom rssi" data-cmd_id="'.$eqArray['RSSI']['id'].'">' . $eqArray['RSSI']['value'] . ' dBm</span>';
            echo "<span class='eqLogicAttr custom antenna' data-value='".$eqArray['receivedFrom']['value']."' data-cmd_id='".$eqArray['receivedFrom']['id']."'>(" . $eqArray['receivedFrom']['value'] . ")</span></td>";

            echo '<td><span class="label label-info eqLogicAttr simple" style="font-size : 1em; cursor : default;" data-cmd_id="'.$eqArray['Time']['id'].'">' . $eqArray['Time']['value'] . '</span></td></tr>';
        }
      ?>
	</tbody>
</table>
<script>
    // Store the latest RSSI values for each device and antenna
    var deviceData = {};

    // Function to update or create a row with the best RSSI values for each device
    function updateRow(data) {
        var tableBody = document.querySelector('#table_devicesBlea2mqtt tbody');

        // Check if the row for this eq_id already exists
        var existingRow = tableBody.querySelector(`[data-eq_id="${data.eq_id}"]`);
        if (existingRow) {
            existingRow.querySelector('.rssi').parentNode.classList.remove('label','label-none', 'label-danger', 'label-warning', 'label-info');
            // Update the existing row
            var rssiElement = existingRow.querySelector('.rssi');
            var antennaElement = existingRow.querySelector('.antenna');
            var timeElement = existingRow.querySelector('td:nth-child(5)');

            if (rssiElement && antennaElement && timeElement) {
                // Update the RSSI value
                rssiElement.innerText = `${data.rssi} dBm`;
                rssiElement.unseen();
                // Function to get the signal class based on RSSI value
                var rssiClass = data.rssi <= -150 ? 'none' : data.rssi <= -90 ? 'danger': data.rssi <= -80 ? 'warning' : 'info';
                          // Add new signal class based on RSSI value
                // Update the antenna data for this device
                if (!deviceData[data.eq_id]) {
                    deviceData[data.eq_id] = {};
                }
                deviceData[data.eq_id][data.receivedFrom] = `${data.rssi} dBm (${data.receivedFrom})`;

                // Clear the current contents of the antenna element
                antennaElement.innerHTML = '';

                // Create a new unordered list for the antenna data
                var antennaList = document.createElement('span');
                antennaList.style.paddingLeft = '0';
                antennaList.style.listStyleType = 'none';

                // Update the antenna element with the latest RSSI values for each antenna
                for (let key in deviceData[data.eq_id]) {
                    var listItem = document.createElement('span');
                    listItem.textContent = deviceData[data.eq_id][key];
                    listItem.classList.remove('label-none', 'label-danger', 'label-warning', 'label-info');
                          // Add new signal class based on RSSI value
                    listItem.classList.add('label','label-'+rssiClass);
                    antennaList.appendChild(listItem);
                }

                // Append the updated list to the antenna element
                antennaElement.appendChild(antennaList);

                // Update the time element
                timeElement.innerHTML = `<span class='label label-info eqLogicAttr simple'>${data.time}</span>`;
            }
        } else {
            // Create a new row
            var row = document.createElement('tr');
            row.setAttribute('data-eq_id', data.eq_id);

            var nameCell = document.createElement('td');
            var macCell = document.createElement('td');
            var batteryCell = document.createElement('td');
            var rssiCell = document.createElement('td');
            var antennaCell = document.createElement('td');
            var timeCell = document.createElement('td');

            nameCell.innerHTML = `<a href="${data.link}" style="text-decoration: none;">${data.name}</a>`;
            macCell.textContent = data.mac;
            batteryCell.innerHTML = `<span class='label label-success eqLogicAttr simple' style='font-size: 1em; cursor: default;'>${data.battery} %</span>`;
            rssiCell.innerHTML = `<span class='label label-info eqLogicAttr custom rssi' style='font-size: 0.9em; cursor: default; padding: 0px 5px;'>${data.rssi} dBm</span>`;
            antennaCell.innerHTML = `<span class='label label-info eqLogicAttr custom antenna' style='font-size: 0.9em; cursor: default; padding: 0px 5px;' data-value="${data.receivedFrom}"><span style="padding-left: 0; list-style-type: none;"><span>${data.rssi} dBm (${data.receivedFrom})</span></span></span>`;

            // Initialize the antenna data for this device
            deviceData[data.eq_id] = {};
            deviceData[data.eq_id][data.receivedFrom] = `${data.rssi} dBm (${data.receivedFrom})`;

            timeCell.innerHTML = `<span class='label label-info eqLogicAttr simple' style='font-size: 1em; cursor: default;'>${data.time}</span>`;

            row.appendChild(nameCell);
            row.appendChild(macCell);
            row.appendChild(batteryCell);
            row.appendChild(rssiCell);
            row.appendChild(antennaCell);
            row.appendChild(timeCell);

            tableBody.appendChild(row);
        }
    }

    // Function to handle updates
    function handleUpdate(eq_id, data) {
        updateRow(data); // Update the row with the new data
    }

    // Existing code to add update functions
    document.querySelectorAll('#table_devicesBlea2mqtt .eqLogicAttr.simple').forEach(function (element) {
        jeedom.cmd.addUpdateFunction(element.getAttribute('data-cmd_id'), function (_options) {
            var unit = _options.unit ? _options.unit : '';
            var simpleElement = document.querySelector('.eqLogicAttr.simple[data-cmd_id="' + _options.cmd_id + '"]');
            if (simpleElement) {
                simpleElement.innerText = _options.display_value + ' ' + unit;
            }
        });
    });

    document.querySelectorAll('#table_devicesBlea2mqtt .eqLogicAttr.custom').forEach(function (element) {
        jeedom.cmd.addUpdateFunction(element.getAttribute('data-cmd_id'), function (_options) {
            var cmdId = _options.cmd_id;
            var unit = _options.unit ? _options.unit : '';

            // Ensure _options.display_value is a string
            var displayValue = typeof _options.display_value === 'string' ? _options.display_value : _options.display_value.toString();

            if (element.classList.contains('rssi')) {
                var rssi = parseInt(displayValue.split(' ')[0]); // Extract the RSSI value
                var eq_id = element.closest('tr').getAttribute('data-eq_id');
                var receivedFrom = element.closest('tr').querySelector('.antenna').getAttribute('data-value') || 'Unknown';

                // Update rssi and append the new receivedFrom if it's different
                var data = {
                    eq_id: eq_id, // Use eq_id to identify the row
                    link: element.closest('tr').querySelector('td a').href,
                    name: element.closest('tr').querySelector('td a').textContent,
                    mac: element.closest('tr').querySelector('td:nth-child(2)').textContent,
                    battery: element.closest('tr').querySelector('td:nth-child(3) .label').textContent.trim().replace(' %', ''),
                    rssi: rssi,
                    receivedFrom: receivedFrom,
                    time: new Date().toLocaleTimeString() // Update the time with the current time
                };
                handleUpdate(data.eq_id, data); // Handle the update with the new data
            }

            if (element.classList.contains('antenna')) {
                // Update the receivedFrom value and its display
                var eq_id = element.closest('tr').getAttribute('data-eq_id');
                var rssiValue = element.closest('tr').querySelector('.rssi').innerText.split(' ')[0];

                element.setAttribute('data-value', displayValue);

                // Initialize the antenna data for this device if it doesn't exist
                if (!deviceData[eq_id]) {
                    deviceData[eq_id] = {};
                }

                deviceData[eq_id][displayValue] = `${rssiValue} dBm (${displayValue})`;

                // Clear the current contents of the antenna element
                var antennaList = document.createElement('ul');
                antennaList.style.paddingLeft = '0';
                antennaList.style.listStyleType = 'none';

                // Update the antenna element with the latest RSSI values for each antenna
                for (let key in deviceData[eq_id]) {
                    var listItem = document.createElement('li');
                    listItem.textContent = deviceData[eq_id][key];
                    antennaList.appendChild(listItem);
                }

                element.innerHTML = '';
                element.appendChild(antennaList);

                var data = {
                    eq_id: eq_id, // Use eq_id to identify the row
                    link: element.closest('tr').querySelector('td a').href,
                    name: element.closest('tr').querySelector('td a').textContent,
                    mac: element.closest('tr').querySelector('td:nth-child(2)').textContent,
                    battery: element.closest('tr').querySelector('td:nth-child(3) .label').textContent.trim().replace(' %', ''),
                    rssi: rssiValue,
                    receivedFrom: displayValue,
                    time: new Date().toLocaleTimeString() // Update the time with the current time
                };
                handleUpdate(data.eq_id, data); // Handle the update with the new data
            }
        });
    });


</script>