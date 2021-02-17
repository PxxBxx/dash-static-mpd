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
            'header' => array('#EXTM3U','#EXT-X-VERSION:5','## Created with Brain and Fingers'),
            'extXSessionKey' => array(),
            'audio' => array('# AUDIO groups'),
            'subs' => array('# SUBTITLES groups'),
            'variants' => array('# variants'),
            'variantsAudio' => array('# variants'),
            'keyframes' => array('# keyframes'),
        );

        // we need Group IDs below...
        $audioGroup = null;
        $textGroup = null;

        // fetch all m3u8
        $m3u8s = array('audio' => array(), 'text' => array(), 'video' => array());
        foreach ($this->payload->streams as $type => $uris) {
            foreach ($uris as $uri) {
                $url = $this->payload->origin . $uri;
                $master = $this->getAtomicMaster($url);
                $m3u8s[$type][] = $master;
                if (in_array($master->type, array('audio','text')) && is_null(${$master->type."Group"})) {
                    ${$master->type."Group"} = $master->groupId;
                }
            }
        }

        // Let's merge and magic
        // AUDIO
        foreach ($m3u8s['audio'] as $idx => $audio) {
            if ($idx === 0) {
                // First Audio, add a dedicated Variant
                $variantAudio = $audio->variantLine;
                if (!is_null($textGroup)) {
                    // there are subs, must associate to group
                    $variantAudio .= ',SUBTITLES="' . $textGroup . '"';
                }
                $staticManifest['variantsAudio'][] = $variantAudio;
                $staticManifest['variantsAudio'][] = $audio->uri;
            }
            $audioLine = $audio->groupLine . ',URI="' . $audio->uri . '"';
            if ($idx > 0) {
                // remove ,DEFAULT=YES for tracks > 0
                $audioLine = str_replace(',DEFAULT=YES', '', $audioLine);
            }
            $staticManifest['audio'][] = $audioLine;
        }
        // TEXT
        foreach ($m3u8s['text'] as $idx => $text) {
            $textLine = $text->groupLine . ',URI="' . $text->uri . '"';
            if ($idx > 0) {
                // remove ,DEFAULT=YES for tracks > 0
                $textLine = str_replace(',DEFAULT=YES','',$textLine);
            }
            // ensure ,AUTOSELECT=NO <---- NOPE ! I would love to, but current production has them at YES :'(
            //$textLine = str_replace(',AUTOSELECT=YES',',AUTOSELECT=NO',$textLine);

            $staticManifest['subs'][] = $textLine;
        }

        // VIDEO
        foreach ($m3u8s['video'] as $idx => $video) {
            $variantVideo = $video->variantLine;
            // Add audio Group
            if (!is_null($audioGroup)) {
                $variantVideo .= ',AUDIO="' . $audioGroup . '"';
            }
            // Add text Group
            if (!is_null($textGroup)) {
                $variantVideo .= ',SUBTITLES="' . $textGroup . '"';
            }

            // Add Variant
            $staticManifest['variants'][] = $variantVideo; // Header
            $staticManifest['variants'][] = $video->uri; // URI
            // Add Keyframe
            if (!empty($video->keyframe)) {
                $staticManifest['keyframes'][] = $video->keyframe;
            }
            // Add DRM Key
            if (!empty($video->key) && !in_array($video->key, $staticManifest['extXSessionKey'])) {
                $staticManifest['extXSessionKey'][] = $video->key;
            }
        }



/*            //$master = $this->getMasterM3u8($url);
            // DRM key ?
            if (!empty($master['extXSessionKey']) && !in_array($master['extXSessionKey'][0], $staticManifest['extXSessionKey'])) {
                $staticManifest['extXSessionKey'][] = $master['extXSessionKey'][0]; 
            }
            $staticManifest['variants'][] = $master['variants'][0] . ',AUDIO="audio-aacl-96"'; // Add reference to AUDIO Group inconditionnaly
            $staticManifest['variants'][] = $master['variants'][1]; // Add URI stream
    
        // TO TRASH :-////////
        // Process audio (group, ...)
        /*$audioGroupId = null;
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
        }*/

        // Process subs (group, ...)

        // Process video (drm, add audio group, add sub group)

        // Process keyframes

        return $this->rebuildMasterM3u8($staticManifest);
    }

    /**
     * Get Atomic master
     */
    private function getAtomicMaster($url) {
        $type = null;
        $key = null;
        $groupLine = null;
        $groupId = null;
        $variantLine = null;
        $uri = null;
        $keyframe = null;

        // Get Base URI to patch URI    s
        $baseUri = '';
        if (preg_match("#^https?://[^/]+(/.*\.ism/)[^/]*\.m3u8#", $url, $m)) {
            $baseUri = $m[1];
        }

        $lines = file($url, FILE_IGNORE_NEW_LINES);
        $nbLines = count($lines);
        for ($i=0; $i<$nbLines; $i++) {
            $line = $lines[$i];
            switch(true) {
                case (preg_match("%^#EXT-X-SESSION-KEY:.*%", $line, $m)):
                    // DRM KEY
                    $key = $line;
                    break;
                case (preg_match("%^#EXT-X-STREAM-INF:.*CODECS=\"([^\"]*)\"%", $line, $m)):
                    // Variant and URI
                    switch (true) {
                        case (preg_match("#avc1\.#", $m[1])):
                            $type = 'video';
                            break;
                        case (preg_match("#mp4a#", $m[1])):
                            $type = 'audio';
                            break;
                        default:
                            $type = 'text';
                            break;
                    }
                    $variantLine = $line;
                    if (isset($lines[$i+1])) {
                        $uri = $baseUri . $lines[$i+1];
                    }
                    break;
                case (preg_match("%^#EXT-X-I-FRAME-STREAM-INF:.*URI=\"([^\"]+)\".*$%", $line, $m)):
                    // Keyframe (video only)
                    $keyframe = str_replace($m[1], $baseUri . $m[1], $line);
                    break;
                case (preg_match("%^(#EXT-X-MEDIA:.*,GROUP-ID=\"([^\"]+)\".*),URI=\"[^\"]+\"%", $line, $m)):
                    // Group (Audio, Text)
                    $groupLine = $m[1]; // remove ,URI="..."
                    $groupId = $m[2];
                    break;
                case (preg_match("%^(#EXT-X-MEDIA:.*,GROUP-ID=\"([^\"]+)\".*)%", $line, $m)):
                    // Group (Audio, Text)
                    $groupLine = $m[1];
                    $groupId = $m[2];
                    break;
            }
        }

        $toReturn = (object)array(
            'type' => $type,
            'key' => $key,
            'groupLine' => $groupLine,
            'groupId' => $groupId,
            'variantLine' => $variantLine,
            'uri' => $uri,
            'keyframe' => $keyframe
        );

        return $toReturn;

    }

    /**
     * Get Master HLS Manifest and patch its resources (media playlist URL) so it can be served statically
     */
    private function getMasterM3u8($url) {
        $lines = file($url, FILE_IGNORE_NEW_LINES);
        $baseUri = '';
        if (preg_match("#^https?://[^/]+(/.*\.ism/)[^/]*\.m3u8#", $url, $m)) {
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
                //$manifest[$steps[$stepId]][] = $lines[++$i];
                break; 
            case (preg_match("%^# SUBTITLES groups%", $line)):
                $stepId = 3;
                //$manifest[$steps[$stepId]][] = $lines[++$i];
                break; 
            case (preg_match("%^# variants%", $line)):
                $stepId = 4;
                //$manifest[$steps[$stepId]][] = $lines[++$i];
                break; 
            case (preg_match("%^# keyframes%", $line)):
                $stepId = 5;
                //$manifest[$steps[$stepId]][] = $lines[++$i];
                break; 
            case (preg_match("%^#EXT-X-.*,URI=\"([^\"]+)\"%", $line, $m)):
                $newUri = $baseUri . $m[1];
                $newLine = str_replace($m[1], $newUri, $line); // Patch URI to use absolute original URI
                $manifest[$steps[$stepId]][] = $newLine;
                break;
            case (preg_match("%^#EXT-X-STREAM-INF:.*,RESOLUTION=%", $line) && isset($lines[$i+1])):
                $newLine = $baseUri . $lines[$i+1];
                $manifest[$steps[$stepId]][] = $line;
                $manifest[$steps[$stepId]][] = $newLine;
                $i++;
                break;
            default:
                if (empty($line))
                break; // forget empty lines
                $manifest[$steps[$stepId]][] = $line;
                break;
            }
        }
        //  var_dump($manifest); die;
        return $manifest;
    
    }

    private function rebuildMasterM3u8($manifest) {
        $m3u8 = '';
        $m3u8 .= implode("\n", $manifest['header'])."\n";
        $m3u8 .= implode("\n", $manifest['extXSessionKey'])."\n"."\n";
        $m3u8 .= implode("\n", $manifest['audio'])."\n"."\n";
        $m3u8 .= implode("\n", $manifest['subs'])."\n"."\n";
        $m3u8 .= implode("\n", $manifest['variants'])."\n"."\n";
        $m3u8 .= implode("\n", $manifest['variantsAudio'])."\n"."\n";
        $m3u8 .= implode("\n", $manifest['keyframes'])."\n";
        return $m3u8;
    }

}