<?

function load_languages($Document = false, $Lang = false) {
	global $Languages, $LoggedUser;
	
	if(empty($Lang)) {
		if(!empty($_REQUEST['lang'])) {
			$Lang = $_REQUEST['lang'];
		} elseif(!empty($LoggedUser['Language'])) {
			$Lang = $LoggedUser['Language'];
		}
	}

	if(empty($Lang) || !array_key_exists($Lang, $Languages)) {
		$Lang = DEFAULT_LOCALE;
	}

	/*
		Load languages in the following order:
			1. Global default language
			2. Special default language
			3. Global given language (if given)
			4. Special given language (if given)
		Each overriding things set in the locale before.
	*/
	
	$Translations = array();

	//Always load the default language
	if(file_exists(SERVER_ROOT.'/locales/'.DEFAULT_LOCALE.'/'.DEFAULT_LOCALE.'php')) {
		include(SERVER_ROOT.'/locales/'.DEFAULT_LOCALE.'/'.DEFAULT_LOCALE.'php');
		$Translations = $Language;
	}


	//Load special language file, overrides global
	if(!empty($Document)) {
		if(file_exists(SERVER_ROOT.'/locales/'.DEFAULT_LOCALE.'/'.$Document.'.php')) {
			include(SERVER_ROOT.'/locales/'.DEFAULT_LOCALE.'/'.$Document.'.php');
			$Translations = $Language + $Translations;
		}
	}

	if($Lang != DEFAULT_LOCALE) {
		//If langauge not the fallback lang, try and load the given language.
		if(file_exists(SERVER_ROOT.'/locales/'.$Lang.'/'.$Lang.'.php')) {
			//Load global language file
			include(SERVER_ROOT.'/locales/'.$Lang.'/'.$Lang.'.php');
			$Translations = $Language + $Translations;
		}

		//Load special language file, overrides global
		if(!empty($Document)) {
			if(file_exists(SERVER_ROOT.'/locales/'.$Lang.'/'.$Document.'.php')) {
				include(SERVER_ROOT.'/locales/'.$Lang.'/'.$Document.'.php');
				$Translations = $Language + $Translations;
			}
		}
	}

	return $Translations;
}


/**
 * Translate a given word / phrase.
 * @param $Translate The word / phrase to translate.
 * @param $Lang When false, translates to the user's selected language, else forces translation to the given language.
 * @param $Escape Whether or not to escape the translation.
 * @param $WasTranslated See return.
 * @return If $WasTranslated is true, returns whether the word / phrase was actually translated, else returns the translation.
 */

function L($Translate, $Dictionary = false, $Escape = true, $WasTranslated = false) {
	global $Translations;

	if($Dictionary) {
		$UseTranslations = $Dictionary;
	} else {
		$UseTranslations = $Translations;
	}

	if($WasTranslated) {
		return !empty($UseTranslations[$Translate]);
	} else {
		$Translated = empty($UseTranslations[$Translate]) ? $Translate : $UseTranslations[$Translate];
		return $Escape ? display_str($Translated) : $Translated;
	}
}
//</strip>
	
