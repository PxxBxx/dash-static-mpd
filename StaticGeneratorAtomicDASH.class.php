<?php
require_once("DevelopMpd.class.php");

class StaticGeneratorAtomicDASH {
    private $payload;

    public function __construct() {
        $this->payload = null;
    }

    public function setPayload($payload) {
        $this->payload = $payload;
    }

    public function generate() {
        return $this->generateStaticDash();
    }

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