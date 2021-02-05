<?php
/*
This class transform and patch any manifest (HLS and DASH), so that :
  * DASH : DRM keys are specified for each track
  * DASH+HLS :tracks are all mutualized with the reference manifest (web) (HLS and DASH)
  * DASH+HLS : childs (media playlist(HLS), segments(HLS and DASH)) have absolute URLs so that the manifest can be hosted anywhere

Examples :
  * DASH patch an existing HD manifest so that it can have mutualized SD tracks
    Test = curl 'https://bedrock.minitibone.com/manifestOptimizer/?url=https://origin.vod.salto.fr/salto/output/3/5/d/35d3f5ef5991f0dfc7b40bd1c0b3a5b6852b82ede4b927375cf40e4ce12fe5ab/35d3f5ef5991f0dfc7b40bd1c0b3a5b6852b82ede4b927375cf40e4ce12fe5ab_web_drm_hd_dash_v1.ism/Manifest.mpd'
    Player = https://vps.minitibone.com/6play/drm/?url=https%3A%2F%2Fbedrock.minitibone.com%2FmanifestOptimizer%2F%3Furl%3Dhttps%3A%2F%2Forigin.vod.salto.fr%2Fsalto%2Foutput%2F3%2F5%2Fd%2F35d3f5ef5991f0dfc7b40bd1c0b3a5b6852b82ede4b927375cf40e4ce12fe5ab%2F35d3f5ef5991f0dfc7b40bd1c0b3a5b6852b82ede4b927375cf40e4ce12fe5ab_web_drm_hd_dash_v1.ism%2FManifest.mpd&assetId=clip_225451&env=prod-m6_salto&forceSDpolicies=true
  * DASH create a new manifest (dummy target) on the fly
    Test = curl 'https://bedrock.minitibone.com/manifestOptimizer/?url=https://origin.vod.salto.fr/salto/output/3/5/d/35d3f5ef5991f0dfc7b40bd1c0b3a5b6852b82ede4b927375cf40e4ce12fe5ab/35d3f5ef5991f0dfc7b40bd1c0b3a5b6852b82ede4b927375cf40e4ce12fe5ab_dummy_drm_hd_dash_v1.ism/Manifest.mpd'
    Player = https://vps.minitibone.com/6play/drm/?url=https%3A%2F%2Fbedrock.minitibone.com%2FmanifestOptimizer%2F%3Furl%3Dhttps%3A%2F%2Forigin.vod.salto.fr%2Fsalto%2Foutput%2F3%2F5%2Fd%2F35d3f5ef5991f0dfc7b40bd1c0b3a5b6852b82ede4b927375cf40e4ce12fe5ab%2F35d3f5ef5991f0dfc7b40bd1c0b3a5b6852b82ede4b927375cf40e4ce12fe5ab_dummy_drm_hd_dash_v1.ism%2FManifest.mpd&assetId=clip_225451&env=prod-m6_salto&forceSDpolicies=true
  * HLS patch a HD manifest
    Test = curl 'https://bedrock.minitibone.com/manifestOptimizer/?url=https://origin.vod.salto.fr/salto/output/3/5/d/35d3f5ef5991f0dfc7b40bd1c0b3a5b6852b82ede4b927375cf40e4ce12fe5ab/35d3f5ef5991f0dfc7b40bd1c0b3a5b6852b82ede4b927375cf40e4ce12fe5ab_apple_tv_drm_hd_hls_v1.ism/Manifest.m3u8'
    Player = (no player for Fairplay...)
  * HLS create a new manifest (dummy target) on the fly
    Test = curl 'https://bedrock.minitibone.com/manifestOptimizer/?url=https://origin.vod.salto.fr/salto/output/3/5/d/35d3f5ef5991f0dfc7b40bd1c0b3a5b6852b82ede4b927375cf40e4ce12fe5ab/35d3f5ef5991f0dfc7b40bd1c0b3a5b6852b82ede4b927375cf40e4ce12fe5ab_dummy_drm_hd_hls_v1.ism/Manifest.m3u8'
    Player = (no player for Fairplay...)

*/

require_once("DevelopMpd.class.php");

class StaticGenerator {
  
  private $referenceTarget = 'web';

  private $mapHeights = array(
    'SD' => array(
      'sd' => array(
        'hls' => 'web_drm_sd_hls',
        'dash' => 'web_drm_sd_dash',
        'heights' => array('224', '360', '404', '540'),
      ),
    ),
    'HD' => array(
      'sd' => array(
        'hls' => 'web_drm_sd_hls',
        'dash' => 'web_drm_sd_dash',
        'heights' => array('224', '360', '404', '540'),
      ),
      'hd' => array(
        'hls' => 'web_drm_hd_hls',
        'dash' => 'web_drm_hd_dash',
        'heights' => array('720', '1080'),
      ),
    ),
    'HDmobile' => array(
      'sd' => array(
        'hls' => 'web_drm_sd_hls',
        'dash' => 'web_drm_sd_dash',
        'heights' => array('224', '360', '404', '540'),
      ),
      'hd' => array(
        'hls' => 'web_drm_hd_hls',
        'dash' => 'web_drm_hd_dash',
        'heights' => array('720'),
      ),
    ),
    'SDdummy' => array(
      'sd' => array(
        'hls' => 'web_drm_sd_hls',
        'dash' => 'web_drm_sd_dash',
        'heights' => array('540'),
      ),
    ),
    'HDdummy' => array(
      'sd' => array(
        'hls' => 'web_drm_sd_hls',
        'dash' => 'web_drm_sd_dash',
        'heights' => array('540'),
      ),
      'hd' => array(
        'hls' => 'web_drm_hd_hls',
        'dash' => 'web_drm_hd_dash',
        'heights' => array('720'),
      ),
    ),
  );

  private $mapTargets = array(
    'web'           => array('sd' => 'SD', 'hd' => 'HD'),
    'chromecast'    => array('sd' => 'SD', 'hd' => 'HD'),
    'android_mobile' => array('sd' => 'SD', 'hd' => 'HDmobile'),
    'android_tablet' => array('sd' => 'SD', 'hd' => 'HDmobile'),
    'android_tv'    => array('sd' => 'SD', 'hd' => 'HD'),
    'iphone'        => array('sd' => 'SD', 'hd' => 'HDmobile'),
    'ipad'          => array('sd' => 'SD', 'hd' => 'HDmobile'),
    'apple_tv'      => array('sd' => 'SD', 'hd' => 'HD'),
    'hbbtv'         => array('sd' => 'SD', 'hd' => 'HD'),
    'dummy'         => array('sd' => 'SDdummy', 'hd' => 'HDdummy'), // Test inexistant manifest
  );

  private $manifestUrl;
  private $overridenUrl = false;
  private $target;
  private $quality;
  private $format;

  private $debug;

  public function __construct($debug = false) {
    $this->debug = $debug;
  }

  public function generate($sourceManifestUrl) {
    if (preg_match("#_([^_]+(?:_(?:tv|mobile|tablet))?)_drm_([^_]+)_([^_]+)_v\d+\.ism/Manifest\.(?:mpd|m3u8)#", $sourceManifestUrl, $m)) {
      // ONLY _drm_ FOR NOW (Salto, VL, ...)
      $this->target = $m[1];
      $this->quality = $m[2];
      $this->format = $m[3];

      // DEBUG
      if ($this->debug) {
        echo 'Target = '.$this->target.PHP_EOL;
        echo 'Quality = '.$this->quality.PHP_EOL;
        echo 'Format = '.$this->format.PHP_EOL;
      }

    }
    if (!isset($this->mapTargets[$this->target]) || !in_array($this->format, array('dash','hls'))) {
      die("Error, cannont determine target/quality/format");
    }

    $this->manifestUrl = $sourceManifestUrl;

    $mapTarget = $this->mapTargets[$this->target];
    $mapHeight = $this->mapHeights[$mapTarget[$this->quality]];

    if (@file_get_contents($this->manifestUrl) === false) { // TO OPTIMIZE, MAKE A HEAD REQUEST ???
      // DesiredManifest Not Found, must base everything on the reference (=web?) manifest
      $overrideUrl = str_replace($this->target, $this->referenceTarget, $this->manifestUrl);
      if (@file_get_contents($overrideUrl) !== false) {
          // DEBUG
        if ($this->debug) {
          echo 'Desired Manifest = '.$this->manifestUrl.PHP_EOL;
          echo 'Overrided Manifest = '.$overrideUrl.PHP_EOL;
        }
        $this->manifestUrl = $overrideUrl; 
        $this->target = 'web';
      }
      else {
        die("Error, could not find a reference manifest for ".$this->target);
      }
    }

    switch ($this->format) {
      case 'dash':
        return $this->generateDASH($mapHeight);
      case 'hls':
        return $this->generateHLS($mapHeight);
    }


  }

  private function generateDASH($mapHeight) {
    
    // get SD
    $sdUrlPatch = $mapHeight['sd'][$this->format];
    $sdUrl = str_replace($this->target.'_drm_'.$this->quality.'_'.$this->format, $sdUrlPatch, $this->manifestUrl);
    $sdMpd = $this->getDeveloppedMpd($sdUrl);

    if ($this->quality === 'sd') {
      // SD is sufficient ! same for everyone !
      //return $sdMpd;
      // In case this is not true (eg. 'SDdummy'), let's do the logic anyway...
      $domSD = new DOMDocument();
      $domSD->loadXML($sdMpd);
      $RepresentationSDs = $domSD->getElementsByTagName('Representation');
      $toDelete = array();
      foreach ($RepresentationSDs as $RepresentationSD) {
        if (!preg_match("#video=#", $RepresentationSD->getAttribute('id')))
          continue; // Not a Video AdaptationSet
        $height = $RepresentationSD->getAttribute('height');
        if (!in_array($height, $mapHeight['sd']['heights'])) {
          // Remove this SD Representation that isn't needed (Mobile for example)
          $toDelete[] = $RepresentationSD;
        }
        else {
          // Do nothing, this Representation is cool here !
          
        }
      }
      // Delete Marked nodes
      foreach ($toDelete as $trash) {
        $trash->parentNode->removeChild($trash);
      }

      // Magic done, some tidying then return
      return tidy_repair_string($domSD->saveXML(), ['input-xml'=> 1, 'indent' => 1, 'wrap' => 0]);

    }
    else {
      // do HD processing and Merging !!
      $hdUrlPatch = $mapHeight['hd'][$this->format];
      $hdUrl = str_replace($this->target.'_drm_'.$this->quality.'_'.$this->format, $hdUrlPatch, $this->manifestUrl);
      $hdMpd = $this->getDeveloppedMpd($hdUrl);

      // Magic
      $domSD = new DOMDocument();
      $domSD->loadXML($sdMpd);
      $xpathSD = new DOMXpath($domSD);
      $domHD = new DOMDocument();
      $domHD->loadXML($hdMpd);

      $RepresentationHDs = $domHD->getElementsByTagName('Representation');
      $toInsert = array();
      $toDelete = array();
      $where = null;
      foreach ($RepresentationHDs as $RepresentationHD) {
        if (!preg_match("#video=#", $RepresentationHD->getAttribute('id'))) {
          // Not a Video AdaptationSet
          // Take SD everytime
          $sdRs = $xpathSD->query("//*[@id='".$RepresentationHD->getAttribute('id')."']");
          if (count($sdRs) === 1) {
            // Found !
            $sdR = $sdRs[0];
            // Clone the SD node for later insert
            $toInsert[] = array('where' => $RepresentationHD->parentNode, 'clone' => $sdR->cloneNode(true));
            // Mark to delete
            $toDelete[] = $RepresentationHD;
          }
          continue;
        }
        $height = $RepresentationHD->getAttribute('height');
        if (in_array($height, $mapHeight['sd']['heights'])) {
          // Use a SD Representation instead of the HD one
          $sdRs = $xpathSD->query("//*[@height='".$height."']");
          if (count($sdRs) === 1) {
            // Found !
            $sdR = $sdRs[0];
            // Clone the SD node for later insert
            $toInsert[] = array('where' => $RepresentationHD->parentNode, 'clone' => $sdR->cloneNode(true));
            // store where to append :-/
            //if ($where === null)
            //  $where = $RepresentationHD->parentNode;
            // Mark to delete
            $toDelete[] = $RepresentationHD;
          }
        }
        else if (!in_array($height, $mapHeight['hd']['heights']) && !in_array($height, $mapHeight['sd']['heights'])) {
          // Remove this HD (or SD) Representation that isn't needed (Mobile for example)
          $toDelete[] = $RepresentationHD;
        }
        else {
          // Do nothing, this Representation is cool here !
          
        }
      }
      // Insert SD cloned nodes
      foreach ($toInsert as $elt) {
        $newNode = $domHD->importNode($elt['clone'], true);
        $elt['where']->appendChild($newNode);
      }
      // Delete Marked nodes
      foreach ($toDelete as $trash) {
        $trash->parentNode->removeChild($trash);
      }

      // Magic done, some tidying then return
      return tidy_repair_string($domHD->saveXML(), ['input-xml'=> 1, 'indent' => 1, 'wrap' => 0]);
    }


  }

  private function getDeveloppedMpd($url) {
    $developMpd = new DevelopMpd();
    // return a factorized MPD, with a patched BaseURL & media/initialization URLs. can be served statically
    return $developMpd->develop($url);
  }

  private function generateHLS($mapHeight) {
    // get SD
    $sdUrlPatch = $mapHeight['sd'][$this->format];
    $sdUrl = str_replace($this->target.'_drm_'.$this->quality.'_'.$this->format, $sdUrlPatch, $this->manifestUrl);
    $sdMaster = $this->getMasterM3u8($sdUrl);
    
    if ($this->quality === 'sd') {
      // SD is sufficient ! same for everyone !
      //return $this->rebuildMasterM3u8($sdMaster);
      // In case this is not true (eg. 'SDdummy'), let's do the logic anyway...
      foreach ($sdMaster['variants'] as $k => $v) {
        if (!in_array($k, $mapHeight['sd']['heights'])) {
          unset($sdMaster['variants'][$k]); // Remove un-desired SD variant
        }
      }
      foreach ($sdMaster['keyframes'] as $k => $v) {
        if (!in_array($k, $mapHeight['sd']['heights'])) {
          unset($sdMaster['keyframes'][$k]); // Remove un-desired SD keyframe
        }
      }
      return $this->rebuildMasterM3u8($sdMaster);

    }
    else {
      // do HD processing and Merging !!
      $hdUrlPatch = $mapHeight['hd'][$this->format];
      $hdUrl = str_replace($this->target.'_drm_'.$this->quality.'_'.$this->format, $hdUrlPatch, $this->manifestUrl);
      $hdMaster = $this->getMasterM3u8($hdUrl);

      // Magic
      $sdMaster['extXSessionKey'][] = array_pop($hdMaster['extXSessionKey']); // Append HD Keys
      foreach ($sdMaster['variants'] as $k => $v) {
        if (!in_array($k, $mapHeight['sd']['heights'])) {
          unset($sdMaster['variants'][$k]); // Remove un-desired SD variant
        }
      }
      foreach ($hdMaster['variants'] as $k => $v) { 
        if (in_array($k, $mapHeight['hd']['heights'])) {
          $sdMaster['variants'][$k] = $v; // Add a HD variant if required
        }
      }
      foreach ($sdMaster['keyframes'] as $k => $v) {
        if (!in_array($k, $mapHeight['sd']['heights'])) {
          unset($sdMaster['keyframes'][$k]); // Remove un-desired SD keyframe
        }
      }
      foreach ($hdMaster['keyframes'] as $k => $v) { 
        if (in_array($k, $mapHeight['hd']['heights'])) {
          $sdMaster['keyframes'][$k] = $v; // Add a HD keyframe if required
        }
      }

      return $this->rebuildMasterM3u8($sdMaster);
  
    }

  }

  private function getMasterM3u8($url) {
    $lines = file($url, FILE_IGNORE_NEW_LINES);
    $baseUri = '';
    if (preg_match("#^(.*\.ism/)[^/]*\.m3u8#", $url, $m)) {
      $baseUri = $m[1];
    }
    // Patch URI/URLs from master. can be served statically
    // Structurate the manifest
    $steps = array('header', 'extXSessionKey', 'audio', 'subs', 'variants', 'keyframes');
    $manifest = array(
      'header' => array(),
      'extXSessionKey' => array(),
      'audio' => array(),
      'subs' => array(),
      'variants' => array(),
      'keyframes' => array(),
    );
    $stepId = 0;
    $nbLines = count($lines);
    for ($i=0; $i<$nbLines; $i++) {
      $line = $lines[$i];
      switch (true) {
        case (preg_match("%^#EXT-X-SESSION-KEY:.*%", $line, $m)):
          $stepId = 1;
          $manifest[$steps[$stepId]][] = $line;
          break; 
        case (preg_match("%^# AUDIO groups%", $line)):
          $stepId = 2;
          $manifest[$steps[$stepId]][] = $line;
          break; 
        case (preg_match("%^# SUBTITLES groups%", $line)):
          $stepId = 3;
          $manifest[$steps[$stepId]][] = $line;
          break; 
        case (preg_match("%^# variants%", $line)):
          $stepId = 4;
          $manifest[$steps[$stepId]][] = $line;
          break; 
        case (preg_match("%^# keyframes%", $line)):
          $stepId = 5;
          $manifest[$steps[$stepId]][] = $line;
          break; 
        case (preg_match("%^#EXT-X-.*,URI=\"([^\"]+)\"%", $line, $m)):
          $newUri = $baseUri . $m[1];
          $newLine = str_replace($m[1], $newUri, $line); // Patch URI to use absolute original URI
          if (preg_match("#RESOLUTION=\d+x(\d+),#", $line, $m)) {
            $manifest[$steps[$stepId]][(string)$m[1]] = $newLine;
          }
          else {
            $manifest[$steps[$stepId]][] = $newLine;
          }
          break;
        case (preg_match("%^#EXT-X-STREAM-INF:.*RESOLUTION=\d+x(\d+),%", $line, $m) && isset($lines[$i+1])):
          $newLine = $baseUri . $lines[$i+1];
          $manifest[$steps[$stepId]][(string)$m[1]] = $line . "\n" . $newLine;
          $i++;
          break;
        default:
          if (empty($line))
            break; // forget empty lines
          $manifest[$steps[$stepId]][] = $line;
          break;
      }
    }
    return $manifest;
  }

  private function rebuildMasterM3u8($manifest) {
    $m3u8 = '';
    $m3u8 .= implode("\n", $manifest['header'])."\n";
    $m3u8 .= implode("\n", $manifest['extXSessionKey'])."\n"."\n";
    $m3u8 .= implode("\n", $manifest['audio'])."\n"."\n";
    $m3u8 .= implode("\n", $manifest['subs'])."\n"."\n";
    $m3u8 .= implode("\n", $manifest['variants'])."\n"."\n";
    $m3u8 .= implode("\n", $manifest['keyframes'])."\n";
    return $m3u8;
  }
}


// Local Test
if (isset($argv[1])) {
  $me = new StaticGenerator();
  echo $me->generate($argv[1]);
}
