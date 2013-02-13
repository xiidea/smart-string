<?php

class SmartString
{
    private $string = "";

    private $encoding;
    private $font_file = false;
    private $type = 'Words';
    private $fn_strlen;
    private $fn_substr;

    private $config = array(
        'font_dir' => 'fonts',
        'font_name' => 'arial',
        'font_size' => '7',
        'character_limit' => '100',
        'word_limit' => '10',
        'width_limit' => '100',
        'end' => '&hellip;',
    );

    public function __construct($initial_value = "", $config = array())
    {
        if (is_array($initial_value)) {
            $config = $initial_value;
        } else {
            $this->string = $this->clean($initial_value);
        }

        $this->init();

        return $this->config($config);
    }

    public function val($new = null)
    {
        if ($new === null) {
            return $this->string;
        }

        $this->string = $this->clean($new);
        return $this->init();
    }

    public function type($new = null)
    {
        if ($new === null) {
            return $this->type;
        }

        $this->type = ucfirst($new);

        return $this;
    }

    public function __toString()
    {
        return $this->string;
    }

    private function init()
    {
        $this->encoding = mb_detect_encoding($this->string);
        $this->fn_strlen = 'mb_strlen';
        $this->fn_substr = 'mb_strlen';
        if (strtoupper($this->encoding) == 'ASCII') {
            $this->fn_strlen = 'strlen';
            $this->fn_substr = 'substr';
        }
        return $this;
    }

    public function clean($text = null)
    {
        if ($text === null) {
            $this->string = $this->clean($this->string);
            return $this;
        }
        $text = str_replace(array("\r\n", "\r", "\n", "\t", '&nbsp;'), ' ', strip_tags($text));
        return html_entity_decode(trim(preg_replace("/\s+/", ' ', $text)));
    }

    public function limitWords($limit = null)
    {
        if ($this->string == '') {
            return "";
        }

        if ($limit == null) {
            $limit = $this->config['word_limit'];
        }

        $strlen = $this->fn_strlen;

        $end_char = $this->config['end'];

        preg_match('/^\s*+(?:\S++\s*+){1,' . (int)$limit . '}/', $this->string, $matches);

        if ($strlen($this->string) == $strlen($matches[0])) {
            $end_char = '';
        }

        return rtrim($matches[0]) . $end_char;
    }

    public function limitCharacters($limit = null)
    {
        if ($limit == null) {
            $limit = $this->config['character_limit'];
        }

        $str = $this->string;

        $strlen = $this->fn_strlen;
        $substr = $this->fn_substr;

        if ($strlen($str) <= $limit) {
            return $str;
        }

        $end_char = $this->config['end'];

        $out = "";
        foreach (explode(' ', trim($str)) as $val) {
            if ($strlen("{$out} {$val}") > $limit) //We are going to exceeded limit!
            {
                $out = trim($out);
                //what if a crazy user put a long word to test you out!! do not disappoint him
                if ($out == "" && $str != "") {
                    return $substr($str, 0, $limit) . $end_char;
                }
                return ($strlen($out) == $strlen($str)) ? $out : $out . $end_char;
            }

            $out .= $val . ' ';
        }
        return ($strlen($out) == $strlen($str)) ? $out : $out . $end_char;

    }

    public function getWidth($str = null)
    {
        if ($str == null) {
            $str = $this->string;
        }

        if (!$this->font_file) {
            throw new Exception("No font file configured!");
        }

        $bounding_box = imagettfbbox($this->config['font_size'], 0, $this->font_file, $str);

        return $bounding_box[2];
    }

    public function limitWidth($width = null)
    {
        if ($width == null) {
            $width = $this->config['width_limit'];
        }

        $append_string = $this->clean($this->config['end']);
        $append_string_width = $this->getWidth($append_string);

        $strlen = $this->fn_strlen;
        $substr = $this->fn_substr;

        $text = $this->string;
        $str_len = $strlen($text);

        for ($i = 0; $i <= $str_len; $i++) {
            $trimmed_text = $substr($text, 0, $i);
            $trimmed_text_width = $this->getWidth($trimmed_text);
            if ($trimmed_text_width + $append_string_width > $width && $i > 0) {
                $str_to_return = $substr($trimmed_text, 0, $strlen($trimmed_text) - 1);
                if ($trimmed_text_width != $str_len) {
                    $str_to_return .= $append_string;
                }
                return $str_to_return;
            }
        }
        return $text;
    }

    public function limit($limit = null, $type = null)
    {
        if ($type !== null) {
            $this->type = ucfirst($type);
        }

        switch ($this->type) {
            case 'Words':
            case 'Characters':
            case 'Width':
                $function = "limit{$this->type}";
                break;
            default:
                throw new Exception("Invalid Operation");
        }

        return $this->$function($limit);
    }

    private function initFontFile()
    {
        $this->font_file = false;

        $font_base_dir = realpath($this->config['font_dir']);
        $font_file = $font_base_dir . DIRECTORY_SEPARATOR . $this->config['font_name'] . ".ttf";

        if (file_exists($font_file)) {
            $this->font_file = $font_file;
        }
    }

    public function config($config = array())
    {
        $needFontFileInit = (!$this->font_file || isset($config['font_dir']) || isset($config['font_name']));

        $this->config = array_merge($this->config, $config);

        if ($needFontFileInit) {
            $this->initFontFile();
        }

        return $this;
    }
}
