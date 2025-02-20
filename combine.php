<?php

/**
  * CSS und Javascript Combinator 0.5
  * Copyright 2006 Niels Leenheer
  *
  * Jedem, der eine Kopie dieser Software und der zugehörigen 
  * Dokumentationsdateien (die "Software") erhält, wird hiermit unentgeltlich
  * die Genehmigung erteilt, mit der Software uneingeschränkt zu
  * handeln, einschließlich und ohne Einschränkung das Recht,
  * die Software zu verwenden, zu kopieren, zu ändern, zusammenzufügen,
  * zu veröffentlichen, zu verteilen, unterzulizenzieren 
  * und/oder Kopien der Software zu verkaufen.
  * Personen, denen die Software zur Verfügung gestellt wird, dies zu gestatten,
  * vorbehaltlich der folgenden Bedingungen:
  *
  * Der obige Copyright-Vermerk und dieser Genehmigungsvermerk müssen
  * in allen Kopien oder wesentlichen Teilen der Software enthalten sein.
  *
  * DIE SOFTWARE WIRD „WIE BESEHEN“ BEREITGESTELLT, OHNE JEGLICHE GARANTIE,
  * AUSDRÜCKLICH ODER STILLSCHWEIGEND, EINSCHLIESSLICH ABER NICHT BESCHRÄNKT
  * AUF DIE GARANTIEN
  * DER MARKTGÄNGIGKEIT, EIGNUNG FÜR EINEN BESTIMMTEN ZWECK UND
  * NICHTVERLETZUNG. IN KEINEM FALL SIND DIE AUTOREN ODER COPYRIGHTINHABER
  * HAFTBAR FÜR JEGLICHE ANSPRÜCHE, SCHÄDEN ODER ANDERE HAFTBARKEIT, SEIEN DIES AUS EINER
  * VERTRAGS-, UNERLAUBTEN HANDLUNG ODER ANDERWEITIG, DIE AUS, AUS ODER IN VERBINDUNG MIT DER
  * SOFTWARE ODER DER VERWENDUNG ODER ANDEREN UMGANG MIT DER SOFTWARE ENTSTEHEN.
  *
  * @modified 2025-02-20 by Thomas Boettcher <github[at]ztatement[dot]com>
  * @version 1.0
  * @file $Id: combine.php 1 Thu Feb 20 2025 11:41:56 GMT+0100Z ztatement $
  * @see https://github.com/ztatement/CSS-and-Javascript-Combinator
  * @updated for PHP 8.4+/9 compatibility
  */

  // Use strict typing for better type checking and performance
  declare(strict_types=1);

  // Configuration variables
  $cache    = true;
  $cachedir = __DIR__ . '/cache'; // Use __DIR__ instead of dirname(__FILE__)
  $cssdir   = __DIR__ . '/css';
  $jsdir    = __DIR__ . '/javascript';

  // Validate and sanitize input
  $type   = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING) ?? '';
  $files  = filter_input(INPUT_GET, 'files', FILTER_SANITIZE_STRING) ?? '';

  // Determine the directory and type we should use
  $base = match ($type)
  {
    'css'         => realpath($cssdir),
    'javascript'  => realpath($jsdir),
    default       => null,
  };

  if ($base === null)
  {
    header("HTTP/1.1 503 Not Implemented");
    exit;
  }

  $elements = explode(',', $files);

  // Determine last modification date of the files
  $lastmodified = 0;

  foreach ($elements as $element)
  {
    $path = realpath($base . '/' . $element);

    if (($type === 'javascript' && !str_ends_with($path, '.js')) ||
        ($type === 'css' && !str_ends_with($path, '.css')))
    {
      header("HTTP/1.1 403 Forbidden");
      exit;
    }

    if (!str_starts_with($path, $base) || !file_exists($path))
    {
        header("HTTP/1.1 404 Not Found");
        exit;
    }

    $lastmodified = max($lastmodified, filemtime($path));
  }

  // Send Etag hash
  $hash = $lastmodified . '-' . md5($files);
  header("Etag: \"$hash\"");

  if (
    isset($_SERVER['HTTP_IF_NONE_MATCH']) &&
    stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) === "\"$hash\""
  ) {
    // Return visit and no modifications, so do not send anything
    header('HTTP/1.1 304 Not Modified');
    header('Content-Length: 0');
  }
  else
  {
    // First time visit or files were modified
    if ($cache)
    {
      // Determine supported compression method
      $encoding = match (true)
      {
        str_contains($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip')    => 'gzip',
        str_contains($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'deflate') => 'deflate',
        default => 'none',
      };

      // Check for buggy versions of Internet Explorer
      if (!str_contains($_SERVER['HTTP_USER_AGENT'] ?? '', 'Opera') && preg_match(
        '/^Mozilla\/4\.0 \(compatible; MSIE ([0-9]\.[0-9])/i',
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        $matches
      ))
      {
        $version = floatval($matches[1]);

        if ($version < 6 || ($version == 6 && !str_contains($_SERVER['HTTP_USER_AGENT'], 'EV1')))
        {
          $encoding = 'none';
        }
      }

      // Try the cache first to see if the combined files were already generated
      $cachefile = "cache-$hash.$type" . ($encoding !== 'none' ? ".$encoding" : '');

      if (file_exists("$cachedir/$cachefile"))
      {
        if ($fp = fopen("$cachedir/$cachefile", 'rb'))
        {
          if ($encoding !== 'none')
          {
            header("Content-Encoding: $encoding");
          }

          header("Content-Type: text/$type");
          header('Content-Length: ' . filesize("$cachedir/$cachefile"));

          fpassthru($fp);
          fclose($fp);
          exit();
        }
      }
    }

    // Get contents of the files
    $contents = '';
    foreach ($elements as $element)
    {
      $path = realpath("$base/$element");
      $contents .= "\n\n" . file_get_contents($path);
    }

    // Send Content-Type
    header("Content-Type: text/$type");

    if (isset($encoding) && $encoding !== 'none')
    {
      // Send compressed contents
      $contents = gzencode(
        $contents,
        9,
        $encoding === 'gzip' ? FORCE_GZIP : FORCE_DEFLATE
      );
      header("Content-Encoding: $encoding");
      header('Content-Length: ' . strlen($contents));
      echo $contents;
    }
    else
    {
      // Send regular contents
      header('Content-Length: ' . strlen($contents));
      echo $contents;
    }

    // Store cache
    if ($cache)
    {
      file_put_contents("$cachedir/$cachefile", $contents);
    }
}

 /*
  * // verwendung in htacces
  * RewriteEngine On
  * RewriteBase /
  * RewriteRule ^css/(.*\.css) /combine.php?type=css&files=$1
  * RewriteRule ^javascript/(.*\.js) /combine.php?type=javascript&files=$1
  */

/**
  * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ *
  * @LastModified: 2025-02-20 $
  * @Date $LastChangedDate: Thu Feb 20 2025 11:41:56 GMT+0100 $
  * @editor: $LastChangedBy: ztatement $
  * ----------------
  *
  * $Date$     : $Revision$          : $LastChangedBy$  - Description
  * 2025-02-20 : 1.0.0.2025.02.20    : ztatement        - updated for PHP 8.4+/9 compatibility
  * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ *
  * Local variables:
  * tab-width: 2
  * c-basic-offset: 2
  * c-hanging-comment-ender-p: nil
  * End:
  */
