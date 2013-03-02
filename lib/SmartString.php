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
        'line_limit' => '10',
        'line_width' => '100',
        'word_limit' => '10',
        'width_limit' => '100',
        'end' => '&hellip;',
        'trim_word' => false,
        'new_line' => '<br>',
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

    private function reCalculateLimit($str = null, $limit = null){

        if ($str === null) {
            $str = $this->string;
        }

        if ($str == '') {
            return $limit;
        }

        if ($limit == null) {
            $limit = $this->config['character_limit'];
        }

        $strlen = $this->fn_strlen;
        $substr = $this->fn_substr;

        $str = $substr($str, 0 , $limit);

        $wide = $strlen(preg_replace('/[^A-Z0-9_@#%$&]/', '', $str));

        return round($limit - $wide * 0.2);
    }

    public function limitWords($limit = null, $str = null)
    {
        if ($str === null) {
            $str = $this->string;
        }

        if ($str == '') {
            return "";
        }

        if ($limit == null) {
            $limit = $this->config['word_limit'];
        }

        $strlen = $this->fn_strlen;

        $end_char = $this->config['end'];

        preg_match('/^\s*+(?:\S++\s*+){1,' . (int)$limit . '}/', $str, $matches);

        if ($strlen($str) == $strlen($matches[0])) {
            $end_char = '';
        }

        return rtrim($matches[0]) . $end_char;
    }

    public function limitCharacters($limit = null, $str = null, $ellipsis = true)
    {
        if ($limit === null) {
            $limit = $this->config['character_limit'];
        }

        if ($str === null) {
            $str = $this->string;
        }

        $limit = $this->reCalculateLimit($str, $limit);

        $strlen = $this->fn_strlen;
        $substr = $this->fn_substr;

        if ($strlen($str) <= $limit) {
            return $str;
        }

        $end_char = $ellipsis ? $this->config['end'] : "";
        $dummyEnd = html_entity_decode($end_char);

        $out = "";
        foreach (explode(' ', trim($str)) as $val) {
            if ($strlen("{$out} {$val}{$dummyEnd}") > $limit) //We are going to exceeded limit!
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
        return $out . $end_char;

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

    public function limitWidth($width = null, $text = null)
    {
        if($this->config['trim_word']){
            return $this->limitWidthBreakWord($width, $text);
        }else{
            return $this->limitWidthFullWord($width, $text);
        }
    }

    public function limitLine($lines = null, $text = null, $lineWidth = null)
    {
        if ($lines === null) {
            $lines = $this->config['line_limit'];
        }

        if ($text === null) {
            $text = $this->string;
        }

        if ($lineWidth === null) {
            $lineWidth = $this->config['line_width'];
        }

        $strlen = $this->fn_strlen;
        $substr = $this->fn_substr;

        $result = '';
        $spaces_added = 0;
        $next_start = 0;

        // Divide up the string into lines
        for ($i=0;$i<$lines;$i++) {
            if (! $next_start) {
                $start = $i * $lineWidth;
            } else {
                $start = $next_start;
            }

            $line = $substr($text, $start, $lineWidth + 1);

            // Truncate the line by the appropriate length
            $old_line = $line;

            $line = $this->limitCharacters($lineWidth, $line, ($lines == $i+1));

            $limitTemp = $strlen($line);

            $next_start = $start + $limitTemp + 1;

            // If there are no line breaks in this line at all
            if ($strlen($line) < $strlen($text) and ! strstr($line, ' ')) {
                // Add a space to it, keep track of how many spaces are added
                $line .= ' ';
                $spaces_added ++;
            }
            $result .= $line;
            if($lines !== $i+1){
                $result .= $this->config['new_line'];
            }
        }

        return $result;
    }

    private function limitWidthBreakWord($width = null, $text = null)
    {
        if ($width === null) {
            $width = $this->config['width_limit'];
        }

        if ($text === null) {
            $text = $this->string;
        }

        $single_character_width = $this->getWidth('W');

        $startIndex = floor($width / $single_character_width);

        $append_string = $this->clean($this->config['end']);
        $append_string_width = $this->getWidth($append_string);

        $strlen = $this->fn_strlen;
        $substr = $this->fn_substr;

        $str_len = $strlen($text);

        for ($i = $startIndex; $i <= $str_len; $i++) {
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

    private function limitWidthFullWord($width = null, $text = null)
    {
        if ($width == null) {
            $width = $this->config['width_limit'];
        }

        if ($text === null) {
            $text = $this->string;
        }

        $append_string = $this->clean($this->config['end']);
        $append_string_width = $this->getWidth(html_entity_decode($append_string));

        $fullWidth = $this->getWidth();

        if($fullWidth < $width){
            return $text;
        }

        $out = "";
        foreach (explode(' ', $text) as $val) {
            $newWidth = $this->getWidth("{$out} {$val}") + $append_string_width;
            if ($newWidth > $width) //We are going to exceeded limit!
            {
                $out = trim($out);
                //what if a crazy user put a long word to test you out!! do not disappoint him
                if ($out == "" && $text != "") {
                    return $this->limitWidth();
                }

                return ($text == $out) ? $out : $out . $append_string;
            }

            $out .= $val . ' ';
        }

        return ($text == $out) ? $out : $out . $append_string;
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
            case 'Line':
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

         $font_base_dir = $this->config['font_dir'] == 'fonts' ?
                                                    dirname(__FILE__). DIRECTORY_SEPARATOR ."fonts"
                                                    : realpath($this->config['font_dir']);

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
