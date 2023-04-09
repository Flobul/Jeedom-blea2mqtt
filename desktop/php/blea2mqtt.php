<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
// Déclaration des variables obligatoires
$plugin = plugin::byId('blea2mqtt');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>
<style>
span.hiddenAsCard i.fas.fa-sign-in-alt				{ margin-right:10px;vertical-align:top;margin-top:-3px;margin-left:-5px!important; }
span.hiddenAsCard i.fas.status-circle				{ margin-right:6px; }
span.blea2mqttIconStatus i.fas		 				{ top: 20px;font-size: 0.9em !important;position: absolute;margin-left: 20px; }

</style>

<div class="row row-overflow">
	<!-- Page d'accueil du plugin -->
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
		<!-- Boutons de gestion du plugin -->
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoPrimary" data-action="add">
				<i class="fas fa-plus-circle"></i>
				<br>
				<span>{{Ajouter}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
			</div>
		</div>
		<legend><i class="fas fa-broadcast-tower"></i> {{Mes antennes blea2mqtt}}</legend>
		<?php
		if (count($eqLogics) == 0) {
			echo '<br><div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Aucun équipement blea2mqtt trouvé, cliquer sur "Ajouter" pour commencer}}</div>';
		} else {
			// Champ de recherche
			echo '<div class="input-group" style="margin:5px;">';
			echo '<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic">';
			echo '<div class="input-group-btn">';
			echo '<a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>';
			echo '<a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>';
			echo '</div>';
			echo '</div>';
			// Liste des équipements du plugin
			echo '<div class="eqLogicThumbnailContainer">';
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo '<img src="' . $eqLogic->getImage() . '"/>';
				echo '<br>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '<span class="hiddenAsCard displayTableRight hidden">';

				echo ($eqLogic->getIsVisible() == 1) ? '<i class="fas fa-eye" title="{{Equipement visible}}"></i>' : '<i class="fas fa-eye-slash" title="{{Equipement non visible}}"></i>';
				echo '</span>';
                $status = $eqLogic->getStatus('lastReceivedFrom');
				echo '<span class="displayTableRight blea2mqttIconStatus">';
                $temps = time() - strtotime($status);
                if ($temps > 720) {
                    echo '    <i class="fas fa-times" title="{{Déconnecté}}</br>{{Depuis }}'.$temps.'{{ s}}" style="color:red"></i>';
                } else {
                    echo '    <i class="fas fa-wifi" title="{{Connecté}}" style="color:green"></i>';
                }
				echo '</span>';
				echo '</div>';
			}
			echo '</div>';
		}
		?>
	</div> <!-- /.eqLogicThumbnailDisplay -->

	<!-- Page de présentation de l'équipement -->
	<div class="col-xs-12 eqLogic" style="display: none;">
		<!-- barre de gestion de l'équipement -->
		<div class="input-group pull-right" style="display:inline-flex;">
			<span class="input-group-btn">
				<!-- Les balises <a></a> sont volontairement fermées à la ligne suivante pour éviter les espaces entre les boutons. Ne pas modifier -->
				<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
				</a><a class="btn btn-sm btn-default eqLogicAction" data-action="copy"><i class="fas fa-copy"></i><span class="hidden-xs">  {{Dupliquer}}</span>
				</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
				</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
				</a>
			</span>
		</div>
		<!-- Onglets -->
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-list"></i> {{Commandes}}</a></li>
		</ul>
		<div class="tab-content">
			<!-- Onglet de configuration de l'équipement -->
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<!-- Partie gauche de l'onglet "Equipements" -->
				<!-- Paramètres généraux et spécifiques de l'équipement -->
				<form class="form-horizontal">
					<fieldset>
						<div class="col-lg-6">
							<legend><i class="fas fa-wrench"></i> {{Paramètres généraux}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Nom de l'équipement}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display:none;">
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label" >{{Objet parent}}</label>
								<div class="col-sm-6">
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
										<option value="">{{Aucun}}</option>
										<?php
										$options = '';
										foreach ((jeeObject::buildTree(null, false)) as $object) {
											$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
										}
										echo $options;
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Catégorie}}</label>
								<div class="col-sm-6">
									<?php
									foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" >' . $value['name'];
										echo '</label>';
									}
									?>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Options}}</label>
								<div class="col-sm-6">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked>{{Activer}}</label>
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked>{{Visible}}</label>
								</div>
							</div>

							<legend><i class="fas fa-cogs"></i> {{Paramètres spécifiques}}</legend>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{Adresse IP}}</label>
                                <div class="col-sm-3">
                                    <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="ip"/>
                                </div>
                                <label class="col-sm-2 control-label">{{Port}}</label>
                                <div class="col-sm-3">
                                    <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="port"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{Utilisateur}}</label>
                                <div class="col-sm-3">
                                    <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="user"/>
                                </div>
                                <label class="col-sm-2 control-label">{{Mot de passe}}</label>
                                <div class="col-sm-3">
                                    <input type="password" class="eqLogicAttr form-control inputPassword" data-l1key="configuration" data-l2key="pwd"/>
                                </div>
                            </div>
	                        <div class="form-group">
 								<label class="col-sm-3 control-label">{{Répertoire racine d'installation}}</label>
 								<div class="col-sm-8">
 									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="homeDir" type="text" placeholder="{{Chemin complet du répertoire d'installation}}">
 								</div>
 							</div>
	                        <div class="form-group">
 								<label class="col-sm-3 control-label">{{Fichier clé publique}}</label>
 								<div class="col-sm-8">
 									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="pubkey" type="text" placeholder="{{Chemin complet vers le fichier contenant la clé publique}}">
 								</div>
 							</div>
 							<div class="form-group">
 								<label class="col-sm-3 control-label">{{Fichier clé privée}}</label>
 								<div class="col-sm-8">
 									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="privkey" type="text" placeholder="{{Chemin complet vers le fichier contenant la clé privée}}">
 								</div>
 							</div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{Nom de l'antenne}}</label>
                                <div class="col-sm-4">
                                    <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="name"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{Librairie blea2mqtt}}</label>
                                <div class="col-sm-4">
									<select id="sel_lib" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="library">
										<option value="<?php echo blea2mqtt::GITHUB_FLOBUL_BLEA2MQTT;?>">{{blea2mqtt pour antennes Tasmota}}</option>
										<option value="<?php echo blea2mqtt::GITHUB_BITKILL_BLEA2MQTT;?>">{{blea2mqtt}}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{Installation des dépendances}}</label>
                                <div class="col-sm-1">
                                    <a class="btn btn-warning eqLogicAttr" data-action="installDependancy"><i class="fas fa-spinner"></i> {{Installer}}</a>
                                </div>
                            </div>
						</div>

						<!-- Partie droite de l'onglet "Équipement" -->
						<!-- Affiche un champ de commentaire par défaut mais vous pouvez y mettre ce que vous voulez -->
						<div class="col-lg-6">
							<legend><i class="fas fa-info"></i> {{Informations}}</legend>
                              <div class="form-group">
								<label class="col-sm-4 control-label">{{Date de dernière installation des dépendances}}</label>
								<div class="col-sm-6">
									<span class="eqLogicAttr label label-info" data-l1key="configuration" data-l2key="lastDependancyInstall"></span>
								</div>
							  </div>
                              <div class="form-group">
								<label class="col-sm-4 control-label">{{Date de dernière valeur}}</label>
								<div class="col-sm-6">
									<span class="eqLogicAttr label label-info" data-l1key="status" data-l2key="lastReceivedFrom"></span>
								</div>
							  </div>

                              <div class="form-group">
								<label class="col-sm-4 control-label">{{Système d'exploitation}}</label>
								<div class="col-sm-6">
									<span class="eqLogicAttr label label-info" data-l1key="configuration" data-l2key="system"></span>
								</div>
							  </div>
                              <div class="form-group">
								<label class="col-sm-4 control-label">{{Nom d'hôte}}</label>
								<div class="col-sm-6">
									<span class="eqLogicAttr label label-info" data-l1key="configuration" data-l2key="hostname"></span>
								</div>
							  </div>
							  <div class="form-group">
								<label class="col-sm-4 control-label">{{Description}}</label>
								<div class="col-sm-6">
									<textarea class="form-control eqLogicAttr autogrow" data-l1key="comment"></textarea>
								</div>
							  </div>
						</div>
					</fieldset>
				</form>
			</div><!-- /.tabpanel #eqlogictab-->

			<!-- Onglet des commandes de l'équipement -->
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<a class="btn btn-default btn-sm pull-right cmdAction" data-action="add" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une commande}}</a>
				<br><br>
				<div class="table-responsive">
					<table id="table_cmd" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th class="hidden-xs" style="min-width:50px;width:70px;">ID</th>
								<th style="min-width:200px;width:350px;">{{Nom}}</th>
								<th>{{Type}}</th>
								<th style="min-width:260px;">{{Options}}</th>
								<th>{{Etat}}</th>
								<th style="min-width:80px;width:200px;">{{Actions}}</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div><!-- /.tabpanel #commandtab-->

		</div><!-- /.tab-content -->
	</div><!-- /.eqLogic -->
</div><!-- /.row row-overflow -->

<!-- Inclusion du fichier javascript du plugin (dossier, nom_du_fichier, extension_du_fichier, id_du_plugin) -->
<?php include_file('desktop', 'blea2mqtt', 'js', 'blea2mqtt');?>
<!-- Inclusion du fichier javascript du core - NE PAS MODIFIER NI SUPPRIMER -->
<?php include_file('core', 'plugin.template', 'js');?>
