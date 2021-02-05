<?php

class StaticGeneratorAtomicHLS {
    private $payload;

    public function __construct() {
        $this->payload = null;
    }

    public function setPayload($payload) {
        $this->payload = $payload;
    }

    public function generate() {
        return $this->generateStaticHls();
    }

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

}