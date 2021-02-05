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

require_once("DevelopMpd.class.php");

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
                return $this->generateStaticHls();
                break;
            case 'DASH':
                return $this->generateStaticDash();
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
    private function generateStaticDash() {
        // Load First MPD Video
        $url = $this->payload->origin . $this->payload->streams['video'][0];
        $mpd = $this->loadMPDandDevelopNodes($url);
        $dom = new DOMDocument();
        $dom->loadXML($mpd);
        $Period = $this->findElementByTagAndAttr($dom, 'Period');
        $videoAdaptationSet = $this->findElementByTagAndAttr($dom, 'AdaptationSet' , array('contentType' => 'video'));
        $tempRepresentation =$this->findElementByTagAndAttr($dom, 'Representation');
        if ($videoAdaptationSet->hasAttribute('codecs')) {
            $tempRepresentation->setAttribute('codecs', $videoAdaptationSet->getAttribute('codecs'));
            $videoAdaptationSet->removeAttribute('codecs');
        }
        $minBandwidth = $tempRepresentation->hasAttribute('bandwidth') ? $tempRepresentation->getAttribute('bandwidth') : 0;
        $maxBandwidth = $minBandwidth;
        $maxWidth = $videoAdaptationSet->hasAttribute('width') ? $videoAdaptationSet->getAttribute('width') : 0;
        $maxHeight = $videoAdaptationSet->hasAttribute('height') ? $videoAdaptationSet->getAttribute('height') : 0;
        if ($videoAdaptationSet->hasAttribute('height')) {
            $videoAdaptationSet->removeAttribute('height');
        }
        if ($videoAdaptationSet->hasAttribute('width')) {
            $videoAdaptationSet->removeAttribute('width');
        }
        $videoAdaptationSet->setAttribute('par','16:9');
        // Load Other MPD Video and merge
        for ($i=1; $i<count($this->payload->streams['video']); $i++) {
            $url = $this->payload->origin . $this->payload->streams['video'][$i];
            $tempMpd = $this->loadMPDandDevelopNodes($url);
            $tempDom = new DOMDocument();
            $tempDom->loadXml($tempMpd);
            $tempAdaptationSet = $this->findElementByTagAndAttr($tempDom, 'AdaptationSet');
            $tempRepresentation = $this->findElementByTagAndAttr($tempDom, 'Representation');
            if ($tempAdaptationSet->hasAttribute('codecs')) {
                $tempRepresentation->setAttribute('codecs', $tempAdaptationSet->getAttribute('codecs'));
                // $tempAdaptationSet->removeAttribute('codecs'); // No need, we won't need the cleansed AS
            }    
            $tempBandwidth = $tempRepresentation->hasAttribute('bandwidth') ? $tempRepresentation->getAttribute('bandwidth') : 0;
            if (!$tempRepresentation->hasAttribute('height')) {
                $tempRepresentation->setAttribute('height', $tempAdaptationSet->getAttribute('height'));
            }
            $tempHeight = $tempRepresentation->getAttribute('height');
            if (!$tempRepresentation->hasAttribute('width')) {
                $tempRepresentation->setAttribute('width', $tempAdaptationSet->getAttribute('width'));
            }
            $tempWidth = $tempRepresentation->getAttribute('width');
            $maxBandwidth = ($tempBandwidth > $maxBandwidth) ?  $tempBandwidth: $maxBandwidth;
            $minBandwidth = ($tempBandwidth < $minBandwidth) ?  $tempBandwidth: $minBandwidth;
            $maxHeight = ($tempHeight > $maxHeight) ? $tempHeight : $maxHeight;
            $maxWidth = ($tempWidth > $maxWidth) ? $tempWidth : $maxWidth;

            // Clone and inject
            $from = $tempRepresentation->cloneNode(true);
            $to = $dom->importNode($from, true);
            $videoAdaptationSet->appendChild($to);
        }
        // Patch AdaptationSet and Representations
        $videoAdaptationSet->setAttribute('maxBandwidth', $maxBandwidth);
        $videoAdaptationSet->setAttribute('minBandwidth', $minBandwidth);
        $videoAdaptationSet->setAttribute('maxHeight', $maxHeight);
        $videoAdaptationSet->setAttribute('maxWidth', $maxWidth);

        // Load MPD Audio and merge
        for ($i=0; $i<count($this->payload->streams['audio']); $i++) {
            $url = $this->payload->origin . $this->payload->streams['audio'][$i];
            $tempMpd = $this->loadMPDandDevelopNodes($url);
            $tempDom = new DOMDocument();
            $tempDom->loadXml($tempMpd);
            $tempAdaptationSet = $this->findElementByTagAndAttr($tempDom, 'AdaptationSet');
            $from = $tempAdaptationSet->cloneNode(true);
            $to = $dom->importNode($from, true);
            $Period->appendChild($to);
        }

        // Load MPD Text and merge
        for ($i=0; $i<count($this->payload->streams['text']); $i++) {
            $url = $this->payload->origin . $this->payload->streams['text'][$i];
            $tempMpd = $this->loadMPDandDevelopNodes($url);
            $tempDom = new DOMDocument();
            $tempDom->loadXml($tempMpd);
            $tempAdaptationSet = $this->findElementByTagAndAttr($tempDom, 'AdaptationSet');
            $from = $tempAdaptationSet->cloneNode(true);
            $to = $dom->importNode($from, true);
            $Period->appendChild($to);
        }


        // Clean AdaptationSet(s) (id, group)
        $AdaptationSets = $dom->getElementsByTagName('AdaptationSet');
        $ASid = 0;
        foreach ($AdaptationSets as $AdaptationSet) {
            $AdaptationSet->setAttribute('id', ++$ASid);
            switch ($AdaptationSet->getAttribute('contentType')) {
                case 'video':
                    $AdaptationSet->setAttribute('group', '2');
                    break;
                case 'audio':
                    $AdaptationSet->setAttribute('group', '1');
                    break;
                case 'text':
                    $AdaptationSet->setAttribute('group', '3');
                    break;
            }
        }

        // Magic done, some tidying then return
        return tidy_repair_string($dom->saveXML(), ['input-xml'=> 1, 'indent' => 1, 'wrap' => 0]);
    }

    private function loadMPDandDevelopNodes($url) {
        $developMpd = new DevelopMpd();
        // return a factorized MPD, with a patched BaseURL & media/initialization URLs. can be served statically
        return $developMpd->develop($url, false);    
    }

    private function findElementByTagAndAttr($dom, $tag, $how = array()) {
        $where = $dom->getElementsByTagName($tag);
        foreach ($where as $elt) {
            $found = false;
            if ($how === array()) {
                $found = true;
            }
            else {
                foreach ($how as $k => $v) {
                    if ($elt->hasAttribute($k) && $elt->getAttribute($k) == $v) {
                        $found = true;
                    }
                }
            }
            if ($found) {
                return $elt;
            }
        }
        return false;
    }

 }