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
?>
<style>
    .scanHender{
        cursor: pointer !important;
        width: 100%;
    }
</style>

<table class="table table-condensed tablesorter" id="table_healthblea2mqtt">
	<thead>
		<tr>
			<th>{{Appareil}}</th>
			<th>{{Id}}</th>
			<th>{{Système}}</th>
			<th>{{Librairie}}</th>
			<th>{{Date de de dernière actualisation}}</th>
			<th>{{Date d'ajout}}</th>
			<th>{{Date création}}</th>
		</tr>
	</thead>
	<tbody>
      <?php

        foreach ($eqLogics as $eqLogic) {
          echo '<tr><td><a href="' . $eqLogic->getLinkToConfiguration() . '" style="text-decoration: none;">' . $eqLogic->getHumanName(true) . '</a></td>';

          echo '<td>' . $eqLogic->getConfiguration('name') . '</td>';

          echo '<td><span class="label label-success" style="font-size : 1em; cursor : default;">'.$eqLogic->getConfiguration('system', 'N/A').'</span></td>';
          
          $lib = $eqLogic->getConfiguration('library', 'N/A');
          echo "<td><span class='label label-info' style='font-size: 1em; cursor: default;'>$lib</span></td>";
          
          $status = $eqLogic->getStatus('lastReceivedFrom');
          $temps = time() - strtotime($status);
          if ($temps > 720) {
              $lastProd = '    <i class="fas fa-times" title="{{Déconnecté}}</br>{{Depuis }}'.$temps.'{{ s}}" style="color:red"></i>';
          } else {
              $lastProd = '    <i class="fas fa-wifi" title="{{Connecté}}" style="color:green"></i>';
          }
          //$lastProd = $lastProd ? '<span class="label label-info" style="font-size: 1em; cursor: default;">' . date('Y-m-d H:i:s', $lastProd) . '</span>' : '<span class="label label-danger" style="font-size: 1em; cursor: default;">{{N/A}}</span>';
          echo "<td>$lastProd $status</td>";

          echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getStatus('lastCommunication') . '</span></td>';
          echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getConfiguration('createtime') . '</span></td></tr>';
        }
      ?>
	</tbody>
</table>