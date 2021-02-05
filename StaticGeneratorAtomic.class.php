<?php
/**
# Context
- current Content dir = <S3dirname>/
- Static manifests path = <S3dirname>/static/
- individual ISM = <S3dirname>/<indivISM>.ism

# DASH / MPD
- Prepare <MPD> node
- Prepare <Period> node
- Get all individual USP-generated `.mpd`
- Group AdaptationSet
  - one for all contentType=video
  - one for each Audio
  - one for each SubText
- Foreach (required tracks)
  - (if first track for an AdaptationSet) Get AdaptationSet as father
  - Get individual USP-generated `.mpd`
  - Move <ContentProtection> (and childs) into <Representation>
  - Patch <SegmentTemplate> @initialization and @media
    - use the absolute path = /<S3dirname>/<indivISM>.ism/dash/<original@value>
  - Move <Representation> (and childs) into the correct AdaptationSet
- Populate <AdaptationSet> attributes
  - @id must be unique (and incremental)
  - @group must be grouped by `contentType` => audio=1, video=2, text=3
  - [video]
    - `@par` to set to `16:9`
    - `@minBandwidth` to set to minimum `bandwidth` from included `Representation@bandwidth`
    - `@maxBandwidth` to set to maximum `bandwidth` from included `Representation@bandwidth`
    - `@maxWidth` to set to maximum `width` from included `Representation@width`
    - `@maxHeight` to set to maximum `height` from included `Representation@height`
- Populate <MPD> atttributes
  - based on a `video` .mpd : `@mediaPresentationDuration` `@maxSegmentDuration` `@minBufferTime` et les bons `@xmlns` (DRM, ...)
- DONE

# HLS / M3U8
- prepare sections
  - header
    - static => `#EXTM3U\n#EXT-X-VERSION:5\n`
  - extXSessionKey
  - audio
  - subs
  - variants
  - keyframes
- Foreach (required tracks)
  - extXSessionKey => add unique `#EXT-X-SESSION-KEY`
  - audio => TODO MAKE GROUP !!!
  - subs => TODO MAKE GROUP !!! AUTOSELECT ???? DEFAULT ????
  - variants => TODO MAKE GROUP !!!
  - keyframe => TODO MAKE GROUP !!!
  - Prefix the URis with the absolute individual ISM path = /<S3dirname>/<indivISM>.ism/<originalURI>
- Build master manifest
  - concatenate all sections
- DONE
  
 */

require_once("StaticGeneratorAtomicHLS.class.php");
require_once("StaticGeneratorAtomicDASH.class.php");

 class StaticGeneratorAtomic {

    private $payload;

    public function __construct() {
        $this->payload = null;
    }

    public function setPayload($payload) {
        $this->payload = $payload;
        $this->testPayload();
    }

    private function testPayload() {
        if (!isset($this->payload->type) || !in_array($this->payload->type, array('HLS','DASH'))) {
            die('Incorrect `type`');
        }
        if (!isset($this->payload->origin)) {
            die('Please specify an `origin` URL');
        }
        if (!isset($this->payload->streams) || !is_array($this->payload->streams)) {
            die('Incorrect or missing `streams[]`');
        }
    }

    public function generate() {
        if (empty($this->payload)) {
            die("Can't be done...");
        }
        switch ($this->payload->type) {
            case 'HLS':
              $generator = new StaticGeneratorAtomicHLS();
              $generator->setPayload($this->payload);
              return $generator->generate();
              break;
            case 'DASH':
                $generator = new StaticGeneratorAtomicDASH();
                $generator->setPayload($this->payload);
                return $generator->generate();
                break;
        }
    }

    /**
     * HLS SECTION
     */
    private function generateStaticHls() {
        // Init container
        $staticManifest = array(
            'header' => array('#EXTM3U','#EXT-X-VERSION:4','## Created with Brain and Fingers'),
            'extXSessionKey' => array(),
            'audio' => array('# AUDIO groups'),
            'subs' => array('# SUBTITLES groups'),
            'variants' => array('# variants'),
            'keyframes' => array('# keyframes'),
        );
        
        // fetch all m3u8
        $m3u8s = array();
        foreach ($this->payload->streams as $type => $uris) {    
            foreach ($uris as $uri) {
                $url = $this->payload->origin . $uri;
                $m3u8s[$type][] = $this->getMasterM3u8($url);
            }
        }

        // Process audio (group, ...)
        $audioGroupId = null;
        $hasDefault = false;
        if (isset($m3u8s['audio'])) {
            foreach ($m3u8s['audio'] as $audioM3u8) {
                if (isset($audioM3u8['audio'],$audioM3u8['audio'][0])) {
                    $groupLine = $audioM3u8['audio'][0];
                    var_dump($groupLine);
                }
                var_dump($audioM3u8);die;
                // get group id
                //if (preg_match("##"))
            }
        }

        // Process subs (group, ...)

        // Process video (drm, add audio group, add sub group)

        // Process keyframes

        return $this->rebuildMasterM3u8($staticManifest);
    }

    /**
     * Get Master HLS Manifest and patch its resources (media playlist URL) so it can be served statically
     */
    private function getMasterM3u8($url) {
        $lines = file($url, FILE_IGNORE_NEW_LINES);
        $baseUri = '';
        if (preg_match("#^(.*\.ism/)[^/]*\.m3u8#", $url, $m)) {
          $baseUri = $m[1];
        }
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
              $manifest[$steps[$stepId]][] = $lines[++$i];
              break; 
            case (preg_match("%^# SUBTITLES groups%", $line)):
              $stepId = 3;
              $manifest[$steps[$stepId]][] = $lines[++$i];
              break; 
            case (preg_match("%^# variants%", $line)):
              $stepId = 4;
              $manifest[$steps[$stepId]][] = $lines[++$i];
              break; 
            case (preg_match("%^# keyframes%", $line)):
              $stepId = 5;
              $manifest[$steps[$stepId]][] = $lines[++$i];
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

    /**
     * DASH SECTION
     */


 }