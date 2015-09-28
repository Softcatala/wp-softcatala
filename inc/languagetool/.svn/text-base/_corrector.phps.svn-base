	<?php
		
		drupal_add_js("languagetool/js/fancybox/jquery.fancybox-1.3.4.pack.js");
		drupal_add_css("languagetool/js/fancybox/jquery.fancybox-1.3.4.css"); 
		drupal_add_js("languagetool/online-check/tiny_mce/tiny_mce.js"); 
		drupal_add_js("languagetool/online-check/tiny_mce/plugins/atd-tinymce/editor_plugin.js");
		drupal_add_js("languagetool/languagetool.js");
		drupal_add_css("languagetool/languagetool.css");
		
		$test_txt = "Aixi tot el que digui o diga ell açí; penso, pens i pense. Cafè o café. Desprès digué que se n'havien després. La teva i la meua. Dóna-li'ls, dóna'ls-hi. I que ens ho agraesca. Què es celebra? És d’una ingenuïtat que ratlla la imprudència. Li ha infringit una severa derrota. Este home i eixa dona. Aquesta dona i aquest home exigixen i exigeixen una resposta.";
	?>

	<p>
	Aquest servei permet trobar <span class="hiddenSpellError">errors ortogràfics</span>, <span class="hiddenGrammarError">errors gramaticals o tipogràfics</span>, <span class="hiddenGreenError">recomanacions d'estil</span>. 
	</p>
	<p>
	La variant de corrector «<strong>general</strong>» recull les opcions més comunes d'arreu del domini lingüístic, mentre que la de corrector «<strong>valencià</strong>» afegeix a l'anterior formes específiques valencianes, com ara certes accentuacions o terminacions nominals.
	</p>
	<p>
	Enganxeu els vostres textos en el camp del formulari, i feu clic sobre «Comprova el text». El corrector us mostrarà els possibles <span class="hiddenSpellError">errors ortogràfics en vermell</span>, <span class="hiddenGrammarError">errors gramaticals o tipogràfics en blau</span> i <span class="hiddenGreenError">recomanacions d'estil en verd</span>. Fent clic sobre les paraules assenyalades us mostrarà una <strong>llista de suggeriments</strong>.
	</p>
	<p>
	Aquest corrector és una ajuda en la millora de la qualitat de textos en català. Els seus resultats són orientatius i en cap cas poden usar-se com a reemplaçament d'una correcció feta per un entès.
	</p>

	<div class="lin5"><img src="/img/shim.gif" alt="Softcatalà" longdesc="Separador" /></div>

	<div id="corrector" class="ngrid690">
		<form name="checkform" action="http://community.languagetool.org" method="post">
			<p id="checktextpara">
				<textarea id="checktext" name="text" style="width: 100%" rows="6"><?php echo $test_txt; ?></textarea>
			</p>

			<div style="margin-top:2px;position:relative;">
				<input type="hidden" name="lang" value="ca"/>
				<div style="margin-top:0px; margin-left: 30px; display:block; float: left;">
					Formes 
					<input type="radio" name="formes" value="formes_generals" checked id="check_formes_generals">generals
					<input type="radio" name="formes" value="formes_valencianes" id="check_formes_valencianes">valencianes
					<input type="radio" name="formes" value="formes_balears" id="check_formes_balears">balears
				</div>

				<div style="margin-top:0px; display:block; float: right;">
					<input type="submit" id="submit" name="_action_checkText" value="Comprova el text">
				</div>
				<div style="margin-top:0px; margin-left: 30px; display:block; float: left;">
					<input type="checkbox" id="mesopcions" value="mesopcions">Mostra més opcions 
					<div id="mes_opcions" style="display:none; background-color:#BDBDBD;">
						<input type="checkbox" name="SE_DAVANT_SC" value="SE_DAVANT_SC" checked>Exigeix 'se' (se sap, se celebra)<br/>
						<input type="checkbox" name="CA_UNPAIRED_QUESTION" value="CA_UNPAIRED_QUESTION">Exigeix signe d'interrogació inicial (¿)<br/>

						<div id="opcions_valencia" style="margin-top:0px; display:block; background-color:#BDBDBD; float: left;display:none;" >   	
							<table summary="" border="0">
								<tr>
									<td>Accentuació:</td>
									<td><input type="radio" name="accentuacio" value="accentuacio_general" checked>general (cafè)</td>
									<td><input type="radio" name="accentuacio" value="accentuacio_valenciana">valenciana (café)</td>
								</tr>
								<tr>
									<td>Verbs incoatius:</td>
									<td><input type="radio" name="incoatius" value="incoatius_eix" checked>-eix (serveix)</td>
									<td><input type="radio" name="incoatius" value="incoatius_ix">-ix (servix)</td>
								</tr>
								<tr>
								<td></td>
									<td><input type="radio" name="incoatius2" value="incoatius_esc">-esc (servesc)</td>
									<td><input type="radio" name="incoatius2" value="incoatius_isc" checked>-isc (servisc)</td>
								</tr>
								<tr>
									<td>Demostratius:</td>
									<td><input type="radio" name="demostratius" value="demostratius_aquest" checked>aquest</td>
									<td><input type="radio" name="demostratius" value="demostratius_este">este</td>
							</tr>
							</table>  
						</div>
					</div>
				</div>
				<div style="clear:both"> </div>
			</div>

		</form>
	</div>
