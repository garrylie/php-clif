<?php

/**
 * PHP Command Line Functions
 * 
 * @author garry
 * @version 1.03 (November 19, 2025)
 * @link https://github.com/garrylie/php-clif
 */

$GLOBALS['clif_start'] = microtime(true);

error_reporting(0);
set_error_handler('clif_error_handler');
register_shutdown_function('clif_shutdown');

function clif_shutdown() {
	$errfile = "unknown file";
	$errstr  = "shutdown";
	$errno   = E_CORE_ERROR;
	$errline = 0;

	$error = error_get_last();


	if ($error !== NULL) {
		$errno   = $error["type"];
		$errfile = $error["file"];
		$errline = $error["line"];
		$errstr  = $error["message"];

		$errstr = preg_replace('~ in ' . preg_quote($errfile, '~') . ':' . $errline . '\nStack trace:\n.+~s', '', $errstr);

		clif_error_handler($errno, $errstr, $errfile, $errline);
	}
	printf("Script execution time: %s\n", cc('italic 159', round(microtime(true) - $GLOBALS['clif_start'], 3) . ' sec.'));
}

function clif_error_handler($errno, $errstr, $errfile, $errline) {
	$exit = true;
	if (preg_match('/^file_get_contents/', $errstr)) $exit = false;
	if (strpos($errstr, 'zlib_decode(): data error') !== false) $exit = false;
	switch ($errno) {
		case E_ERROR:
		$errtitle = 'PHP Error!';
		case E_ALL:
		case E_COMPILE_ERROR:
		case E_COMPILE_WARNING:
		case E_CORE_ERROR:
		case E_CORE_WARNING:
		case E_DEPRECATED:
		case E_NOTICE:
		case E_PARSE:
		case E_RECOVERABLE_ERROR:
		$errtitle = 'PHP Caught Exception!';
		break;
		case E_USER_DEPRECATED:
		case E_USER_ERROR:
		case E_USER_ERROR:
		$errtitle = 'PHP Error:';
		break;
		case E_USER_NOTICE:
		$errtitle = 'PHP Application Error:';
		break;
		case E_USER_WARNING:
		$errtitle = 'PHP Warning:';
		break;
		case E_WARNING:
		$errtitle = 'PHP Fatal error:';
		break;

		default: $errtitle = "Unknown error type: [$errno]";
		break;
	}


	$debug_backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
	$backtrace_data = [];
	foreach ($debug_backtrace as $b_index => $backtrace) {
		// if (!$b_index) continue;
		if (!empty($backtrace['file']) && !empty($backtrace['line']))
			$backtrace_data[] = "#{$b_index} {$backtrace['file']}({$backtrace['line']}): {$backtrace['function']}()";
		else
			$backtrace_data[] = "#{$b_index} {$backtrace['function']}()";
	}

	$border_color = 196;
	$errstr_color = '210 italic';
	$errtitle_color = 'bold underline 226';

	$all_text_data = [];
	$all_text_data = array_merge($all_text_data, explode("\n", $errstr));
	$all_text_data[] = sprintf('File: %s:%s', $errfile, $errline);
	$all_text_data = array_merge($all_text_data, $backtrace_data);

	// Calculating box width
	$cols = intval(`tput cols`);
	$box_length = 0;
	foreach ($all_text_data as $text) {
		$length = mb_strlen($text);
		if ($length > $cols - 4)
			$length = $cols - 4;
		
		if ($length > $box_length)
			$box_length = $length;
	}

	box_init($box_length, $cols, $border_color);
	box_header($errtitle, $errtitle_color);
	box_line($errstr, $errstr_color);

	// File: <file>:<line>
	$cc_filestr = sprintf('File: <cc format="177">%s</cc>:<cc format="117">%s</cc>', $errfile, $errline);
	box_line($cc_filestr, -1);

	// Box Divider
	$divider_text = 'Stack trace - Error #' . $errno;
	box_divider($divider_text, 227);

	// Stack trace
	foreach ($backtrace_data as $backtrace_string) {
		box_line($backtrace_string, 230);
	}

	$footer_text = sprintf('PHP %s (%s)', PHP_VERSION, PHP_OS);
	box_footer($footer_text, 'italic 224');

	if ($exit) exit();
}

function box_length(...$param) {

	$all_text_data = [];

	$cols = $param[0];
	unset($param[0]);
	$param = array_values($param);


	foreach ($param as $key => $p) {
		if (!$key) $p .= ' [] '; // header is separated by spaces and brackets
		if (is_array($p)) {
			foreach ($p as $s)
				$all_text_data = array_merge($all_text_data, explode("\n", $s));
		} else {
			$all_text_data = array_merge($all_text_data, explode("\n", $p));
			// $all_text_data[] = $p;
		}
	}

	// Calculating box width
	$box_length = 0;
	foreach ($all_text_data as $text) {
		$length = mb_strlen(cc_decode($text));
		if ($length > $box_length)
			$box_length = $length;
	}

	if ($box_length > $cols - 4)
		$box_length = $cols - 4;

	return $box_length;
	
}

function box_init($box_length, $cols, $border_color = 15) {
	$GLOBALS['global__clif_box_length']   = $box_length;
	$GLOBALS['global__clif_border_color'] = $border_color;
	$GLOBALS['global__clif_cols']         = $cols;
}

function box_header($text = null, $cc = 15) {
	$box_length   = $GLOBALS['global__clif_box_length'];
	$border_color = $GLOBALS['global__clif_border_color'];
	if ($text) {
		printf(
			"\n%s %s %s%s%s\n",
			cc($border_color, '╭─['),
			cc($cc, $text),
			cc($border_color, ']'),
			cc($border_color, str_repeat('─', $box_length - mb_strlen($text) - 3)),
			cc($border_color, '╮')
		);
	} else {
		printf(
			"\n%s%s%s\n",
			cc($border_color, '╭'),
			cc($border_color, str_repeat('─', $box_length + 2)),
			cc($border_color, '╮')
		);
	}
}

function box_footer($text = null, $cc = 15) {
	$box_length   = $GLOBALS['global__clif_box_length'];
	$border_color = $GLOBALS['global__clif_border_color'];
	if ($text) {
		printf(
			"%s%s%s %s %s\n",
			cc($border_color, '╰'),
			cc($border_color, str_repeat('─', $box_length - mb_strlen($text) - 3)),
			cc($border_color, '['),
			cc($cc, $text),
			cc($border_color, ']─╯')
		);
	} else {
		printf(
			"%s%s%s\n",
			cc($border_color, '╰'),
			cc($border_color, str_repeat('─', $box_length + 2)),
			cc($border_color, '╯')
		);
	}
}

function box_line($data, $cc = 15) {
	$box_length   = $GLOBALS['global__clif_box_length'];
	$border_color = $GLOBALS['global__clif_border_color'];
	$cols         = $GLOBALS['global__clif_cols'];
	$lines = explode("\n", $data);
	foreach ($lines as $text) {
		if (mb_strlen($text) > $cols - 4) {
			$cc_split = cc_split($text, $cols - 4);
			foreach ($cc_split['cc_lines'] as $key => $cc_ml) {
				$line = $cc_split['lines'][$key];
				printf(
					"%s %s%s %s\n",
					cc($border_color, '│'),
					cc_decode($cc_ml, $cc),
					str_repeat(' ', $box_length - mb_strlen($line)),
					cc($border_color, '│')
				);
			}
		} else {
			$line = cc_strip_tags($text);
			printf(
				"%s %s%s %s\n",
				cc($border_color, '│'),
				cc_decode($text, $cc),
				str_repeat(' ', $box_length - mb_strlen($line)),
				cc($border_color, '│')
			);
		}
	}
}

function box_divider($text, $cc) {
	$box_length   = $GLOBALS['global__clif_box_length'];
	$border_color = $GLOBALS['global__clif_border_color'];
	if (empty($text)) {
		printf(
			"%s%s%s\n",
			cc($border_color, '├'),
			cc($border_color, str_repeat('─', $box_length + 2)),
			cc($border_color, '┤')
		);
	} else {
		printf(
			"%s %s %s%s%s\n",
			cc($border_color, '├─['),
			cc($cc, $text),
			cc($border_color, ']'),
			cc($border_color, str_repeat('─', $box_length - mb_strlen($text) - 3)),
			cc($border_color, '┤')
		);
	}
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

function cc_split($ml, $max_length) {
	if (!preg_match_all('~</?cc[^>]*>~s', $ml, $matches, PREG_OFFSET_CAPTURE)) {
		$split = str_split($ml, $max_length);
		return ['lines' => $split, 'cc_lines' => $split];
	}
	$length = strlen($ml); // this is important to NOT USE mb_strlen here!
	$state = 0;
	/**
	 * state:
	 * 0 - plain text
	 * 1 - we've met opened cc tag
	 */
	$lines         = [];
	$line          = '';
	$cc_lines      = [];
	$cc_line       = '';
	$end_point     = -1;
	$is_tag_opened = false;
	$opened_tag    = '';
	for ($i = 0; $i < $length; $i++) { 

		foreach ($matches[0] as $key => $match) {
			if ($i == $match[1]) {
				if (strpos($match[0], '<cc format="') === 0) {
					$is_tag_opened = true;
					$opened_tag = $match[0];
					$end_point = $i + mb_strlen($match[0]); // index where we should switch to state 0 again (end of a tag + next char)
					$cc_line .= $match[0];
					$state = 1;
				}
				else { // </cc>
					$is_tag_opened = false;
					$end_point = $i + mb_strlen($match[0]); // index where we should switch to state 0 again (end of a tag + next char)
					$cc_line .= $match[0];
					$state = 2;
				}
				break;
			}
			if ($i == $end_point)
				$state = 0;
		}

		switch ($state) {
			case 0:
				$line    .= $ml[$i];
				$cc_line .= $ml[$i];
				break;
			
			case 1:

				break;

			case 2:
				break;
		}

		if (mb_strlen($line) == $max_length && ord($ml[$i]) !== 208 /* not UTF-8 divider */) {
			if ($is_tag_opened)
				$cc_line .= '</cc>';
			$lines[]    = $line;
			$cc_lines[] = $cc_line;
			$line       = '';
			$cc_line    = '';
			if ($is_tag_opened)
				$cc_line = $opened_tag;
		}
	}
	if (!empty($line)) {
		$lines[] = $line;
		$cc_lines[] = $cc_line;
	}
	return [
		'lines'    => $lines,
		'cc_lines' => $cc_lines,
	];
}

function cc_decode($ml, $cc = null) { // <cc format="210">RED TEXT</cc>
	if (empty($ml)) return '';
	if (!preg_match_all('~<cc format="([^"]+)">(.+?)</cc>~us', $ml, $matches)) {
		if ($cc)
			return cc($cc, $ml);
		else
			return $ml;
	}
	$result = $ml;
	foreach ($matches[0] as $key => $match) {
		$format = $matches[1][$key];
		$text   = $matches[2][$key];
		$result = preg_replace('~' . preg_quote($match, '~') . '~', cc($format, $text), $result);
	}
	return $result;
}

function cc_strip_tags($string) {
	return preg_replace('~</?cc[^>]*>~', '', $string);
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

function print_loading_bar($i, $total, $label = '', $finish_message = '') {
	/* version 20.11.2025 */
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

	$bar_symbol = '▊';

	
	//╭╮╰─╯│

	$label_color     = 220;
	$percent_color   = 202;
	$status_color    = 122;
	$empty_bar_color = 236;
	$bar_color       = 33;

	$ratio = $i / $total;
	$percent = ceil( $ratio * 100 );
	$percent_info = $percent . '%';
	// if ($percent < 100) $percent_info .= ' ';

	if ($GLOBALS['__loading_bar__label'] !== $label) {
		$space = str_repeat(' ', $cols);
		$status = sprintf('%d/%d', $i, $total);
		$label_text = $label;
		$max_label_length = $cols - (mb_strlen($percent_info) + mb_strlen($status)) - 17;
		if (mb_strlen($label_text) >= $max_label_length)
			$label_text = mb_substr($label, 0, $max_label_length - 4) . '...';
			// $label_text = mb_substr($label, 1) . '_';
		$line_length = $cols - mb_strlen($label_text) - mb_strlen($percent_info) - mb_strlen($status) - 17;
		$label_line = '╭─[ ' . cc($percent_color, $percent_info) . ' ]─[ ' . cc($status_color, $status) . ' ]─[ ' . cc($label_color, $label_text) . ' ]' . str_repeat('─', $line_length) . '╮';
		if (empty($GLOBALS['__loading_bar__init'])) {
			echo "\r{$space}\r{$label_line}\n";
			$GLOBALS['__loading_bar__init'] = true;
		}
		else 
			echo chr(27) . "[0G" . chr(27) . "[2A\r{$space}\r{$label_line}\n";
	}

	$GLOBALS['__loading_bar__label'] = $label;

	$bars_total = $width /* brackets "[]" */ - 4 /* percent " 1%" */;
	if ($finish_message) {
		$space = str_repeat(' ', $cols);
		echo "\033[0G\033[1A\r{$space}\r{$finish_message}\n" . str_repeat(' ', $cols) . "\r";
		unset($GLOBALS['__loading_bar__cols']);
		// Show the cursor
		echo "\033[?25h";
		return;
	}
	$bars_current = round($ratio * $bars_total);
	$bars = str_repeat($bar_symbol, $bars_current);

	$empty_space = str_repeat($bar_symbol, $bars_total - $bars_current);


	$line = '│ ' . cc($bar_color, $bars) . cc($empty_bar_color, $empty_space) . ' │';
	print("\r" . $line);
	printf("╰%s╯", str_repeat('─', $cols - 2));
	// Move cursor
	// echo "\033[0G\033[1A\033[" . ($bars_total - $bars_current + 1) . 'D';

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

	$box_length = box_length($cols, $errtitle, $errstr);

	box_init($box_length, $cols, 196);
	box_header($errtitle, 'bold underline 226');
	box_line($errstr, '230 italic');
	box_footer();
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

	$modes = [];
	$arguments = [];
	$parameter = null;

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

		$box_length = box_length($cols, $title, $description);
		box_init($box_length, $cols);
		box_header();
		box_line('- ' . $title . ' -', $title_color);
		box_footer();

	} else {


		$box_length = box_length($cols, $title, $description);
		box_init($box_length, $cols);
		box_header($title, $title_color);
		if (is_array($description)) {
			foreach ($description as $line) {
				box_line($line, '229 italic');
			}
		} else
			box_line($description, '229 italic');
		box_footer();
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
