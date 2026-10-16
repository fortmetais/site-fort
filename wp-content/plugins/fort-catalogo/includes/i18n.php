<?php
/** Deterministic front-end translations; no remote services or content changes. */
defined('ABSPATH') || exit;

function fort_i18n_languages() { return array('pt-br', 'en', 'es'); }

function fort_i18n_valid_language($value) {
    return is_string($value) && in_array($value, fort_i18n_languages(), true) ? $value : 'pt-br';
}

function fort_language() {
    if (array_key_exists('lang', $_GET)) return fort_i18n_valid_language($_GET['lang']);
    if (isset($_POST['action']) && $_POST['action'] === 'fort_orcamento' && array_key_exists('lang', $_POST)) return fort_i18n_valid_language($_POST['lang']);
    return isset($_COOKIE['fort_lang']) ? fort_i18n_valid_language($_COOKIE['fort_lang']) : 'pt-br';
}

function fort_i18n_frontend_request() {
    if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST) || (function_exists('wp_doing_ajax') && wp_doing_ajax()) || (defined('DOING_CRON') && DOING_CRON)) return false;
    $uri = isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
    $path = (string) wp_parse_url($uri, PHP_URL_PATH);
    return !preg_match('~/(?:wp-admin|wp-json|wp-includes|wp-content|wp-login\.php|xmlrpc\.php)(?:/|$|\?)~i', $path) && !isset($_GET['rest_route']);
}

add_action('init', function () {
    if (!fort_i18n_frontend_request() || !array_key_exists('lang', $_GET)) return;
    $language = fort_language();
    $_COOKIE['fort_lang'] = $language;
    if (!headers_sent()) setcookie('fort_lang', $language, array('expires' => time() + YEAR_IN_SECONDS, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax'));
}, 1);

/** The public catalog retains source keys for the administration screen. */
function fort_translation_catalog($language) {
    static $catalogs = array();
    $language = fort_i18n_valid_language($language);
    if ($language === 'pt-br') return array();
    if (isset($catalogs[$language])) return $catalogs[$language];
    $path = __DIR__ . '/translations/' . $language . '.php';
    $base = is_file($path) ? require $path : array();
    if (!is_array($base)) $base = array();
    $options = get_option('fort_i18n_overrides', array());
    $overrides = is_array($options) && isset($options[$language]) && is_array($options[$language]) ? $options[$language] : array();
    $catalog = array();
    foreach (array_replace($base, $overrides) as $source => $translation) {
        if (is_string($source) && is_string($translation) && trim($source) !== '' && trim($translation) !== '') $catalog[$source] = $translation;
    }
    return $catalogs[$language] = $catalog;
}

function fort_i18n_normalize($text) {
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim((string) preg_replace('/[\s\x{00a0}]+/u', ' ', $text));
}

function fort_i18n_dictionary($language) {
    static $maps = array();
    if (isset($maps[$language])) return $maps[$language];
    $map = array();
    foreach (fort_translation_catalog($language) as $source => $translation) $map[fort_i18n_normalize($source)] = $translation;
    return $maps[$language] = $map;
}

/** Exact lookup with entity/space normalization and original edge whitespace. */
function fort_t($text, $language = null) {
    if (!is_string($text)) return $text;
    $language = fort_i18n_valid_language($language === null ? fort_language() : $language);
    if ($language === 'pt-br' || trim($text) === '') return $text;
    $map = fort_i18n_dictionary($language);
    $key = fort_i18n_normalize($text);
    if (!array_key_exists($key, $map)) return $text;
    $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    preg_match('/^[\s\x{00a0}]*/u', $decoded, $leading);
    preg_match('/[\s\x{00a0}]*$/u', $decoded, $trailing);
    return $leading[0] . $map[$key] . $trailing[0];
}

/** Longest whole phrases compose dynamic labels without translating URL/codes. */
function fort_i18n_translate_phrase($text, $language = null) {
    if (!is_string($text)) return $text;
    $language = fort_i18n_valid_language($language === null ? fort_language() : $language);
    if ($language === 'pt-br' || trim($text) === '') return $text;
    $exact = fort_t($text, $language);
    if ($exact !== $text) return $exact;
    static $patterns = array();
    if (!isset($patterns[$language])) {
        $map = fort_i18n_dictionary($language);
        foreach ($map as $source => $translation) if (strlen($source) < 3 || $source === $translation) unset($map[$source]);
        uksort($map, function ($a, $b) { return strlen($b) <=> strlen($a); });
        $parts = array();
        foreach ($map as $source => $translation) $parts[] = preg_quote($source, '~');
        $patterns[$language] = array($parts ? '~(?<![\pL\pN_])(?:' . implode('|', $parts) . ')(?![\pL\pN_])~u' : '', $map);
    }
    list($pattern, $map) = $patterns[$language];
    if ($pattern === '') return $text;
    $pieces = preg_split('~(https?://[^\s<>]+|[\w.+-]+@[\w.-]+\.[a-z]{2,}|\bF-\d+\b|\+?\d[\d ()-]{6,}\d)~iu', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (!is_array($pieces)) return $text;
    foreach ($pieces as $index => $piece) {
        if ($index % 2 === 1) continue;
        $translated = preg_replace_callback($pattern, function ($match) use ($map) { return $map[$match[0]]; }, $piece);
        if (is_string($translated)) $pieces[$index] = $translated;
    }
    return implode('', $pieces);
}

/** Internal means the same public origin, including its configured local port. */
function fort_i18n_is_internal_url($url) {
    if (!is_string($url) || $url === '' || preg_match('/[\x00-\x20]/', $url) || $url[0] === '#') return false;
    $parts = wp_parse_url($url);
    $home = wp_parse_url(home_url('/'));
    if (!is_array($parts) || !is_array($home)) return false;
    if (isset($parts['scheme']) && !in_array(strtolower($parts['scheme']), array('http', 'https'), true)) return false;
    if (isset($parts['user']) || isset($parts['pass'])) return false;
    if (isset($parts['host'])) {
        if (strtolower($parts['host']) !== strtolower($home['host'])) return false;
        if (isset($parts['scheme']) && strtolower($parts['scheme']) !== strtolower($home['scheme'])) return false;
        $port = isset($parts['port']) ? (int) $parts['port'] : ((isset($parts['scheme']) ? strtolower($parts['scheme']) : strtolower($home['scheme'])) === 'https' ? 443 : 80);
        $home_port = isset($home['port']) ? (int) $home['port'] : (strtolower($home['scheme']) === 'https' ? 443 : 80);
        if ($port !== $home_port) return false;
    }
    return true;
}

function fort_i18n_url($url, $language = null) {
    if (!is_string($url) || !fort_i18n_is_internal_url($url)) return is_string($url) ? $url : '';
    $parts = wp_parse_url($url);
    $path = isset($parts['path']) ? rawurldecode($parts['path']) : '';
    if (preg_match('~(?:^|/)(?:wp-admin|wp-json|wp-content|wp-includes)(?:/|$)|(?:^|/)(?:wp-login|xmlrpc)\.php(?:$|/)|\.(?:jpe?g|png|gif|webp|avif|svg|ico|css|js|mjs|json|xml|txt|pdf|woff2?|ttf|otf|mp4|webm|mp3|zip)$~i', $path)) return $url;
    if (isset($parts['query']) && preg_match('/(?:^|&)rest_route=/', $parts['query'])) return $url;
    $language = fort_i18n_valid_language($language === null ? fort_language() : $language);
    return $language === 'pt-br' ? remove_query_arg('lang', $url) : add_query_arg('lang', $language, $url);
}

/** Language-choice URLs are explicit, including Portuguese, to update the cookie. */
function fort_language_url($language) {
    $language = fort_i18n_valid_language($language);
    $uri = isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
    if (strlen($uri) > 8192 || !str_starts_with($uri, '/') || str_starts_with($uri, '//') || preg_match('/[\x00-\x20]/', $uri)) $uri = '/';
    $home = wp_parse_url(home_url('/'));
    $origin = $home['scheme'] . '://' . $home['host'] . (isset($home['port']) ? ':' . $home['port'] : '');
    return add_query_arg('lang', $language, $origin . $uri);
}

function fort_i18n_whatsapp_url($url, $language = null) {
    if (!is_string($url)) return '';
    $parts = wp_parse_url($url);
    if (!is_array($parts) || !isset($parts['host']) || !in_array(strtolower($parts['host']), array('wa.me', 'api.whatsapp.com', 'web.whatsapp.com'), true)) return $url;
    $query = array();
    if (isset($parts['query'])) parse_str($parts['query'], $query);
    if (!isset($query['text']) || !is_string($query['text'])) return $url;
    $message = fort_i18n_translate_phrase($query['text'], $language);
    $message = preg_replace_callback('~https?://[^\s<>]+~u', function ($match) use ($language) {
        $link = rtrim($match[0], '.,;!?)');
        return fort_i18n_url($link, $language) . substr($match[0], strlen($link));
    }, $message);
    return is_string($message) ? add_query_arg('text', rawurlencode($message), $url) : $url;
}

function fort_i18n_attribute($tag, $name, $value, $language, $type = '') {
    if (in_array($name, array('title', 'alt', 'placeholder', 'aria-label', 'data-label', 'data-model-title'), true)) return fort_i18n_translate_phrase($value, $language);
    if ($tag === 'input' && $name === 'value' && in_array(strtolower($type), array('submit', 'button', 'reset'), true)) return fort_i18n_translate_phrase($value, $language);
    if (($name === 'href' && in_array($tag, array('a', 'area'), true)) || ($name === 'action' && $tag === 'form') || $name === 'data-quote') return fort_i18n_url(fort_i18n_whatsapp_url($value, $language), $language);
    if ($name === 'data-whatsapp') return fort_i18n_whatsapp_url($value, $language);
    return $value;
}

function fort_i18n_walk_dom($node, $language) {
    if ($node instanceof DOMText) {
        $node->nodeValue = fort_i18n_translate_phrase($node->nodeValue, $language);
        return;
    }
    if ($node instanceof DOMElement) {
        $tag = strtolower($node->tagName);
        if (strtolower($node->getAttribute('translate')) === 'no' || $node->hasAttribute('data-no-translate') || in_array($tag, array('script', 'style', 'svg', 'pre', 'code'), true)) return;
        $type = $node->getAttribute('type');
        foreach (array('title', 'alt', 'placeholder', 'aria-label', 'data-label', 'data-model-title', 'value', 'href', 'action', 'data-quote', 'data-whatsapp') as $name) {
            if (!$node->hasAttribute($name) || ($name === 'href' && $node->hasAttribute('data-fort-language'))) continue;
            $node->setAttribute($name, fort_i18n_attribute($tag, $name, $node->getAttribute($name), $language, $type));
        }
        if ($tag === 'meta' && in_array(strtolower($node->getAttribute('name') ?: $node->getAttribute('property')), array('description', 'og:title', 'og:description', 'twitter:title', 'twitter:description'), true) && $node->hasAttribute('content')) $node->setAttribute('content', fort_i18n_translate_phrase($node->getAttribute('content'), $language));
        if ($tag === 'html') $node->setAttribute('lang', $language === 'pt-br' ? 'pt-BR' : $language);
        if ($tag === 'form') {
            $action = $node->getAttribute('action');
            $eligible = $action === '' || fort_i18n_is_internal_url($action);
            $has_language = false;
            foreach ($node->getElementsByTagName('*') as $field) if ($field->getAttribute('name') === 'lang') { $has_language = true; break; }
            if ($eligible && !$has_language) {
                $field = $node->ownerDocument->createElement('input');
                $field->setAttribute('type', 'hidden'); $field->setAttribute('name', 'lang'); $field->setAttribute('value', $language);
                $node->appendChild($field);
            }
        }
        if ($tag === 'textarea') return;
    }
    for ($child = $node->firstChild; $child; $child = $next) { $next = $child->nextSibling; fort_i18n_walk_dom($child, $language); }
}

/** Fallback for PHP builds without DOM; parse tags independently of text content. */
function fort_i18n_translate_html_fallback($html, $language) {
    $tokens = preg_split('~(<!--[\s\S]*?-->|<![^>]*>|<\?[\s\S]*?\?>|</?[a-zA-Z][^>"\']*(?:(?:"[^"]*"|\'[^\']*\')[^>"\']*)*>)~s', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (!is_array($tokens)) return $html;
    $stack = array();
    $void = array('area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr');
    foreach ($tokens as $index => $token) {
        $parent = $stack ? $stack[count($stack) - 1] : array('tag' => '', 'skip' => false);
        if (!str_starts_with($token, '<')) {
            if (!$parent['skip']) $tokens[$index] = esc_html(fort_i18n_translate_phrase(html_entity_decode($token, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $language));
            continue;
        }
        if (preg_match('~^</([a-zA-Z][\w:-]*)\b~', $token, $match)) {
            $tag = strtolower($match[1]);
            if ($parent['skip'] && in_array($parent['tag'], array('script', 'style', 'textarea'), true) && $parent['tag'] !== $tag) continue;
            for ($position = count($stack) - 1; $position >= 0; $position--) if ($stack[$position]['tag'] === $tag) { $stack = array_slice($stack, 0, $position); break; }
            continue;
        }
        if (!preg_match('~^<([a-zA-Z][\w:-]*)\b~', $token, $match) || ($parent['skip'] && in_array($parent['tag'], array('script', 'style', 'textarea'), true))) continue;
        $tag = strtolower($match[1]);
        $no_translate = preg_match('~\btranslate\s*=\s*(?:"no"|\'no\'|no\b)|\bdata-no-translate(?:\s|=|>)~i', $token);
        $skip = $parent['skip'] || $no_translate || in_array($tag, array('script', 'style', 'svg', 'pre', 'code'), true);
        if (!$skip && class_exists('WP_HTML_Tag_Processor')) {
            $processor = new WP_HTML_Tag_Processor($token);
            if ($processor->next_tag()) {
                foreach (array('title', 'alt', 'placeholder', 'aria-label', 'data-label', 'data-model-title', 'value', 'href', 'action', 'data-quote', 'data-whatsapp') as $name) {
                    $value = $processor->get_attribute($name);
                    if (!is_string($value) || ($name === 'href' && $processor->get_attribute('data-fort-language') !== null)) continue;
                    $processor->set_attribute($name, fort_i18n_attribute($tag, $name, $value, $language, (string) $processor->get_attribute('type')));
                }
                if ($tag === 'html') $processor->set_attribute('lang', $language === 'pt-br' ? 'pt-BR' : $language);
                $tokens[$index] = $processor->get_updated_html();
            }
        }
        if (!in_array($tag, $void, true) && !preg_match('~/\s*>$~', $token)) $stack[] = array('tag' => $tag, 'skip' => $skip || $tag === 'textarea');
    }
    $result = implode('', $tokens);
    return preg_replace_callback('~(<form\b[^>]*>)([\s\S]*?)(</form>)~i', function ($match) use ($language) {
        if (preg_match('~\btranslate\s*=\s*(?:"no"|\'no\'|no\b)|\bname\s*=\s*(?:"lang"|\'lang\'|lang\b)|\bdata-no-translate~i', $match[0])) return $match[0];
        if (preg_match('~\baction\s*=\s*(["\'])(.*?)\1~i', $match[1], $action) && $action[2] !== '' && !fort_i18n_is_internal_url(html_entity_decode($action[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'))) return $match[0];
        return $match[1] . $match[2] . '<input type="hidden" name="lang" value="' . esc_attr($language) . '">' . $match[3];
    }, $result) ?: $result;
}

function fort_i18n_translate_html($html) {
    $language = fort_language();
    if ($language === 'pt-br' || !is_string($html) || strlen($html) > 6 * 1024 * 1024 || stripos($html, '<html') === false) return $html;
    // libxml may serialize non-ASCII script contents as HTML entities. Preserve
    // executable/code/vector blocks byte-for-byte, including inline JSON and SVG.
    $blocks = array();
    $prefix = 'FORT_I18N_RAW_' . substr(hash('sha256', $html), 0, 16) . '_';
    $masked = preg_replace_callback('~<(script|style|svg|pre|code)\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>[\s\S]*?</\1\s*>~i', function ($match) use (&$blocks, $prefix) {
        $marker = '<!--' . $prefix . count($blocks) . '-->';
        $blocks[$marker] = $match[0];
        return $marker;
    }, $html);
    if (!is_string($masked)) return $html;
    if (!class_exists('DOMDocument')) return strtr(fort_i18n_translate_html_fallback($masked, $language), $blocks);
    $previous = libxml_use_internal_errors(true);
    $document = new DOMDocument('1.0', 'UTF-8');
    $loaded = $document->loadHTML('<?xml encoding="UTF-8">' . $masked, LIBXML_NONET);
    libxml_clear_errors(); libxml_use_internal_errors($previous);
    if (!$loaded) return $html;
    $document->encoding = 'UTF-8';
    foreach (iterator_to_array($document->childNodes) as $child) if ($child->nodeType === XML_PI_NODE) $document->removeChild($child);
    fort_i18n_walk_dom($document, $language);
    $result = $document->saveHTML();
    return is_string($result) ? strtr($result, $blocks) : $html;
}

add_action('template_redirect', function () {
    if (fort_i18n_frontend_request() && fort_language() !== 'pt-br' && !is_feed() && !is_trackback()) ob_start('fort_i18n_translate_html');
}, 0);

add_filter('language_attributes', function ($attributes) {
    if (!fort_i18n_frontend_request()) return $attributes;
    $language = fort_language() === 'pt-br' ? 'pt-BR' : fort_language();
    return preg_match('/\blang=["\']/', $attributes) ? preg_replace('/\blang=(["\'])[^"\']*\1/', 'lang="' . $language . '"', $attributes) : $attributes . ' lang="' . $language . '"';
});

add_filter('wp_redirect', function ($location) {
    if (!isset($_POST['action']) || $_POST['action'] !== 'fort_orcamento' || !is_string($location)) return $location;
    $contact = wp_parse_url(home_url('/fale-com-a-gente/'), PHP_URL_PATH);
    return fort_i18n_is_internal_url($location) && wp_parse_url($location, PHP_URL_PATH) === $contact ? fort_i18n_url($location) : $location;
}, 99);

add_action('wp_head', function () {
    if (!fort_i18n_frontend_request()) return;
    foreach (array('pt-br' => 'pt-BR', 'en' => 'en', 'es' => 'es') as $key => $hreflang) echo '<link rel="alternate" hreflang="' . esc_attr($hreflang) . '" href="' . esc_url(fort_language_url($key)) . '">' . "\n";
    echo '<link rel="alternate" hreflang="x-default" href="' . esc_url(fort_language_url('pt-br')) . '">' . "\n";
}, 2);
