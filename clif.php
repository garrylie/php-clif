<?php

/**
 * PHP Command Line Functions
 * 
 * @author garry
 * @version 1.00
 * @link https://github.com/garrylie/php-clif
 */

function cc($format = 0, $text = '') {
	if (empty($format)) return "\e[0m";
	if (defined('CRON') && CRON) return $text;
	$color_reset = $text ? "\e[0m" : '';
	if (is_numeric($format)) return "\e[38;5;".$format.'m'.$text.$color_reset;
	if (!is_array($format)) $format = explode(' ', $format);
	$codes = ['bold' => 1,'italic' => 3,'underline' => 4,'strikethrough' => 9,'default' => 39];
	$formatMap = array_map(function ($code) use ($codes) {
		if (is_numeric($code)) return '38;5;' . $code;
		if (preg_match('/^bg:(\d+)$/', $code, $m)) return '48;5;' . $m[1];
		if (!array_key_exists($code, $codes)) die("\e[38;5;196m" . "cc: Invalid code: [" . $code . "]\e[0m\n");
		return $codes[$code];
	}, $format);
	return "\e[".implode(';',$formatMap).'m'.$text.$color_reset;
}

function trans_choice($titles, $number, $delimiter = '/') {
	$abs = abs($number);
	$cases = [2,0,1,1,1,2];
	return sprintf(explode($delimiter, $titles)[ ($abs%100 > 4 && $abs%100 < 20) ? 2 : $cases[min($abs%10, 5)] ], $number);
}

function human_filesize($bytes, $dec = 2) {
	$size = array('байт', 'КБ', 'МБ', 'ГБ');
	$factor = floor((strlen($bytes) - 1) / 3);
	if ($factor == 0) $dec = 0;
	return sprintf("%.{$dec}f %s", $bytes / (1024 ** $factor), $size[$factor]);
}

function cd(...$vars) {
	print(cc(50));
	var_dump(...$vars);
	print(cc());
	print(PHP_EOL);
	exit();
}

function print_loading_bar($i, $total, $label = '', $finish_message = '', $brackets = '[]', $bar_symbol = '#', $space_symbol = '.') {
	/* version 23.10.2025 */
	if (empty($GLOBALS['__loading_bar__cols'])) {
		$GLOBALS['__loading_bar__cols'] = intval(exec('tput cols'));
	}
	if (empty($GLOBALS['__loading_bar__width'])) {
		$GLOBALS['__loading_bar__width'] = $GLOBALS['__loading_bar__cols'];
	}
	$cols = $GLOBALS['__loading_bar__cols'];
	$width = $GLOBALS['__loading_bar__width'];

	if (empty($GLOBALS['__loading_bar__label'])) {
		$GLOBALS['__loading_bar__label'] = '';
	}

	if ($GLOBALS['__loading_bar__label'] !== $label) {
		$space = str_repeat(' ', $cols);
		if (empty($GLOBALS['__loading_bar__init'])) {
			echo "\r{$space}\r{$label}\n";
			$GLOBALS['__loading_bar__init'] = true;
		}
		else 
			echo chr(27) . "[0G" . chr(27) . "[1A\r{$space}\r{$label}\n";
	}

	$GLOBALS['__loading_bar__label'] = $label;

	$ratio = $i / $total;
	$percent = ceil( $ratio * 100 );

	$bars_total = $width - 2 /* brackets "[]" */ - 4 /* percent " 1%" */;
	if ($finish_message) {
		$space = str_repeat(' ', $cols);
		echo chr(27) . "[0G" . chr(27) . "[1A\r{$space}\r{$finish_message}\n" . str_repeat(' ', $cols) . "\r";
		return;
	}
	$bars_current = round($ratio * $bars_total);
	$bars = str_repeat($bar_symbol, $bars_current);

	$empty_space = str_repeat($space_symbol, $bars_total - $bars_current);

	$percent_info = $percent . '%';
	if ($percent < 100) $percent_info .= ' ';

	$line = $percent_info . $brackets[0] . $bars . $empty_space . $brackets[1] . $finish_message;
	print("\r" . $line);

}

function _readline($prompt = null){
	if ($prompt) echo $prompt;
	$fp = fopen("php://stdin","r");
	$line = rtrim(fgets($fp, 1024));
	return $line;
}

function askToContinue($text = 'Продолжить?') {
	$line = _readline("\n$text (Y/N): ");
	if (strtolower($line) !== 'y') die('');
	echo "\n";
}

function fatal_error($str1, $str2 = '') {
	$cols = exec('tput cols');
	printf("%s\n", cc(196, str_repeat('-', $cols)));
	if (empty($str1)) $str1 = 'Неизвестная ошибка';
	if (empty($str2)) {
		printf("%s\n", cc(196, $str1));
	} else {
		printf("%s: %s\n", $str1, cc(196, $str2));
	}
	printf("%s\n", cc(196, str_repeat('-', $cols)));
	exit();
}

function script_init($title = null) {

	global $argv;

	if (empty($title)) {
		if (!empty($argv[0]))
			$title = $argv[0];
		else 
			$title = $_SERVER['PHP_SCRIPT'];
	}

	motd($title);

	if (!empty($argv)) {
		foreach ($argv as $key => $arg) {
			if (!$key) continue;
			if (preg_match('/^--(.+)$/', $arg, $m)) {
				if (preg_match('/^([^=]+)=([^=]+)$/', $m[1], $mm)) {
					$modes[] = $mm[1];
					$arguments[$mm[1]] = $mm[2];
				} else $modes[] = $m[1];
			} elseif (preg_match('/^-([^-]{1}.*)$/', $arg, $m)) {
				$modes = array_merge($modes, str_split($m[1]));
			} else {
				$last_mod = end($modes);
				if (empty($arguments[$last_mod]))
					$arguments[$last_mod] = $arg;
				$parameter = $arg;
			}
		}

		define('SCRIPT_MODS', $modes);
		define('SCRIPT_ARGS', $arguments);
		define('SCRIPT_PARAM', $parameter);
	}

}

function motd($title) {
	printf("Script initialized: %s\n", cc(226, $title));
}

function hasMode($mode) {
	if (!defined('SCRIPT_MODS')) fatal_error('Script is not initialized');
	if (is_array($mode)) {
		foreach ($mode as $m)
			if (in_array($m, SCRIPT_MODS)) return true;
		return false;
	} elseif (is_string($mode)) {
		return in_array($mode, SCRIPT_MODS);
	} else fatal_error('Неизвестный тип мода');
}

function isArgument($arg, $value) {
	if (!defined('SCRIPT_ARGS')) fatal_error('Script is not initialized');
	if (!array_key_exists($arg, SCRIPT_ARGS)) return false;
	return (SCRIPT_ARGS[$arg] == $value);
}

function getArgument($arg) {
	if (!defined('SCRIPT_ARGS')) fatal_error('Script is not initialized');
	if (is_string($arg)) {
		if (!array_key_exists($arg, SCRIPT_ARGS)) return false;
		return SCRIPT_ARGS[$arg];
	} elseif (is_array($arg)) {
		foreach ($arg as $a) {
			if (!array_key_exists($a, SCRIPT_ARGS)) continue;
			return SCRIPT_ARGS[$a];
		}
	}
	return false;
}

function getParameter() {
	if (!defined('SCRIPT_PARAM')) fatal_error('Script is not initialized');
	return SCRIPT_PARAM;
}
