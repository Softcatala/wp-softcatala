<?php

class Summer_Converter extends WP_CLI_Command {

	public function __invoke() {

		$converters = array(
			new Links_Converter(),
			new Programa_Converter(),
			new Esdeveniment_Converter(),
			new Main_Image_Post_Converter()
		);

		foreach ($converters as $converter) {
			$converter->__invoke();
		}
	}
}