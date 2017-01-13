<?php
declare (strict_types = 1);
define('__ROOT_PATH__', $_SERVER['DOCUMENT_ROOT']);

// model

function ls(string $cd = null) {
  $ocd = getcwd();
  $cd ?? $cd = $ocd;
  if (!file_exists($cd)) {
    return $ls = [0 => '..', 1 => '(not found)'];
  }

  chdir($cd);

  $ls = scandir($cd, SCANDIR_SORT_ASCENDING);
  if (!$ls) {
    return $ls = [0 => '..', 1 => '(no access)'];
  }

  $is_root = $cd === __ROOT_PATH__;
  $dirs = array_filter($ls, function (string $x) use ($is_root) {
    return $x !== '.' && !($is_root && $x === '..') && is_dir($x);
  });
  $files = array_filter($ls, function (string $x) {
    return strpos($x, '.') !== 0 && is_file($x);
  });
  $ls = array_merge(array_map(function (string $x) {
    return $x . '/';
  }, $dirs), $files);

  chdir($ocd);

  return $ls;
}

// presenter

function die_code(int $code = 404, string $message = '') {
  function mylowerstr(string $s) {
    $l = strlen($s);
    for ($i = 1; $i < $l - 1; $i++) {
      $c = $s{$i - 1} . $s{$i} . $s{$i + 1};

      if (preg_match('/[^a-zA-Z][A-Z][a-z]/', $c)) {
        $s{$i} = strtolower($s{$i});
      }

    }
    return $s;
  }

  $codes = [
    100 => 'Continue',
    101 => 'Switching Protocols',

    200 => 'OK',
    201 => 'Created',
    202 => 'Accepted',
    203 => 'Non-Authoritative Information',
    204 => 'No Content',
    205 => 'Reset Content',
    206 => 'Partial Content',

    300 => 'Multiple Choices',
    301 => 'Moved Permanently',
    302 => 'Found',
    303 => 'See Other',
    304 => 'Not Modified',
    305 => 'Use Proxy',
    306 => '(Unused)',
    307 => 'Temporary Redirect',

    400 => 'Bad Request',
    401 => 'Unauthorized',
    402 => 'Payment Required',
    403 => 'Forbidden',
    404 => 'Not Found',
    405 => 'Method Not Allowed',
    406 => 'Not Acceptable',
    407 => 'Proxy Authentication Required',
    408 => 'Request Timeout',
    409 => 'Conflict',
    410 => 'Gone',
    411 => 'Length Required',
    412 => 'Precondition Failed',
    413 => 'Request Entity Too Large',
    414 => 'Request-URI Too Long',
    415 => 'Unsupported Media Type',
    416 => 'Requested Range Not Satisfiable',
    417 => 'Expectation Failed',

    500 => 'Internal Server Error',
    501 => 'Not Implemented',
    502 => 'Bad Gateway',
    503 => 'Service Unavailable',
    504 => 'Gateway Timeout',
    505 => 'HTTP Version Not Supported',
  ];

  $msg = $codes[$code] ?? '';
  $s = mylowerstr($msg);
  $title = $code . ' ' . $msg;

  header('HTTP/1.1 ' . $title);
  die('<!DOCTYPE html><title>' . $title . '</title><meta name=viewport content="width=100">' . $s . '. ' . $message);
}

function build_href(string $href, string $query = '') {
  if ($query !== '') {
    $query = '?' . $query;
  }

  return join(array_map(function (string $u) {return rawurlencode($u);}, explode('/', $href)), '/') . $query;
}

if (isset($_GET['d'])) {
  $cd = realpath(__ROOT_PATH__ . $_GET['d']);
} else {
  $cd = realpath(__ROOT_PATH__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

  if (!is_dir($cd)) {
    $cd = getcwd();
  }
}

if (!$cd or strpos($cd, __ROOT_PATH__) !== 0 or !is_dir($cd)) {
  die_code(403);
}

$ls = ls($cd);
$dir = substr($cd, strlen(__ROOT_PATH__)) . '/';

function normalizePath($path) {
  $realpath = realpath(__ROOT_PATH__ . $path);

  if (!$realpath) {
    return false;
  }

  if (strpos($realpath, __ROOT_PATH__) !== 0) {
    return false;
  } else {
    $path = substr($realpath, strlen(__ROOT_PATH__));
    if (is_dir($realpath)) {
      return $path . '/';
    }
    return $path;
  }
}

$obj = array_map(function (string $u) use ($dir) {
  $href = normalizePath($dir . $u);
  $query = '';

  if (!$href) {
    $href = '#';
  }

  if (pathinfo($href, PATHINFO_EXTENSION) === 'js') {
    $href = '/test.php' . $href;
    $query = 'w=1';
  }

  return [
    'href' => build_href($href, $query),
    'text' => $u,
  ];
}, $ls);

if (isset($_GET['d'])) {
  header('Content-Type: text/plain; charset=UTF-8');
  die(json_encode($obj));
}

$links = array_map(function (array $u) use ($dir) {
  return '<a href="' . $u['href'] . '">' . htmlentities($u['text']) . '</a>';
}, $obj);

// view

?><!DOCTYPE html>
<title>Ls <?=$dir?></title>
<meta name=viewport content="width=device-width, initial-scale=1.0, maximum-scale=1">

<style>
html, input, button {
  font: 16px/1.4 'Open Sans', sans-serif; }
body {
  margin: 0; }
a {
  text-decoration: none; }
ul {
  list-style: none; }
b {
  font-weight: 600; }
form {
  margin: 15px 0 0 40px; }
label {
  /*content: 'Ls\00a0';*/
  display: inline-block;
  margin-left: -3em;
  width: 3em;
  text-align: right; }
input {
  padding: 0;
  border: none;
  outline: none; }
button {
  display: none; }

@font-face {
  font-family: 'open sans';
  font-weight: 400;
  font-style: normal;

  src: local('open sans'), url(/s/Open_Sans/OpenSans-Regular.ttf); }
@font-face {
  font-family: 'open sans';
  font-weight: 600;
  font-style: normal;

  src: local('open sans'), url(/s/Open_Sans/OpenSans-SemiBold.ttf); }

</style>

<form><label>Ls&nbsp;</label><input autocomplete=off autofocus><button>Go</button></form>
<main>
<ul>
  <li><?=join($links, '</li>
  <li>')?></li>
</ul>
</main>

<script src="/s/ls/ls.min.js"></script>
