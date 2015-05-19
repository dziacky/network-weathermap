<?php
// Sample Pluggable datasource for PHP Weathermap 0.9
// - read a pair of values from a database, and return it

// TARGET dbplug:databasename:username:pass:hostkey

class WeatherMapDataSource_tabfile extends WeatherMapDataSource
{

    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/\.(tsv|txt)$/'
        );

    }

    /**
     * @param string $targetString The string from the config file
     * @param the $map A reference to the map object (redundant)
     * @param the $mapItem A reference to the object this target is attached to
     * @return array invalue, outvalue, unix timestamp that the data was valid
     */
    public function ReadData($targetString, &$map, &$mapItem)
    {
        $data[IN] = null;
        $data[OUT] = null;
        $dataTime = 0;

        $itemName = $mapItem->name;

        $fullpath = realpath($targetString);

        wm_debug("Opening $fullpath\n");

        if (! file_exists($fullpath)) {
            wm_warn("File '$fullpath' doesn't exist.");
            return array(null, null, null);
        }

        if (! is_readable($fullpath)) {
            wm_warn("File '$fullpath' isn't readable.");
            return array(null, null, null);
        }

        $fileHandle=fopen($fullpath, "r");

        if ($fileHandle) {
            $data = $this->readDataFromTSV($fileHandle, $itemName);
            $stats = stat($fullpath);
            $dataTime = $stats['mtime'];
        } else {
            // some error code to go in here
            wm_warn("TabText ReadData: Couldn't open ($fullpath). [WMTABDATA01]\n");
        }

        wm_debug("TabText ReadData: Returning (".($data[IN]===null ? 'null' : $data[IN]) . "," . ($data[OUT]===null ? 'null' : $data[OUT]).",$dataTime)\n");

        return( array($data[IN], $data[OUT], $dataTime) );
    }

    /**
     * @param $fileHandle
     * @param $itemName
     * @return array
     */
    protected function readDataFromTSV($fileHandle, $itemName)
    {
        $data = array();
        $data[IN] = null;
        $data[OUT] = null;

        while (!feof($fileHandle)) {
            $buffer = fgets($fileHandle, 4096);

            // strip out any line-endings that have gotten in here
            $buffer = str_replace("\r", "", $buffer);
            $buffer = str_replace("\n", "", $buffer);

            $parts = explode("\t", $buffer);

            if ($parts[0] == $itemName) {
                $data[IN] = ($parts[1] == "-" ? null : WMUtility::interpretNumberWithMetricPrefix($parts[1]));
                $data[OUT] = ($parts[2] == "-" ? null : WMUtility::interpretNumberWithMetricPrefix($parts[2]));
            }
        }

        return $data;
    }
}

// vim:ts=4:sw=4:
