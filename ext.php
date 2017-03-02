<?php

namespace imkingdavid\spoiler;

class ext extends \phpbb\extension\base
{
	/**
	* Overwrite enable_step to enable board rules notifications
	* before any included migrations are installed.
	*
	* @param mixed $old_state State returned by previous call of this method
	* @return mixed Returns false after last step, otherwise temporary state
	* @access public
	*/
	public function enable_step($old_state)
	{
		$this->db = $this->container->get('dbal.conn');
		$this->table_prefix = $this->container->getParameter('core.table_prefix');
		switch ($old_state)
		{
			case '': // Empty means nothing has run yet

				$this->insert_spoiler_bbcode();
				return 'spoiler';

			break;

			default:

				// Run parent enable step method
				return parent::enable_step($old_state);

			break;
		}
	}

	/**
	* Overwrite disable_step to disable board rules notifications
	* before the extension is disabled.
	*
	* @param mixed $old_state State returned by previous call of this method
	* @return mixed Returns false after last step, otherwise temporary state
	* @access public
	*/
	public function disable_step($old_state)
	{
		$this->db = $this->container->get('dbal.conn');
		$this->table_prefix = $this->container->getParameter('core.table_prefix');
		switch ($old_state)
		{
			case '': // Empty means nothing has run yet

				$this->delete_spoiler_bbcode();
				return 'spoiler';

			break;

			default:

				// Run parent disable step method
				return parent::disable_step($old_state);

			break;
		}
	}

	/**
	 * Custom function to add the spoiler bbcode in the database
	 *
	 * @return null
	 * @access protected
	 */
	protected function insert_spoiler_bbcode()
	{
		// This code is based somewhat loosely on ./phpBB/includes/acp/acp_bbcodes.php
		// That code needs to be refactored for easier reuse >:(
		$bbcode_tag = 'spoiler';
		$bbcode_match = '[spoiler]{TEXT}[/spoiler]';
		$bbcode_tpl = '<div class="spoiler_block"><div class="spoiler_title"><input class="button2 btnlite" type="button" value="{L_SHOW}" /></div><div class="spoiler_content">{TEXT}</div></div>';
		$bbcode_helpline = '[spoiler]hidden text[/spoiler]';
		$data = $this->build_regexp($bbcode_match, $bbcode_tpl);

		$sql_ary = array(
			'bbcode_tag'			=> $data['bbcode_tag'],
			'bbcode_match'			=> $bbcode_match,
			'bbcode_tpl'			=> $bbcode_tpl,
			'display_on_posting'	=> 1,
			'bbcode_helpline'		=> $bbcode_helpline,
			'first_pass_match'		=> $data['first_pass_match'],
			'first_pass_replace'	=> $data['first_pass_replace'],
			'second_pass_match'		=> $data['second_pass_match'],
			'second_pass_replace'	=> $data['second_pass_replace']
		);

		// Insert sample rule data
		$this->db->sql_query("INSERT INTO {$this->table_prefix}bbcodes " . $this->db->sql_build_array('INSERT', $sql_ary));
	}

	/**
	 * Custom function to add the spoiler bbcode in the database
	 *
	 * @return null
	 * @access protected
	 */
	protected function delete_spoiler_bbcode()
	{
		// Insert sample rule data
		$this->db->sql_query('DELETE FROM ' . $this->table_prefix . "bbcodes WHERE bbcode_tag = 'spoiler'");
	}

	/**
	 * Pulled directly from acp_bbcodes()
	 */
	protected function build_regexp(&$bbcode_match, &$bbcode_tpl)
	{
		$bbcode_match = trim($bbcode_match);
		$bbcode_tpl = trim($bbcode_tpl);

		// Allow unicode characters for URL|LOCAL_URL|RELATIVE_URL|INTTEXT tokens
		$utf8 = preg_match('/(URL|LOCAL_URL|RELATIVE_URL|INTTEXT)/', $bbcode_match);

		$fp_match = preg_quote($bbcode_match, '!');
		$fp_replace = preg_replace('#^\[(.*?)\]#', '[$1:$uid]', $bbcode_match);
		$fp_replace = preg_replace('#\[/(.*?)\]$#', '[/$1:$uid]', $fp_replace);

		$sp_match = preg_quote($bbcode_match, '!');
		$sp_match = preg_replace('#^\\\\\[(.*?)\\\\\]#', '\[$1:$uid\]', $sp_match);
		$sp_match = preg_replace('#\\\\\[/(.*?)\\\\\]$#', '\[/$1:$uid\]', $sp_match);
		$sp_replace = $bbcode_tpl;

		// @todo Make sure to change this too if something changed in message parsing
		$tokens = array(
			'URL'	 => array(
				'!(?:(' . str_replace(array('!', '\#'), array('\!', '#'), get_preg_expression('url')) . ')|(' . str_replace(array('!', '\#'), array('\!', '#'), get_preg_expression('www_url')) . '))!ie'	=>	"\$this->bbcode_specialchars(('\$1') ? '\$1' : 'http://\$2')"
			),
			'LOCAL_URL'	 => array(
				'!(' . str_replace(array('!', '\#'), array('\!', '#'), get_preg_expression('relative_url')) . ')!e'	=>	"\$this->bbcode_specialchars('$1')"
			),
			'RELATIVE_URL'	=> array(
				'!(' . str_replace(array('!', '\#'), array('\!', '#'), get_preg_expression('relative_url')) . ')!e'	=>	"\$this->bbcode_specialchars('$1')"
			),
			'EMAIL' => array(
				'!(' . get_preg_expression('email') . ')!ie'	=>	"\$this->bbcode_specialchars('$1')"
			),
			'TEXT' => array(
				'!(.*?)!es'	 =>	"str_replace(array(\"\\r\\n\", '\\\"', '\\'', '(', ')'), array(\"\\n\", '\"', '&#39;', '&#40;', '&#41;'), trim('\$1'))"
			),
			'SIMPLETEXT' => array(
				'!([a-zA-Z0-9-+.,_ ]+)!'	 =>	"$1"
			),
			'INTTEXT' => array(
				'!([\p{L}\p{N}\-+,_. ]+)!u'	 =>	"$1"
			),
			'IDENTIFIER' => array(
				'!([a-zA-Z0-9-_]+)!'	 =>	"$1"
			),
			'COLOR' => array(
				'!([a-z]+|#[0-9abcdef]+)!i'	=>	'$1'
			),
			'NUMBER' => array(
				'!([0-9]+)!'	=>	'$1'
			)
		);

		$sp_tokens = array(
			'URL'	 => '(?i)((?:' . str_replace(array('!', '\#'), array('\!', '#'), get_preg_expression('url')) . ')|(?:' . str_replace(array('!', '\#'), array('\!', '#'), get_preg_expression('www_url')) . '))(?-i)',
			'LOCAL_URL'	 => '(?i)(' . str_replace(array('!', '\#'), array('\!', '#'), get_preg_expression('relative_url')) . ')(?-i)',
			'RELATIVE_URL'	 => '(?i)(' . str_replace(array('!', '\#'), array('\!', '#'), get_preg_expression('relative_url')) . ')(?-i)',
			'EMAIL' => '(' . get_preg_expression('email') . ')',
			'TEXT' => '(.*?)',
			'SIMPLETEXT' => '([a-zA-Z0-9-+.,_ ]+)',
			'INTTEXT' => '([\p{L}\p{N}\-+,_. ]+)',
			'IDENTIFIER' => '([a-zA-Z0-9-_]+)',
			'COLOR' => '([a-zA-Z]+|#[0-9abcdefABCDEF]+)',
			'NUMBER' => '([0-9]+)',
		);

		$pad = 0;
		$modifiers = 'i';
		$modifiers .= ($utf8) ? 'u' : '';

		if (preg_match_all('/\{(' . implode('|', array_keys($tokens)) . ')[0-9]*\}/i', $bbcode_match, $m))
		{
			foreach ($m[0] as $n => $token)
			{
				$token_type = $m[1][$n];

				reset($tokens[strtoupper($token_type)]);
				list($match, $replace) = each($tokens[strtoupper($token_type)]);

				// Pad backreference numbers from tokens
				if (preg_match_all('/(?<!\\\\)\$([0-9]+)/', $replace, $repad))
				{
					$repad = $pad + sizeof(array_unique($repad[0]));
					$replace = preg_replace_callback('/(?<!\\\\)\$([0-9]+)/', function ($match) use ($pad) {
						return '${' . ($match[1] + $pad) . '}';
					}, $replace);
					$pad = $repad;
				}

				// Obtain pattern modifiers to use and alter the regex accordingly
				$regex = preg_replace('/!(.*)!([a-z]*)/', '$1', $match);
				$regex_modifiers = preg_replace('/!(.*)!([a-z]*)/', '$2', $match);

				for ($i = 0, $size = strlen($regex_modifiers); $i < $size; ++$i)
				{
					if (strpos($modifiers, $regex_modifiers[$i]) === false)
					{
						$modifiers .= $regex_modifiers[$i];

						if ($regex_modifiers[$i] == 'e')
						{
							$fp_replace = "'" . str_replace("'", "\\'", $fp_replace) . "'";
						}
					}

					if ($regex_modifiers[$i] == 'e')
					{
						$replace = "'.$replace.'";
					}
				}

				$fp_match = str_replace(preg_quote($token, '!'), $regex, $fp_match);
				$fp_replace = str_replace($token, $replace, $fp_replace);

				$sp_match = str_replace(preg_quote($token, '!'), $sp_tokens[$token_type], $sp_match);

				// Prepend the board url to local relative links
				$replace_prepend = ($token_type === 'LOCAL_URL') ? generate_board_url() . '/' : '';

				$sp_replace = str_replace($token, $replace_prepend . '${' . ($n + 1) . '}', $sp_replace);
			}

			$fp_match = '!' . $fp_match . '!' . $modifiers;
			$sp_match = '!' . $sp_match . '!s' . (($utf8) ? 'u' : '');

			if (strpos($fp_match, 'e') !== false)
			{
				$fp_replace = str_replace("'.'", '', $fp_replace);
				$fp_replace = str_replace(".''.", '.', $fp_replace);
			}
		}
		else
		{
			// No replacement is present, no need for a second-pass pattern replacement
			// A simple str_replace will suffice
			$fp_match = '!' . $fp_match . '!' . $modifiers;
			$sp_match = $fp_replace;
			$sp_replace = '';
		}

		// Lowercase tags
		$bbcode_tag = preg_replace('/.*?\[([a-z0-9_-]+=?).*/i', '$1', $bbcode_match);
		$bbcode_search = preg_replace('/.*?\[([a-z0-9_-]+)=?.*/i', '$1', $bbcode_match);

		if (!preg_match('/^[a-zA-Z0-9_-]+=?$/', $bbcode_tag))
		{
			global $user;
			trigger_error($user->lang['BBCODE_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
		}

		$fp_match = preg_replace_callback('#\[/?' . $bbcode_search . '#i', function ($match) {
			return strtolower($match[0]);
		}, $fp_match);
		$fp_replace = preg_replace_callback('#\[/?' . $bbcode_search . '#i', function ($match) {
			return strtolower($match[0]);
		}, $fp_replace);
		$sp_match = preg_replace_callback('#\[/?' . $bbcode_search . '#i', function ($match) {
			return strtolower($match[0]);
		}, $sp_match);
		$sp_replace = preg_replace_callback('#\[/?' . $bbcode_search . '#i', function ($match) {
			return strtolower($match[0]);
		}, $sp_replace);

		return array(
			'bbcode_tag'				=> $bbcode_tag,
			'first_pass_match'			=> $fp_match,
			'first_pass_replace'		=> $fp_replace,
			'second_pass_match'			=> $sp_match,
			'second_pass_replace'		=> $sp_replace
		);
	}
}
