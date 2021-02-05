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