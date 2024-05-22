<?php


namespace   App\Helpers;

class Validator
{

    /**
     * Validation data for the current form submission
     *
     * @var array
     */
    protected $_field_data		= array();

    /**
     * Valid URL
     *
     * @param	string	$str
     * @return	bool
     */
    public static function valid_url($str)
    {
        if (empty($str)) {
            return FALSE;
        } elseif (preg_match('/^(?:([^:]*)\:)?\/\/(.+)$/', $str, $matches)) {

            if (empty($matches[2])) {
                return FALSE;
            } elseif ( ! in_array(strtolower($matches[1]), array('http', 'https'), TRUE)) {
                return FALSE;
            }

            $str = $matches[2];
        }

        // PHP 7 accepts IPv6 addresses within square brackets as hostnames,
        // but it appears that the PR that came in with https://bugs.php.net/bug.php?id=68039
        // was never merged into a PHP 5 branch ... https://3v4l.org/8PsSN
        if (preg_match('/^\[([^\]]+)\]/', $str, $matches) && ! is_php('7') && filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== FALSE) {
            $str = 'ipv6.host'.substr($str, strlen($matches[1]) + 2);
        }

        return (filter_var('http://'.$str, FILTER_VALIDATE_URL) !== FALSE);
    }

    // --------------------------------------------------------------------

    /**
     * Valid Email
     *
     * @param	string
     * @return	bool
     */
    public static function valid_email($str)
    {
        if (function_exists('idn_to_ascii') && sscanf($str, '%[^@]@%s', $name, $domain) === 2)
        {
            $str = $name.'@'.idn_to_ascii($domain);
        }

        return (bool) filter_var($str, FILTER_VALIDATE_EMAIL);
    }

    // --------------------------------------------------------------------

    /**
     * Valid Emails
     *
     * @param	string
     * @return	bool
     */
    public static function valid_emails($str)
    {
        if (strpos($str, ',') === FALSE)
        {
            return self::valid_email(trim($str));
        }

        foreach (explode(',', $str) as $email)
        {
            if (trim($email) !== '' && self::valid_email(trim($email)) === FALSE)
            {
                return FALSE;
            }
        }

        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Alpha
     *
     * @param	string
     * @return	bool
     */
    public static function alpha($str)
    {
        return ctype_alpha($str);
    }

    // --------------------------------------------------------------------

    /**
     * Alpha-numeric
     *
     * @param	string
     * @return	bool
     */
    public static function alpha_numeric($str)
    {
        return ctype_alnum((string) $str);
    }

    // --------------------------------------------------------------------

    /**
     * Alpha-numeric w/ spaces
     *
     * @param	string
     * @return	bool
     */
    public static function alpha_numeric_spaces($str)
    {
        return (bool) preg_match('/^[A-Z0-9 ]+$/i', $str);
    }

    /**
     * Alpha w/ spaces
     *
     * @param	string
     * @return	bool
     */
    public static function alpha_spaces($str)
    {
        return (bool) preg_match('/^[A-Z ]+$/i', $str);
    }

    // --------------------------------------------------------------------

    /**
     * Alpha-numeric with underscores and dashes
     *
     * @param	string
     * @return	bool
     */
    public static function alpha_dash($str)
    {
        return (bool) preg_match('/^[a-z0-9_-]+$/i', $str);
    }

    // --------------------------------------------------------------------

    /**
     * Numeric
     *
     * @param	string
     * @return	bool
     */
    public static function numeric($str)
    {
        return (bool) preg_match('/^[\-+]?[0-9]*\.?[0-9]+$/', $str);

    }

    // --------------------------------------------------------------------

    /**
     * Integer
     *
     * @param	string
     * @return	bool
     */
    public function integer($str)
    {
        return (bool) preg_match('/^[\-+]?[0-9]+$/', $str);
    }

    // --------------------------------------------------------------------

    /**
     * Decimal number
     *
     * @param	string
     * @return	bool
     */
    public function decimal($str)
    {
        return (bool) preg_match('/^[\-+]?[0-9]+\.[0-9]+$/', $str);
    }

    // --------------------------------------------------------------------

    /**
     * Greater than
     *
     * @param	string
     * @param	int
     * @return	bool
     */
    public function greater_than($str, $min)
    {
        return is_numeric($str) ? ($str > $min) : FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Equal to or Greater than
     *
     * @param	string
     * @param	int
     * @return	bool
     */
    public static function greater_than_equal_to($str, $min)
    {
        return is_numeric($str) ? ($str >= $min) : FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Less than
     *
     * @param	string
     * @param	int
     * @return	bool
     */
    public function less_than($str, $max)
    {
        return is_numeric($str) ? ($str < $max) : FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Equal to or Less than
     *
     * @param	string
     * @param	int
     * @return	bool
     */
    public function less_than_equal_to($str, $max)
    {
        return is_numeric($str) ? ($str <= $max) : FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Value should be within an array of values
     *
     * @param	string
     * @param	string
     * @return	bool
     */
    public function in_list($value, $list)
    {
        return in_array($value, explode(',', $list), TRUE);
    }

    // --------------------------------------------------------------------

    /**
     * Is a Natural number  (0,1,2,3, etc.)
     *
     * @param	string
     * @return	bool
     */
    public static function is_natural($str)
    {
        return ctype_digit((string) $str);
    }

    // --------------------------------------------------------------------

    /**
     * Is a Natural number, but not a zero  (1,2,3, etc.)
     *
     * @param	string
     * @return	bool
     */
    public function is_natural_no_zero($str)
    {
        return ($str != 0 && ctype_digit((string) $str));
    }

    // --------------------------------------------------------------------

    /**
     * Valid Base64
     *
     * Tests a string for characters outside of the Base64 alphabet
     * as defined by RFC 2045 http://www.faqs.org/rfcs/rfc2045
     *
     * @param	string
     * @return	bool
     */
    public function valid_base64($str)
    {
        return (base64_encode(base64_decode($str)) === $str);
    }

    // --------------------------------------------------------------------

    /**
     * Prep URL
     *
     * @param	string
     * @return	string
     */
    public function prep_url($str = '')
    {
        if ($str === 'http://' OR $str === '') {
            return '';
        }

        if (strpos($str, 'http://') !== 0 && strpos($str, 'https://') !== 0) {
            return 'http://'.$str;
        }

        return $str;
    }

    // --------------------------------------------------------------------

    /**
     * Performs a Regular Expression match test.
     *
     * @param	string
     * @param	string	regex
     * @return	bool
     */
    public function regex_match($str, $regex)
    {
        return (bool) preg_match($regex, $str);
    }

    // --------------------------------------------------------------------

    /**
     * Match one field to another
     *
     * @param	string	$str	string to compare against
     * @param	string	$field
     * @return	bool
     */
    public function matches($str, $field)
    {
        return isset($this->_field_data[$field], $this->_field_data[$field]['postdata'])
            ? ($str === $this->_field_data[$field]['postdata'])
            : FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Differs from another field
     *
     * @param	string
     * @param	string	field
     * @return	bool
     */
    public function differs($str, $field)
    {
        return ! (isset($this->_field_data[$field]) && $this->_field_data[$field]['postdata'] === $str);
    }

    // --------------------------------------------------------------------

    /**
     * Is Unique
     *
     * Check if the input value doesn't already exist
     * in the specified database field.
     *
     * @param	string	$str
     * @param	string	$field
     * @return	bool
     */
    public function is_unique($str, $field)
    {
        sscanf($field, '%[^.].%[^.]', $table, $field);
        return isset($this->CI->db)
            ? ($this->CI->db->limit(1)->get_where($table, array($field => $str))->num_rows() === 0)
            : FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Minimum Length
     *
     * @param	string
     * @param	string
     * @return	bool
     */
    public static function min_length($str, $val)
    {
        if ( ! is_numeric($val)) {
            return FALSE;
        }

        return ($val <= mb_strlen($str));
    }

    // --------------------------------------------------------------------

    /**
     * Max Length
     *
     * @param	string
     * @param	string
     * @return	bool
     */
    public function max_length($str, $val)
    {
        if ( ! is_numeric($val)) {
            return FALSE;
        }

        return ($val >= mb_strlen($str));
    }

    // --------------------------------------------------------------------

    /**
     * Exact Length
     *
     * @param	string
     * @param	string
     * @return	bool
     */
    public static function exact_length($str, $val)
    {
        if ( ! is_numeric($val)) {
            return FALSE;
        }

        return (mb_strlen($str) === (int) $val);
    }

    public static function fuzzy_number_match( $expected, $actual) {

        if(strpos($expected,".") !== false){
            return false;
        }

        return strcmp(abs($expected), $actual) == 0 ? true : false;
    }
}

