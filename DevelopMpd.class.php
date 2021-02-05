<?php

class DevelopMpd {
	public function __construct() {

	}
	public function develop($url, $withBaseUrl = true, $absolute = true) {
		//$url = 'https://usp.minitibone.com/salto_1/section-de-recherches_web_drm_hd_dash_v1.ism/Manifest.mpd';
		//if (isset($argv[1]))
		//	$url = $argv[1];

		if (preg_match("#^(https?://[^/]+)(/.*/)([^/]+\.ism/).*$#", $url, $m)) {
			$base = $m[1].$m[2];
			$prefix = $m[3].'dash/';
			if (!$withBaseUrl) {
				$prefix = $m[2].$prefix;
				$base = null;
			}
		}

		// Load MPD
		if (($content = file_get_contents($url)) === false)
			die('Cannot load MPD');

		// Develop all factorized nodes
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;

		$dom->loadXML($content);

		$BaseURLs = $dom->getElementsByTagName('BaseURL');
		if ($base === null) {
			$BaseURLs[0]->parentNode->removeChild($BaseURLs[0]);
		}
		else {
			$BaseURLs[0]->textContent = $base;
		}

		$Periods = $dom->getElementsByTagName('Period');
		foreach ($Periods as $Period) {
			$AdaptationSets = $Period->getElementsByTagName('AdaptationSet');
			foreach ($AdaptationSets as $AdaptationSet) {
				$toPurge = array();

				$SegmentTemplates = $AdaptationSet->getElementsByTagName('SegmentTemplate');
				$SegmentTemplate = $SegmentTemplates->item(0)->cloneNode(true);

				// Patch media / initialization
				if (($media = $SegmentTemplate->getAttribute('media')) !== '') {
					$SegmentTemplate->setAttribute('media', $prefix.$media);
				}
				if (($initialization = $SegmentTemplate->getAttribute('initialization')) !== '') {
					$SegmentTemplate->setAttribute('initialization', $prefix.$initialization);
				}

				// to purge - original SegmentTemplate
				$toPurge[] = $SegmentTemplates->item(0);


				$ContentProtections = $AdaptationSet->getElementsByTagName('ContentProtection');
				$ClonedContentProtections = array();
				foreach ($ContentProtections as $ContentProtection) {
					$ClonedContentProtections[] = $ContentProtection->cloneNode(true);
					// to purge - ContentProtection(n)
					$toPurge[] = $ContentProtection;
				}

				// Cleanup originals
				foreach ($toPurge as $elt) {
					$elt->parentNode->removeChild($elt);
				}

				$Representations = $AdaptationSet->getElementsByTagName('Representation');

				// Insert clones
				foreach($Representations as $Representation) {
					$Representation->appendChild($SegmentTemplate->cloneNode(true));
					foreach ($ClonedContentProtections as $ContentProtection) {
						$Representation->appendChild($ContentProtection->cloneNode(true));
					}
				}
			}
		}

		$xml = tidy_repair_string($dom->saveXML(), ['input-xml'=> 1, 'indent' => 1, 'wrap' => 0]);

		return $xml;
	}
}