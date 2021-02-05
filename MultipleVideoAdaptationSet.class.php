<?php

class MultipleVideoAdaptationSet {

    public function __construct() {
        //

    }

    public function factorize($url) {
        if (($mpd = file_get_contents($url)) === false) {
            die("Cannot load MPD");
        }

        $baseUrl = '';
        if (preg_match("#^(.*/)[^/]+$#", $url, $m)) {
            $baseUrl = $m[1];
        }
        $dom = new DOMDocument();
        $dom->loadXML($mpd);
        //$xpath = new DOMXpath($dom);

        $Periods = $dom->getElementsByTagName('Period');
        foreach ($Periods as $Period) {
            $keysRIds = array();
            $AdaptationSets = $Period->getElementsByTagName('AdaptationSet');
            $AS = null;
            foreach ($AdaptationSets as $AdaptationSet) {
                if ($AdaptationSet->getAttribute('contentType') !== 'video') {
                    continue; // Not Video AdaptationSet
                }
                // Find all keys
                $CPs = $AdaptationSet->getElementsByTagName('ContentProtection');
                foreach ($CPs as $CP) {
                    if ($CP->hasAttributeNS('urn:mpeg:cenc:2013', 'default_KID')) {
                        $key = $CP->getAttributeNS('urn:mpeg:cenc:2013', 'default_KID');
                        $id = $CP->parentNode->getAttribute('id');
                        $keysRIds[$key][] = $id;
                    }
                }
                // Save the AS
                $AS = $AdaptationSet;
                break;
            }
            // insert new AS and factorize them
            $ASid = $AS->getAttribute('id');
            $ASswitching = array();
            foreach ($keysRIds as $key => $RIds) {
                $newAS = $AS->cloneNode(true);
                $Rs = $newAS->getElementsByTagName('Representation');
                $toDelete = array();
                $CPisCloned = false;
                foreach ($Rs as $R) {
                    $id = $R->getAttribute('id');
                    if (!in_array($id, $RIds)) {
                        $toDelete[] = $R; // mark Representation TO DELETE
                    }
                    else {
                        $CPs = $R->getElementsByTagName('ContentProtection');
                        if (!$CPisCloned) {
                            // Clone the ContentProtection(s) into the AS
                            foreach ($CPs as $CP) {
                                $newAS->appendChild($CP->cloneNode(true));
                            }
                            $CPisCloned = true;
                        }
                        // Delete the ContentProtection
                        foreach ($CPs as $CP) {
                            $toDelete[] = $CP;
                        }
                    }
                }
                foreach ($toDelete as $elt) {
                    $elt->parentNode->removeChild($elt);
                }
                $newAS->setAttribute('id', ++$ASid);
                $ASswitching[] = $ASid;
                $AS->parentNode->appendChild($newAS);
            }
            // Delete the original AS
            $AS->parentNode->removeChild($AS);

            // Set BaseURL
            $eltBaseURL = $dom->createElement('BaseURL', $baseUrl);
            $Period->appendChild($eltBaseURL);

            // Post-move the AS so that the BaseURL is first
            $toMove = array();
            $ASes = $Period->getElementsByTagName('AdaptationSet');
            foreach ($ASes as $ASn) {
                $toMove[] = $ASn;
            }
            foreach ($toMove as $ASn) {
                if ($ASn->getAttribute('contentType') === 'video') {
                    // Video ! Lets add the SupplementalProperty NEW!!!
                    // <SupplementalProperty schemeIdUri="urn:mpeg:dash:adaptation-set-switching:2016" value="1,3"/>
                    $currentId = $ASn->getAttribute('id');
                    $switchIds = array();
                    foreach ($ASswitching as $id) {
                        if ($id != $currentId) {
                            $switchIds[] = $id;
                        }
                    }
                    $elt = $dom->createElement('SupplementalProperty');
                    $elt->setAttribute('schemeIdUri','urn:mpeg:dash:adaptation-set-switching:2016');
                    $elt->setAttribute('value',implode(', ', $switchIds));
                    $ASn->appendChild($elt);
                    // And as ever.... post-Move the Elements (Supplemental > ContentProtection > Representation)
                    $localToMove = array();
                    $STs = $ASn->getElementsByTagName('SegmentTemplate');
                    foreach ($STs as $ST) {
                        $localToMove[] = $ST;
                    }
                    $CPs = $ASn->getElementsByTagName('ContentProtection');
                    foreach ($CPs as $CP) {
                        $localToMove[] = $CP;
                    }
                    $Rs = $ASn->getElementsByTagName('Representation');
                    foreach ($Rs as $R) {
                        $localToMove[] = $R;
                    }
                    foreach ($localToMove as $elt) {
                        $ASn->appendChild($elt);
                    }
                }
                $Period->appendChild($ASn);
            }

        }

        return tidy_repair_string($dom->saveXML(), ['input-xml'=> 1, 'indent' => 1, 'wrap' => 0]);
    }

    private function mergeRepresentations($AS) {

    }
}




// Local Test
if (isset($argv[1])) {
    $me = new MultipleVideoAdaptationSet();
    echo $me->factorize($argv[1]);
  }
  