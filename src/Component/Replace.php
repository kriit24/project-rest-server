<?php

namespace Project\RestServer\Component;

class Replace
{
    static function dotValue($name, $array)
    {

        $keys = explode('.', $name);
        $key = array_shift($keys);
        $ret = [];

        if (array_is_list($array)) {

            foreach ($array as $k => $v) {

                $ret[$k] = self::dotValue($name, $v);
            }
        }
        else {

            if (isset($array[$key]) && !empty($array[$key]))
                $ret[$key] = is_array($array[$key]) ? self::dotValue(implode('.', $keys), $array[$key]) : $array[$key];
            else
                $ret[$key] = count($keys) >= 1 ? [] : null;
        }

        return $ret;
    }

    static function replace($content, $array, $replaceIfEmpty = true)
    {

        //for texteditor replace
        $content = preg_replace('/<tr data-replace-each="\{([a-zA-Z0-9\_\-_]+)\}"(.*?)<\/tr>/s', '{\1}<tr\2</tr>{/\1}', $content);

        //php tag: single replacement
        $content = preg_replace_callback('/\{php:(.*?)\}/s', function ($matches) use ($array, $replaceIfEmpty) {

            $fn = rtrim($matches[1], ';');
            return self::evalReplace('echo ' . $fn . ";", $array);
        }, $content);

        //multi replacement
        $content = preg_replace_callback('/\{([a-zA-Z0-9\_\:_]+)\}(.*?)\{\/\\1\}/s', function ($matches) use ($array, $replaceIfEmpty) {

            if (strpos($matches[1], ':') == 0)
                $matches[1] = str_replace(':', '', $matches[1]);

            $key = $matches[1];
            $html = $matches[2];
            $list = '';
            if (array_key_exists($key, $array) && is_array($array[$key])) {

                foreach ($array[$key] as $row) {
                    $list .= \Project\RestServer\Component\Replace::replace($html, $row, $replaceIfEmpty);
                }
            }
            return $list;
        }, $content);

        //single replacement
        $content = preg_replace_callback('/\{([a-zA-Z0-9\_\:\-_]+)\}/', function ($matches) use ($array, $replaceIfEmpty) {

            if (strpos($matches[1], ':') == 0)
                $matches[1] = str_replace(':', '', $matches[1]);
            if ($replaceIfEmpty)
                return array_key_exists($matches[1], $array) ? $array[$matches[1]] : null;
            else
                return isset($array[$matches[1]]) ? $array[$matches[1]] : $matches[0];
        }, $content);

        //dot replacement
        $content = preg_replace_callback('/\{([a-zA-Z0-9\_\:\-\._]+)\}/', function ($matches) use ($array, $replaceIfEmpty) {

            $keys = explode('.', $matches[1]);
            $tmp = $array;

            foreach ($keys as $key) {
                $tmp = &$tmp[$key];
            }

            if ($replaceIfEmpty)
                return $tmp;
            else
                return $tmp ? $tmp : $matches[0];
        }, $content);

        //php code tag: replacement
        return preg_replace_callback('/\<\?PHP(.*?)\?\>/s', function ($matches) use ($array) {

            return self::evalReplace(self::replace($matches[1], $array), $array);
        }, $content);
    }

    static function arrayReplace($array, $data, $replaceIfEmpty = true)
    {

        foreach ($array as $k => $v) {

            if (is_array($v)) {

                $array[$k] = self::arrayReplace($v, $data, $replaceIfEmpty);
            }
            else {

                $array[$k] = self::replace($v, $data, $replaceIfEmpty);
            }
        }

        return $array;
    }

    static function editorReplace($content)
    {

        $content = preg_replace('/\{\[([a-zA-Z0-9\_\-\:\/_]+)\]\}/s', '{\\1}/', stripslashes(str_replace(["\\r", "\\n"], ["\r", "\n"], $content)));
        $content = preg_replace('/\<\!\-\-\{([a-zA-Z0-9\_\-\:\/_]+)\}\-\-\>/s', '{\\1}', $content);
        return $content;
    }

    private static function evalReplace($content, $array)
    {

        ob_start();
        eval($content);
        $eval = ob_get_contents();
        ob_end_clean();
        return $eval;
    }

    static function replacenl($content)
    {

        return str_replace(["\r\n", "\r", "\n", "\t"], "", $content);
    }

    static function correctnl($content)
    {

        return stripslashes(str_replace(["\\r", "\\n"], ["\r", "\n"], $content));
    }

    public static function match($string)
    {
        return str_replace('/', '\/', $string);
    }
}
