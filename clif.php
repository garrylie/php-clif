<?php

/**
 * PHP Command Line Functions
 * 
 * @author garry
 * @version 1.02 (November 12, 2025)
 * @link https://github.com/garrylie/php-clif
 */

$GLOBALS['clif_start'] = microtime(true);

register_shutdown_function('clif_shutdown');

function clif_shutdown() {
	printf("Script execution time: %s\n", cc('italic 159', round(microtime(true) - $GLOBALS['clif_start'], 3) . ' sec.'));
}

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

function fatal_error($errstr, $str2 = '') {
	$cols = intval(exec('tput cols'));

	$errtitle = 'Fatal Error';

	if (empty($errstr)) $errstr = 'Неизвестная ошибка';
	if (!empty($str2)) {
		$errtitle = $errstr;
		$errstr = $str2;
	}
	$errstr_length = mb_strlen($errstr);
	if ($errstr_length > $cols)
		$errstr_length = $cols - 4;
	$errstr_length += 2;

	$border_color = 196;

	printf(
		"\n%s %s %s%s%s\n",
		cc($border_color, '╭─['),
		cc('bold underline 226', $errtitle),
		cc($border_color, ']'),
		cc($border_color, str_repeat('─', $errstr_length - mb_strlen($errtitle) - 5)),
		cc($border_color, '╮')
	);
	if (mb_strlen($errstr) > $cols - 4) {
		$arrstr = str_split($errstr, $cols - 4);
		foreach ($arrstr as $errstr) {
			printf(
				"%s %s%s %s\n",
				cc($border_color, '│'),
				cc('230 italic', $errstr),
				str_repeat(' ', $cols - mb_strlen($errstr) - 4),
				cc($border_color, '│')
			);
		}
	} else {
		printf(
			"%s %s %s\n",
			cc($border_color, '│'),
			cc('230 italic', $errstr),
			cc($border_color, '│')
		);
	}
	printf(
		"%s%s%s\n",
		cc($border_color, '╰'),
		cc($border_color, str_repeat('─', $errstr_length)),
		cc($border_color, '╯')
	);
	exit();
}

function script_init($title = null, $description = null) {

	global $argv;

	if (empty($title)) {
		if (!empty($argv[0]))
			$title = $argv[0];
		else 
			$title = $_SERVER['PHP_SCRIPT'];
	}

	motd($title, $description);

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
				if (!empty($modes)) {
					$last_mod = end($modes);
					if (empty($arguments[$last_mod]))
						$arguments[$last_mod] = $arg;
				}
				$parameter = $arg;
			}
		}

		define('SCRIPT_MODS', $modes);
		define('SCRIPT_ARGS', $arguments);
		define('SCRIPT_PARAM', $parameter);
	}

}

function motd($title, $description = null) {

	$cols = intval(`tput cols`);
	$length = mb_strlen($title);

	if ($length > $cols)
		$length = $cols - 2;

	$length += 2;

	$title_color = 'bold 226';

	if (empty($description)) {
		printf("╭%s╮\n", str_repeat('─', $length));
		printf("│ %s │\n", cc($title_color, $title));
		printf("╰%s╯\n", str_repeat('─', $length));
	} else {

		if (is_array($description))
			$description = implode("\n", $description);

		$max_desc_length = 0;
		foreach (explode("\n", $description) as $description_line) {
			$desc_length = mb_strlen($description_line);
			if ($desc_length > $cols)
				$desc_length = $cols - 2;
			$desc_length += 2;
			if ($desc_length > $max_desc_length)
				$max_desc_length = $desc_length;
		}

		printf("╭─[ %s ]%s╮\n", cc($title_color, $title), str_repeat('─', $max_desc_length - $length - 3));
		foreach (explode("\n", $description) as $description_line)
			printf("│ %s%s │\n", cc('229 italic', $description_line), str_repeat(' ', $max_desc_length - mb_strlen($description_line) - 2));
		printf("╰%s╯\n", str_repeat('─', $max_desc_length));
	}

}

function hasMode($mode) {
	if (!defined('SCRIPT_MODS')) fatal_error('Script is not initialized');
	if (!SCRIPT_MODS) return false;
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

/**
 * ╔════════╗
 * ║ mysqli ║
 * ╚════════╝
 */

function db_connect($login, $password, $dbname = null, $host = 'localhost') {
	$start = microtime(true);
	printf("[mysqli] Connecting to database as %s", cc(226, $login));

	if (empty($dbname)) $dbname = $login;
	$GLOBALS['clif_mysqli'] = new mysqli($host, $login, $password, $dbname);
	if ($GLOBALS['clif_mysqli']->connect_error) {
		print(PHP_EOL);
		fatal_error('mysqli connection error', $GLOBALS['clif_mysqli']->connect_error);
	}
	$time = microtime(true) - $start;
	$sec = 0;
	for ($i=3; $i <= 7; $i++)
		if (!$sec)
			$sec = round($time, $i);
		else 
			break;
	printf(": %s sec.\n", $sec);

}

function db_query($sql, ...$param) {
	if (empty($GLOBALS['clif_mysqli'])) fatal_error('Database connection not initialized!');
	try {
		if ($statement = $GLOBALS['clif_mysqli']->prepare($sql)) {
			$bind = array();
			if ($param) {
				foreach ($param as $key => $value) {
					$bind[] = &$param[$key];
				}
				call_user_func_array(array($statement, 'bind_param'), $bind);
			}
			if ($statement->execute()) {

				if (!preg_match('/^(SELECT|DESC|SHOW)/', ltrim($sql)))
					return mysqli_affected_rows($GLOBALS['clif_mysqli']);

				if ($result = $statement->get_result()) {
					if (!$result->num_rows) return 0;
					return mysqli_fetch_all($result, MYSQLI_ASSOC);
				} else {
					return false;
				}
			} else {
				printf("SQL: %s\n\nParameters:\n%s\n\n", cc(50, $sql), print_r($param, true));
				fatal_error('Query execution error', $statement->error);
			}
		} else {
			printf("SQL: %s\n\nParameters:\n%s\n\n", cc(50, $sql), print_r($param, true));
			fatal_error('Query execution error', $GLOBALS['clif_mysqli']->error);
		}
	} catch (Exception $e) {
		fatal_error($e->getMessage());
	}
}

function db_last_insert_id() {
	if (empty($GLOBALS['clif_mysqli'])) fatal_error('Database connection not initialized!');
	if ($result = $GLOBALS['clif_mysqli']->query("SELECT LAST_INSERT_ID() AS LAST_INSERT_ID")) {
		if ($object = $result->fetch_object()) {
			return $object->LAST_INSERT_ID;
		}
	}
	return null;
}
