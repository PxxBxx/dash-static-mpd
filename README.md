# dash-static-mpd
Project to build static MPD manifests referencing external sources

* DevelopMpd.class.php
Class to develop/factorize Nodes inside a MPD structure

* MultipleVideoAdaptationSet.php
Explode video Representation into multiple AdaptationSet, and link them (DASH stream-set-switching)

* StaticGenerator.class.php
Class to generate a static MPD based on SD+HD distinct MPD files
Works on DASH __and__ HLS

* StaticGeneratorAtomic.class.php
Class to generate a static MPD based on _one MPD per elementary stream (video, audio, text)_
Works only in DASH for now (HLS incoming)

## Sample Scripts
* test-atomic-mpd-to-static.php
Use some collections of MPD files to generate static manifests

## Sample input and output

### DASH : Multiple `.ism` => Static `.mpd` with multiple DRM keys (ContentProtection in Representation)
```
    'type' => 'DASH',
    'origin' => 'https://usp.minitibone.com',
    'streams' => array(
        'video' => array(
            '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_39366e7f2b94570941b8fdcaacfe708d_drm_software_224p_dash_v1.ism/Manifest.mpd',
            '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_a815347490e2518dc601f97cd80f3501_drm_software_360p_dash_v1.ism/Manifest.mpd',
            '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_34d25b8e381848be678cad638da8a176_drm_software_404p_dash_v1.ism/Manifest.mpd',
            '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_2863760560ad468bc475bab3901b815b_drm_software_540p_dash_v1.ism/Manifest.mpd',
            '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_e3e89100b3afde342d2ec45ef7163879_drm_hardware_720p_dash_v1.ism/Manifest.mpd',
            '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_7fcd7011ee63f656521c4438403f7389_drm_hardware_1080p_dash_v1.ism/Manifest.mpd',
        ),
        'audio' => array(
            '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_4d82e4dfbdbade4dc3dee7329d051a0d_t1_lv_st1_dash_v1.ism/Manifest.mpd',
            '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_4d82e4dfbdbade4dc3dee7329d051a0d_t2_ov_st1_dash_v1.ism/Manifest.mpd',
            '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_4d82e4dfbdbade4dc3dee7329d051a0d_t3_ad_st1_dash_v1.ism/Manifest.mpd',
        ),
        'text' => array(
            '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_ov_fra_ttml_dash_v1.ism/Manifest.mpd',
            '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_sdh_fra_ttml_dash_v1.ism/Manifest.mpd',
        ),
    ),
```


### DASH : Multiple `.ism` => Static `.mpd` with single DRM key (ContentProtection in AdaptationSet)


### HLS : Multiple `.ism` => Static `.m3u8` with multiple DRM keys


### HLS : Multiple `.ism` => Static `.m3u8` with single DRM key
