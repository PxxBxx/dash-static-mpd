<?php
require_once('StaticGeneratorAtomic.class.php');

$payloads = array(
    'HLSClear' => (object)array(
        'type' => 'HLS',
        'origin' => 'https://usp.minitibone.com',
        'streams' => array(
            'video' => array(
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_39366e7f2b94570941b8fdcaacfe708d_clear_224p_hls_v1.ism/Manifest.m3u8',
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_a815347490e2518dc601f97cd80f3501_clear_360p_hls_v1.ism/Manifest.m3u8',
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_34d25b8e381848be678cad638da8a176_clear_404p_hls_v1.ism/Manifest.m3u8',
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_2863760560ad468bc475bab3901b815b_clear_540p_hls_v1.ism/Manifest.m3u8',
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_e3e89100b3afde342d2ec45ef7163879_clear_720p_hls_v1.ism/Manifest.m3u8',
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_7fcd7011ee63f656521c4438403f7389_clear_1080p_hls_v1.ism/Manifest.m3u8',
            ),
            'audio' => array(
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_4d82e4dfbdbade4dc3dee7329d051a0d_t1_lv_st1_hls_v1.ism/Manifest.m3u8',
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_4d82e4dfbdbade4dc3dee7329d051a0d_t2_ov_st1_hls_v1.ism/Manifest.m3u8',
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_4d82e4dfbdbade4dc3dee7329d051a0d_t3_ad_st1_hls_v1.ism/Manifest.m3u8',
            ),
            'text' => array(
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_ov_fra_webvtt_hls_v1.ism/Manifest.m3u8',
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_sdh_fra_webvtt_hls_v1.ism/Manifest.m3u8',
            ),
        ),
    ),
    'HLSDRM' => (object)array(
        'type' => 'HLS',
        'origin' => 'https://usp.minitibone.com',
        'streams' => array(
            'video' => array(
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_39366e7f2b94570941b8fdcaacfe708d_drm_software_224p_hls_v1.ism/Manifest.m3u8',
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_a815347490e2518dc601f97cd80f3501_drm_software_360p_hls_v1.ism/Manifest.m3u8',
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_34d25b8e381848be678cad638da8a176_drm_software_404p_hls_v1.ism/Manifest.m3u8',
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_2863760560ad468bc475bab3901b815b_drm_software_540p_hls_v1.ism/Manifest.m3u8',
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_e3e89100b3afde342d2ec45ef7163879_drm_hardware_720p_hls_v1.ism/Manifest.m3u8',
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_7fcd7011ee63f656521c4438403f7389_drm_hardware_1080p_hls_v1.ism/Manifest.m3u8',
            ),
            'audio' => array(
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_4d82e4dfbdbade4dc3dee7329d051a0d_t1_lv_st1_hls_v1.ism/Manifest.m3u8',
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_4d82e4dfbdbade4dc3dee7329d051a0d_t2_ov_st1_hls_v1.ism/Manifest.m3u8',
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_4d82e4dfbdbade4dc3dee7329d051a0d_t3_ad_st1_hls_v1.ism/Manifest.m3u8',
            ),
            'text' => array(
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_ov_fra_webvtt_hls_v1.ism/Manifest.m3u8',
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_sdh_fra_webvtt_hls_v1.ism/Manifest.m3u8',
            ),
        ),
    ),
    'DASHClear' => (object)array(
        'type' => 'DASH',
        'origin' => 'https://usp.minitibone.com',
        'streams' => array(
            'video' => array(
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_39366e7f2b94570941b8fdcaacfe708d_clear_224p_dash_v1.ism/Manifest.mpd',
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_a815347490e2518dc601f97cd80f3501_clear_360p_dash_v1.ism/Manifest.mpd',
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_34d25b8e381848be678cad638da8a176_clear_404p_dash_v1.ism/Manifest.mpd',
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_2863760560ad468bc475bab3901b815b_clear_540p_dash_v1.ism/Manifest.mpd',
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_e3e89100b3afde342d2ec45ef7163879_clear_720p_dash_v1.ism/Manifest.mpd',
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_7fcd7011ee63f656521c4438403f7389_clear_1080p_dash_v1.ism/Manifest.mpd',        ),
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
    ),
    'DASHDRM' => (object)array(
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
    ),
    'DASHDRMSimpleTrack' => (object)array(
        'type' => 'DASH',
        'origin' => 'https://usp.minitibone.com',
        'streams' => array(
            'video' => array(
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_39366e7f2b94570941b8fdcaacfe708d_drm_software_224p_dash_v1.ism/Manifest.mpd',
            ),
            'audio' => array(
            ),
            'text' => array(
            ),
        ),
    ),
    'HLSDRMSimpleTrack' => (object)array(
        'type' => 'HLS',
        'origin' => 'https://usp.minitibone.com',
        'streams' => array(
            'video' => array(
                '/usp-dev/00ffc4f4c412fc72c02b71cfa222213267a25a30ef30cf44602516fe83b9ecaf_39366e7f2b94570941b8fdcaacfe708d_drm_software_224p_hls_v1.ism/Manifest.m3u8',
            ),
            'audio' => array(
            ),
            'text' => array(
            ),
        ),
    ),
);

if (count($argv) !== 2)
    $argv[1] = '-h';

switch (true) {
    case ($argv[1] == '-h' || !isset($payloads[$argv[1]])):
        echo 'Usage: php ' . $argv[0] . ' <payload label>' . PHP_EOL;
        echo PHP_EOL;
        echo 'Available payload labels :'.PHP_EOL;
        foreach ($payloads as $label => $payload) {
            echo "\t* " . $label . PHP_EOL;
        }
        echo PHP_EOL;
        break;
    default:
        $me = new StaticGeneratorAtomic();
        $me->setPayload($payloads[$argv[1]]);
        echo $me->generate();        
        break;
}
